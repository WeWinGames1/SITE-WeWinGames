<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CareerApplicationSubmitted;
use Illuminate\Support\Facades\Mail;

class CareerApplicationController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'about'      => 'required|string',
            'position'   => 'nullable|string|max:255',
            // 'resume'     => 'nullable|file|mimes:pdf,doc,docx|max:2048', // Optionally handle file
        ]);

        // Optionally handle file upload here

        // Send notification to Tony
        Notification::route('mail', 'tony@wewingames.com')
            ->notify(new CareerApplicationSubmitted($validated));

        return back()->with('success', 'Your application has been submitted!');
    }
}