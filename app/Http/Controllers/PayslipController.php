<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function download(Payslip $payslip)
    {
        // Check if user has permission to download this payslip
        if (!Auth::user()->can('view_my_payslips') &&
            !Auth::user()->can('process_payroll') &&
            $payslip->payroll->employee->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to payslip.');
        }

        // Check if file exists in public disk
        if (!Storage::disk('public')->exists($payslip->file_path)) {
            abort(404, 'Payslip file not found.');
        }

        // Log the download for audit purposes
        \App\Models\Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'payslip_download',
            'target_type' => Payslip::class,
            'target_id' => $payslip->id,
            'details' => json_encode([
                'downloaded_at' => now(),
                'file_path' => $payslip->file_path,
            ]),
        ]);

        // Return the file for download from public disk
        return Storage::disk('public')->download(
            $payslip->file_path,
            'payslip_' . $payslip->payroll->employee->employee_number . '_' . $payslip->payroll->payroll_period . '.pdf'
        );
    }
}
