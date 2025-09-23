<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Language;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get active languages from database
        $activeLanguages = Language::active()->pluck('code')->toArray();
        $defaultLanguage = 'en';
        
        // Determine language preference priority:
        // 1. Session locale (from language switcher)
        // 2. Authenticated user's language preference
        // 3. Browser language (Accept-Language header)
        // 4. Default language
        
        $language = $defaultLanguage;
        
        // Check session first
        if (Session::has('locale')) {
            $sessionLanguage = Session::get('locale');
            if (in_array($sessionLanguage, $activeLanguages)) {
                $language = $sessionLanguage;
            }
        }
        // Check authenticated user's preference
        elseif (Auth::check() && Auth::user()->lang) {
            $userLanguage = Auth::user()->lang;
            if (in_array($userLanguage, $activeLanguages)) {
                $language = $userLanguage;
            }
        }
        // Check browser language as fallback
        else {
            $browserLanguage = $request->getPreferredLanguage($activeLanguages);
            if ($browserLanguage && in_array($browserLanguage, $activeLanguages)) {
                $language = $browserLanguage;
            }
        }
        
        // Set the application locale
        app()->setLocale($language);
        
        return $next($request);
    }
}
