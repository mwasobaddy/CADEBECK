<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Language;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     */
    public function switch(Request $request, $language)
    {
        // Validate that the language exists and is active in the database
        $languageRecord = Language::where('code', $language)
            ->where('is_active', true)
            ->first();
        
        if (!$languageRecord) {
            return redirect()->back()->with('error', __('Language not supported.'));
        }
        
        // Set the language in session
        Session::put('locale', $language);
        
        // If user is authenticated, update their language preference
        if (Auth::check()) {
            $user = Auth::user();
            $user->update(['lang' => $language]);
        }
        
        // Set the app locale
        app()->setLocale($language);
        
        return redirect()->back()->with('success', __('Language changed successfully.'));
    }
}
