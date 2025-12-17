<?php
namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiService
{
    public function getToken(): ?string
    {
        return Cache::remember('api_token', 3500, function () {
            try {
                $response = Http::asForm()->post(config('services.api.base_url') . '/security/oauth2/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.api.client_id'),
                    'client_secret' => config('services.api.client_secret'),
                ]);

                if ($response->successful()) {
                    $token = $response->json('access_token');
                    if ($token) {
                        Log::info('Token retrieved successfully');
                        return $token;
                    }
                }

                Log::error('Failed to get token', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Exception while getting token', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    public function getTickets(): array
    {
        try {
            $token = $this->getToken();
            
            if (!$token) {
                Log::error('No token available to fetch tickets');
                return ['error' => 'Unable to authenticate with Amadeus API'];
            }

            Log::info('Using token', ['token' => substr($token, 0, 20) . '...']);

            // base_url already includes /v1, so we don't need to add it again
            $response = Http::withToken($token)
                ->get(config('services.api.base_url') . '/booking/flight-orders');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch tickets', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'error' => 'Failed to fetch tickets from Amadeus API',
                'status' => $response->status(),
                'message' => $response->json('errors') ?? $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while fetching tickets', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}
