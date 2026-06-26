<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login dedicato del Portale Agenzie (host b2b). Autentica SOLO utenti con
 * ruolo b2b: qualunque altro ruolo viene rifiutato anche con credenziali valide,
 * così l'area resta isolata dal sito cliente/admin.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('b2b.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Verifichiamo PRIMA che l'utente sia un'agenzia: non vogliamo nemmeno
        // tentare il login di customer/admin sull'host b2b.
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if (! $user || ! $user->isB2b()) {
            throw ValidationException::withMessages([
                'email' => 'Credenziali non valide per l\'area agenzie.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Credenziali non valide.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('b2b.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('b2b.login');
    }
}
