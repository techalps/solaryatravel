<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    /**
     * Display a listing of discount codes.
     */
    public function index(Request $request): View
    {
        $query = DiscountCode::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('valid_until', '<', now());
            } elseif ($request->status === 'upcoming') {
                $query->where('valid_from', '>', now());
            }
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('discount_type', $request->type);
        }

        $discounts = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => DiscountCode::count(),
            'active' => DiscountCode::where('is_active', true)->count(),
            'expired' => DiscountCode::where('valid_until', '<', now())->count(),
            'total_usage' => DiscountCode::sum('usage_count'),
        ];

        return view('admin.discounts.index', compact('discounts', 'stats'));
    }

    /**
     * Show the form for creating a new discount code.
     */
    public function create(): View
    {
        return view('admin.discounts.create');
    }

    /**
     * Store a newly created discount code.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:discount_codes,code',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_limit' => 'integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        // Uppercase the code
        $validated['code'] = strtoupper($validated['code']);

        // Validate percentage max
        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'La percentuale non può superare 100%'])->withInput();
        }

        DiscountCode::create($validated);

        return redirect()
            ->route('admin.discounts.index')
            ->with('success', 'Codice sconto creato con successo.');
    }

    /**
     * Display the specified discount code.
     */
    public function show(DiscountCode $discount): View
    {
        // 'catamaran' e' una relazione di BookingSeat, non di Booking: qui
        // faceva andare in 500 la pagina di dettaglio del codice sconto.
        $recentBookings = $discount->bookings()
            ->with(['tour', 'seatRecords.catamaran'])
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'total_bookings' => $discount->bookings()->count(),
            'total_discount' => $discount->bookings()->sum('discount_amount'),
            'usage_rate' => $discount->usage_limit 
                ? round(($discount->usage_count / $discount->usage_limit) * 100, 1) 
                : null,
        ];

        return view('admin.discounts.show', compact('discount', 'recentBookings', 'stats'));
    }

    /**
     * Show the form for editing the specified discount code.
     */
    public function edit(DiscountCode $discount): View
    {
        return view('admin.discounts.edit', compact('discount'));
    }

    /**
     * Update the specified discount code.
     */
    public function update(Request $request, DiscountCode $discount): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:discount_codes,code,' . $discount->id,
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_limit' => 'integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        // Uppercase the code
        $validated['code'] = strtoupper($validated['code']);

        // Validate percentage max
        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'La percentuale non può superare 100%'])->withInput();
        }

        $discount->update($validated);

        return redirect()
            ->route('admin.discounts.index')
            ->with('success', 'Codice sconto aggiornato con successo.');
    }

    /**
     * Remove the specified discount code.
     */
    public function destroy(DiscountCode $discount): RedirectResponse
    {
        // Check for bookings
        $bookingsCount = $discount->bookings()->count();

        if ($bookingsCount > 0) {
            return back()->with('error', "Impossibile eliminare: ci sono {$bookingsCount} prenotazioni con questo codice.");
        }

        $discount->delete();

        return redirect()
            ->route('admin.discounts.index')
            ->with('success', 'Codice sconto eliminato con successo.');
    }

    /**
     * Toggle discount code active status.
     */
    public function toggle(DiscountCode $discount): RedirectResponse
    {
        $discount->update(['is_active' => !$discount->is_active]);

        $status = $discount->is_active ? 'attivato' : 'disattivato';

        return back()->with('success', "Codice sconto {$status} con successo.");
    }

    /**
     * Generate a unique discount code.
     */
    public function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (DiscountCode::where('code', $code)->exists());

        return $code;
    }
}
