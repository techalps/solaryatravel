<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
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
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'         => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date|before:today',
        ], [
            'date_of_birth.before' => 'La data di nascita deve essere nel passato.',
        ]);

        $user->update($validated);

        return redirect()->route('profile')->with('success', 'Dati aggiornati con successo.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', function ($attr, $val, $fail) use ($user) {
                if (!Hash::check($val, $user->password)) {
                    $fail('La password attuale non è corretta.');
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Le password non coincidono.',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('profile', ['#sicurezza'])->with('success_password', 'Password aggiornata con successo.');
    }
}
