<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Un utente b2b non opera sul dominio principale: lo rispediamo al suo
        // portale (la pagina di login del B2B è separata, su b2b.solaryatravel).
        if ($request->user()->isB2b()) {
            $scheme = $request->getScheme();
            return redirect()->away($scheme.'://'.config('b2b.host'));
        }

        // Redirect esplicito (es. ritorno alla pagina di pagamento dopo login),
        // accettato solo se è un percorso interno per evitare open-redirect.
        $redirect = $request->input('redirect');
        if (! $request->user()->isAdmin() && is_string($redirect) && str_starts_with($redirect, url('/'))) {
            return redirect()->to($redirect);
        }

        // Area di atterraggio per ruolo (lo skipper va all'imbarco, non alla
        // dashboard che non può vedere).
        return redirect()->intended(route($request->user()->homeRouteName()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
