<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShowcaseController extends Controller
{
    public function home()
    {
        return view('showcase.home');
    }

    public function pricing()
    {
        return view('showcase.pricing');
    }

    public function features()
    {
        return view('showcase.features');
    }

    public function about()
    {
        return view('showcase.about');
    }

    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:255',
                'company' => 'nullable|string|max:255',
                'message' => 'required|string',
            ]);

            return back()->with('message_sent', true);
        }

        return view('showcase.contact');
    }

    public function blog()
    {
        return view('showcase.blog');
    }

    public function blogPost($slug)
    {
        return view('showcase.blog-post', ['slug' => $slug]);
    }

    public function downloadDemo(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'company' => 'required|string|max:255',
            ]);

            return back()->with('download_ready', true);
        }

        return view('showcase.download-demo');
    }
}
