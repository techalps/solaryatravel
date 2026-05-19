<?php

namespace App\Http\Controllers;

use App\Models\Catamaran;
use App\Models\Tour;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $tours = Tour::active()->ordered()
            ->with(['images' => fn($q) => $q->where('is_primary', true), 'ageBrackets'])
            ->take(6)
            ->get();

        $minPrice = Tour::active()
            ->with('ageBrackets')
            ->get()
            ->map(fn ($t) => $t->ageBrackets->where('counts_as_seat', true)->min('price'))
            ->filter()
            ->min();

        $testimonials = [
            [
                'name' => 'Marco R.',
                'location' => 'Milano',
                'rating' => 5,
                'text' => 'Un\'esperienza indimenticabile! L\'equipaggio è stato fantastico e il catamarano era perfetto. Sicuramente torneremo.',
                'avatar' => null,
            ],
            [
                'name' => 'Sophie M.',
                'location' => 'Francia',
                'rating' => 5,
                'text' => 'Parfait! La journée en mer était magnifique. Le personnel était très professionnel et attentionné.',
                'avatar' => null,
            ],
            [
                'name' => 'Giovanni P.',
                'location' => 'Roma',
                'rating' => 5,
                'text' => 'Il pranzo a bordo era eccezionale. L\'escursione privata al tramonto è stata magica. Consigliatissimo!',
                'avatar' => null,
            ],
        ];

        $stats = [
            'happy_guests' => 2500,
            'years_experience' => 15,
            'excursions' => 850,
            'catamarans' => Catamaran::where('is_active', true)->count(),
        ];

        $minBookingDate = Carbon::now()
            ->addHours(config('booking.advance_hours', 24))
            ->toDateString();

        $searchTours = Tour::active()->ordered()->get(['id', 'name']);
        $tourSearch = [
            'tour' => null,
            'date' => null,
            'adults' => 2,
            'children' => 0,
        ];

        return view('home', compact('tours', 'minPrice', 'testimonials', 'stats', 'minBookingDate', 'searchTours', 'tourSearch'));
    }
}

