<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * L'Accept-Language italiano è esplicito perché il client di test di
     * Symfony invia per default "en-us,en;q=0.5": senza questo header la home
     * verrebbe (correttamente) reindirizzata a /en dal rilevamento automatico
     * della lingua. Vedi App\Http\Middleware\SetLocale.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->withHeader('Accept-Language', 'it-IT,it;q=0.9')->get('/');

        $response->assertStatus(200);
    }
}
