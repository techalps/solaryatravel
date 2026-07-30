<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AddonController extends Controller
{
    /**
     * Display a listing of addons.
     */
    public function index(): View
    {
        $addons = Addon::withCount('bookings')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.addons.index', compact('addons'));
    }

    /**
     * Show the form for creating a new addon.
     */
    public function create(): View
    {
        return view('admin.addons.create');
    }

    /**
     * Store a newly created addon.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:addons,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|in:per_person,per_booking,per_unit',
            'max_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'requires_advance_booking' => 'boolean',
            'advance_hours' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sort_order' => 'integer|min:0',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Addon::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('addons', 'public');
        }

        $addon = Addon::create($validated);

        return redirect()
            ->route('admin.addons.index')
            ->with('success', 'Extra creato con successo.');
    }

    /**
     * Display the specified addon.
     */
    public function show(Addon $addon): View
    {
        $addon->loadCount('bookings');
        
        $stats = [
            'total_bookings' => $addon->bookings()->count(),
            'total_revenue' => $addon->bookings()->sum('booking_addons.total_price'),
            'avg_quantity' => $addon->bookings()->avg('booking_addons.quantity') ?? 0,
        ];

        $recentBookings = $addon->bookings()
            ->with('catamaran')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.addons.show', compact('addon', 'stats', 'recentBookings'));
    }

    /**
     * Show the form for editing the specified addon.
     */
    public function edit(Addon $addon): View
    {
        return view('admin.addons.edit', compact('addon'));
    }

    /**
     * Update the specified addon.
     */
    public function update(Request $request, Addon $addon): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:addons,slug,' . $addon->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|in:per_person,per_booking,per_unit',
            'max_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'requires_advance_booking' => 'boolean',
            'advance_hours' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sort_order' => 'integer|min:0',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($addon->image_path) {
                Storage::disk('public')->delete($addon->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('addons', 'public');
        }

        $addon->update($validated);

        // Traduzioni dei testi (nome, descrizione) per le lingue attive.
        $submitted = $request->input('translations');
        if (is_array($submitted)) {
            foreach (\App\Support\Locales::translatable() as $locale) {
                if (isset($submitted[$locale]) && is_array($submitted[$locale])) {
                    $addon->setTranslations($locale, $submitted[$locale]);
                }
            }
            $addon->save();
        }

        return redirect()
            ->route('admin.addons.index')
            ->with('success', 'Extra aggiornato con successo.');
    }

    /**
     * Remove the specified addon.
     */
    public function destroy(Addon $addon): RedirectResponse
    {
        // Check for bookings using this addon
        $bookingsCount = $addon->bookings()->count();

        if ($bookingsCount > 0) {
            return back()->with('error', "Impossibile eliminare: ci sono {$bookingsCount} prenotazioni con questo extra.");
        }

        // Delete image
        if ($addon->image_path) {
            Storage::disk('public')->delete($addon->image_path);
        }

        $addon->delete();

        return redirect()
            ->route('admin.addons.index')
            ->with('success', 'Extra eliminato con successo.');
    }

    /**
     * Toggle addon active status.
     */
    public function toggle(Addon $addon): RedirectResponse
    {
        $addon->update(['is_active' => !$addon->is_active]);

        $status = $addon->is_active ? 'attivato' : 'disattivato';

        return back()->with('success', "Extra {$status} con successo.");
    }

    /**
     * Reorder addons.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:addons,id',
        ]);

        foreach ($request->order as $position => $addonId) {
            Addon::where('id', $addonId)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
