<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    /**
     * Display the privacy policy page.
     */
    public function privacy(): View
    {
        return view('pages.privacy');
    }

    /**
     * Display the terms and conditions page.
     */
    public function terms(): View
    {
        return view('pages.terms');
    }

    /**
     * Display the cookie policy page.
     */
    public function cookies(): View
    {
        return view('pages.cookies');
    }

    /**
     * Display the user profile page.
     */
    public function profile(): View
    {
        $user = auth()->user();
        $bookings = $user->bookings()
            ->with(['tour', 'departure'])
            ->orderByDesc('booking_date')
            ->take(10)
            ->get();

        return view('pages.profile', compact('user', 'bookings'));
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
        ]);

        $user->update($validated);

        return redirect()
            ->route('profile')
            ->with('success', 'Profilo aggiornato con successo!');
    }
}
