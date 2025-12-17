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
                        return $token;
                    }
                }
                return null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }
    public function getTickets(
        string $from = 'MAD',
        string $to = 'NYC',
        string $departureDate = '2024-12-20',
        ?string $returnDate = null,
        int $adults = 1
    ): array {
        try {
            $token = $this->getToken();

            if (!$token) {
                return ['error' => 'Unable to authenticate'];
            }

            $response = Http::withToken($token)->get(
                config('services.api.base_url') . '/shopping/flight-offers',
                [
                    'originLocationCode' => strtoupper($from),
                    'destinationLocationCode' => strtoupper($to),
                    'departureDate' => $departureDate,
                    'returnDate' => $returnDate,
                    'adults' => $adults,
                    'max' => 250,
                ]

            );

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'error' => 'Failed to fetch flights',
                'status' => $response->status(),
                'message' => $response->json('errors.0.detail') ?? $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Flight API error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

//    public function getTickets(): array
//    {
//        try {
//            $token = $this->getToken();
//
//            if (!$token) {
//                return ['error' => 'Unable to authenticate with Amadeus API'];
//            }
//            $response = Http::withToken($token)->get(
//                config('services.api.base_url') . '/reference-data/locations',
//                [
//                    'subType' => 'AIRPORT',
//                    'keyword' => 'MAD',
//                ]
//            );
//
//            if ($response->successful()) {
//                return $response->json();
//            }
//
//            return [
//                'error' => 'Failed to fetch tickets from Amadeus API',
//                'status' => $response->status(),
//                'message' => $response->json('errors') ?? $response->body()
//            ];
//        } catch (\Exception $e) {
//            return ['error' => $e->getMessage()];
//        }
//    }
}
