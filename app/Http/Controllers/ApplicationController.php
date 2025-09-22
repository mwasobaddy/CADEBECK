<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Download the CV for a specific application.
     */
    public function downloadCV(Application $application)
    {
        // Check if the CV blob exists
        if (!$application->cv_blob) {
            abort(404, 'CV not found');
        }

        // For now, return the binary data as a downloadable file
        // In a real application, you might want to store files on disk and serve them
        return response($application->cv_blob)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $application->name . '_CV.pdf"');
    }
}