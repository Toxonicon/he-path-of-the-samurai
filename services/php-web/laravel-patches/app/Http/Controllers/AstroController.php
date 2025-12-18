<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AstroController extends Controller
{
    public function events(Request $r)
    {
        $lat  = (float) $r->query('lat', 55.7558);
        $lon  = (float) $r->query('lon', 37.6176);
        $days = max(1, min(30, (int) $r->query('days', 7)));

        $from = now('UTC')->format('Y-m-d');
        $to   = now('UTC')->addDays($days)->format('Y-m-d');

        $appId  = env('ASTRO_APP_ID', '');
        $secret = env('ASTRO_APP_SECRET', '');
        
        // Если credentials не настроены, возвращаем mock данные
        if (empty($appId) || empty($secret) || $appId === 'your-app-id-here') {
            return response()->json([
                'data' => [
                    'table' => [
                        'header' => ['Body', 'Position', 'Date', 'Extra'],
                        'rows' => [
                            [
                                'entry' => [
                                    'id' => '1',
                                    'name' => 'Moon',
                                ],
                                'cells' => [
                                    ['id' => 'body', 'name' => 'Луна', 'value' => ['string' => 'Луна']],
                                    ['id' => 'position', 'name' => 'Восход', 'value' => ['string' => 'Восход']],
                                    ['id' => 'date', 'name' => now()->format('Y-m-d H:i'), 'value' => ['string' => now()->format('Y-m-d H:i')]],
                                    ['id' => 'extra', 'name' => 'Азимут: 45°', 'value' => ['string' => 'Азимут: 45°']],
                                ],
                            ],
                            [
                                'entry' => [
                                    'id' => '2',
                                    'name' => 'Sun',
                                ],
                                'cells' => [
                                    ['id' => 'body', 'name' => 'Солнце', 'value' => ['string' => 'Солнце']],
                                    ['id' => 'position', 'name' => 'Заход', 'value' => ['string' => 'Заход']],
                                    ['id' => 'date', 'name' => now()->addHours(5)->format('Y-m-d H:i'), 'value' => ['string' => now()->addHours(5)->format('Y-m-d H:i')]],
                                    ['id' => 'extra', 'name' => 'Азимут: 280°', 'value' => ['string' => 'Азимут: 280°']],
                                ],
                            ],
                        ],
                    ],
                ],
                'message' => 'Demo data. Set ASTRO_APP_ID and ASTRO_APP_SECRET in .env to get real data from AstronomyAPI.com'
            ]);
        }

        $auth = base64_encode($appId . ':' . $secret);
        
        // Используем endpoint bodies/positions для получения событий
        $url = 'https://api.astronomyapi.com/api/v2/bodies/positions';
        
        $params = [
            'latitude' => $lat,
            'longitude' => $lon,
            'from_date' => $from,
            'to_date' => $to,
            'time' => '12:00:00',
        ];

        $ch = curl_init($url . '?' . http_build_query($params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
            ],
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 0;
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            \Log::error('AstronomyAPI Error', [
                'code' => $code,
                'error' => $err,
                'response' => $raw
            ]);
            
            return response()->json([
                'error' => $err ?: ("HTTP " . $code),
                'code' => $code,
                'message' => 'Failed to fetch from AstronomyAPI. Check your credentials.',
                'raw' => substr($raw, 0, 500)
            ], $code >= 400 ? $code : 500);
        }
        
        $json = json_decode($raw, true);
        return response()->json($json ?? ['raw' => $raw]);
    }
}
