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

        // Check if file exists
        if (!Storage::exists($payslip->file_path)) {
            abort(404, 'Payslip file not found.');
        }

        // Log the download for audit purposes
        \App\Models\Audit::create([
            'user_id' => Auth::id(),
            'action' => 'payslip_download',
            'model_type' => Payslip::class,
            'model_id' => $payslip->id,
            'old_values' => null,
            'new_values' => ['downloaded_at' => now()],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Return the file for download
        return Storage::download(
            $payslip->file_path,
            'payslip_' . $payslip->payroll->employee->employee_number . '_' . $payslip->payroll->payroll_period . '.pdf'
        );
    }
}
