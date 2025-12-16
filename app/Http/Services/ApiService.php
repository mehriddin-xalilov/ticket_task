<?php
namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ApiService
{
    public function getToken(): string
    {
        return Cache::remember('api_token', 3500, function () {
            $response = Http::asForm()->post(config('services.api.base_url') . '/security/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.api.client_id'),
                'client_secret' => config('services.api.client_secret'),
            ]);
            return $response->json('access_token');
        });
    }

    public function getTickets(): array
    {
        try {
            $token = $this->getToken();
            \Log::info('Using token', ['token' => substr($token, 0, 20) . '...']);

            $response = Http::withToken($token)
                ->get(config('services.api.base_url') . '/v1/booking/flight-orders');
            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Failed to fetch tickets', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
