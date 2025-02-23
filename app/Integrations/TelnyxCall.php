<?php

namespace App\Integrations;

use App\Services\CallServiceContract;
use Illuminate\Support\Facades\Http;

class TelnyxCall implements CallServiceContract
{
    public function call(string $phoneNumber, $data = []): string
    {
        $payload = [
            'to'            => '+' . $phoneNumber,
            'from'          => config('services.telnyx.from_number'),
            'connection_id' => config('services.telnyx.connection_id'),
            'stream_url'    => config('services.telnyx.websocket_url') . '/?' . http_build_query($data),
            // 'stream_track' => 'both_tracks',
        ];
        // Realizar la solicitud a Telnyx
        $apiKey = config('services.telnyx.key');
        $response = Http::baseUrl('https://api.telnyx.com/v2')
            ->withHeader('Authorization', "Bearer $apiKey")
            ->acceptJson()
            ->asJson()
            ->post('calls', $payload);

        logger("Telnyx response", [
            $response->status(),
            $response->json()
        ]);

        if ($response->successful()) {
            logger("Llamada iniciada para el teléfono: {$phoneNumber}");

            return $response->json('data.call_control_id');
        }
        logger("No se pudo iniciar la llamada para el teléfono: {$phoneNumber}");

        return null;
    }
}
