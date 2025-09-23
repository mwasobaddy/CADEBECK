<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Language;

class LanguageComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $languages = Language::active()->ordered()->get();
        $view->with('availableLanguages', $languages);
    }
}