<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Ruoli assegnabili dall'utente corrente. Il ruolo tecnico system_admin
     * è selezionabile/visibile SOLO da un system_admin.
     */
    private function availableRoles(): array
    {
        $roles = [
            'customer'    => 'Cliente',
            'admin'       => 'Amministratore',
            'super_admin' => 'Super Admin',
        ];
        if (auth()->user()->isSystemAdmin()) {
            $roles['system_admin'] = 'System Admin (tecnico)';
        }
        return $roles;
    }

    public function index(Request $request): View
    {
        $query = User::query();

        // Gli utenti tecnici (system_admin) sono visibili solo a un altro system_admin.
        if (! auth()->user()->isSystemAdmin()) {
            $query->where('role', '!=', 'system_admin');
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $roles = $this->availableRoles();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = $this->availableRoles();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'role'          => ['required', Rule::in(array_keys($this->availableRoles()))],
            'phone'         => ['nullable', 'string', 'max:30'],
            'tax_code'      => ['nullable', 'string', 'min:11', 'max:16'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);

        User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'role'          => $validated['role'],
            'phone'         => $validated['phone'] ?? null,
            'tax_code'      => !empty($validated['tax_code']) ? strtoupper(trim($validated['tax_code'])) : null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente creato con successo.');
    }

    public function edit(User $user): View
    {
        // Un utente tecnico può essere modificato solo da un altro system_admin.
        abort_if($user->role === 'system_admin' && ! auth()->user()->isSystemAdmin(), 403);

        $roles = $this->availableRoles();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Un utente tecnico può essere modificato solo da un altro system_admin.
        abort_if($user->role === 'system_admin' && ! auth()->user()->isSystemAdmin(), 403);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'          => ['required', Rule::in(array_keys($this->availableRoles()))],
            'phone'         => ['nullable', 'string', 'max:30'],
            'tax_code'      => ['nullable', 'string', 'min:11', 'max:16'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);

        // Prevent demoting the only super_admin
        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->withErrors(['role' => 'Non puoi rimuovere l\'unico Super Admin.'])->withInput();
            }
        }

        // Prevent demoting the only system_admin (ruolo tecnico).
        if ($user->role === 'system_admin' && $validated['role'] !== 'system_admin') {
            if (User::where('role', 'system_admin')->count() <= 1) {
                return back()->withErrors(['role' => 'Non puoi rimuovere l\'unico System Admin.'])->withInput();
            }
        }

        $data = [
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'role'          => $validated['role'],
            'phone'         => $validated['phone'] ?? null,
            'tax_code'      => !empty($validated['tax_code']) ? strtoupper(trim($validated['tax_code'])) : null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente aggiornato con successo.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Un utente tecnico può essere eliminato solo da un altro system_admin.
        abort_if($user->role === 'system_admin' && ! auth()->user()->isSystemAdmin(), 403);

        // Prevent deleting the only system_admin (ruolo tecnico).
        if ($user->role === 'system_admin' && User::where('role', 'system_admin')->count() <= 1) {
            return back()->with('error', 'Non puoi eliminare l\'unico System Admin.');
        }

        // Prevent deleting the only super_admin
        if ($user->role === 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Non puoi eliminare l\'unico Super Admin.');
            }
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminare il tuo stesso account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente eliminato.');
    }
}
