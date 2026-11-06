<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;
use RuntimeException;
use Throwable;

/**
 * Server-side Open-Meteo client (avoids browser CORS on the public homepage).
 */
class OpenMeteoService
{
    private const CACHE_TTL = 900;

    /**
     * WAMP/local dev often lacks CA bundle — allow opt-out via .env curlrequest.verify=false
     *
     * @return array<string, mixed>
     */
    private function httpOptions(): array
    {
        $verify = env('curlrequest.verify');
        if ($verify === null || $verify === '') {
            $verify = ENVIRONMENT === 'production';
        } else {
            $verify = filter_var($verify, FILTER_VALIDATE_BOOLEAN);
        }

        return [
            'timeout'     => 25,
            'http_errors' => false,
            'verify'      => $verify,
            'headers'     => [
                'Accept'     => 'application/json',
                'User-Agent' => 'DRMS/1.0 (LGU weather display)',
            ],
        ];
    }

    /**
     * @return array{current: array<string, mixed>, source: string}
     */
    public function getCurrentConditions(float $lat, float $lng): array
    {
        $lat = round($lat, 4);
        $lng = round($lng, 4);
        $key = 'openmeteo_current_' . $lat . '_' . $lng;

        $cache = cache();
        $hit   = $cache->get($key);
        if (is_array($hit) && isset($hit['current'])) {
            return $hit;
        }

        try {
            $payload = $this->fetchFromOpenMeteo($lat, $lng);
        } catch (Throwable $openMeteoError) {
            log_message('warning', 'Open-Meteo failed: ' . $openMeteoError->getMessage());

            try {
                $payload = $this->fetchFromWttr($lat, $lng);
            } catch (Throwable $wttrError) {
                log_message('warning', 'wttr.in fallback failed: ' . $wttrError->getMessage());

                $stale = $cache->get($key . '_stale');
                if (is_array($stale) && isset($stale['current'])) {
                    $stale['stale'] = true;

                    return $stale;
                }

                throw new RuntimeException(
                    'Weather service unavailable.',
                    0,
                    $openMeteoError,
                );
            }
        }

        $cache->save($key, $payload, self::CACHE_TTL);
        $cache->save($key . '_stale', $payload, 86400);

        return $payload;
    }

    /**
     * @return array{current: array<string, mixed>, source: string}
     */
    /**
     * @return array{current: array<string, mixed>, source: string, description?: string}
     */
    private function fetchFromWttr(float $lat, float $lng): array
    {
        $url = sprintf('https://wttr.in/%.4f,%.4f?format=j1', $lat, $lng);

        /** @var CURLRequest $client */
        $client   = service('curlrequest');
        $response = $client->get($url, $this->httpOptions());

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('wttr.in HTTP ' . $response->getStatusCode());
        }

        $data = json_decode((string) $response->getBody(), true);
        $cc   = $data['current_condition'][0] ?? null;
        if (! is_array($cc)) {
            throw new RuntimeException('Invalid wttr.in response');
        }

        $desc = '';
        if (isset($cc['weatherDesc'][0]['value'])) {
            $desc = (string) $cc['weatherDesc'][0]['value'];
        }

        return [
            'current' => [
                'temperature_2m'       => (float) ($cc['temp_C'] ?? 0),
                'apparent_temperature' => (float) ($cc['FeelsLikeC'] ?? $cc['temp_C'] ?? 0),
                'relative_humidity_2m' => (int) ($cc['humidity'] ?? 0),
                'wind_speed_10m'       => (float) ($cc['windspeedKmph'] ?? 0),
                'weather_code'         => 2,
                'time'                 => date('Y-m-d\TH:i'),
            ],
            'source'      => 'wttr.in',
            'description' => $desc,
        ];
    }

    /**
     * @return array{current: array<string, mixed>, source: string}
     */
    private function fetchFromOpenMeteo(float $lat, float $lng): array
    {
        $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude'       => $lat,
            'longitude'      => $lng,
            'current'        => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
            'timezone'       => 'Asia/Manila',
            'wind_speed_unit'=> 'kmh',
        ]);

        /** @var CURLRequest $client */
        $client   = service('curlrequest');
        $response = $client->get($url, $this->httpOptions());

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        if ($status !== 200) {
            throw new RuntimeException('Open-Meteo HTTP ' . $status);
        }

        $data = json_decode($body, true);
        if (! is_array($data) || ! isset($data['current']) || ! is_array($data['current'])) {
            throw new RuntimeException('Invalid Open-Meteo response');
        }

        return [
            'current' => $data['current'],
            'source'  => 'open-meteo',
        ];
    }
}
