<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['en', 'ms'], true), 404);

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->update(['preferred_language' => $locale]);
        }

        return redirect()->back();
    }
}
