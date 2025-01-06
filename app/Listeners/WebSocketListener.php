<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Reverb\Events\MessageReceived;

class WebSocketListener
{
    const START_EVENT = 'start';
    const MEDIA_EVENT = 'media';
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $connection = $event->connection;
        $message = json_decode($event->message, true);

        switch ($message['event']) {
            case self::START_EVENT:
                break;
            case self::MEDIA_EVENT:
                $this->processMedia($connection, $message);
                break;
        }
        // $event->connection->send(json_encode([
        //     'event' => 'media',
        //     'media' => [
        //         'payload' => ''
        //     ],
        // ]));
        logger("Telnyx event received", json_decode($event->message, true));
    }

    private function clearMessage($connection)
    {
        $connection->send(json_encode([
            'event' => 'clear',
        ]));
    }

    private function processMedia($connection, $data)
    {
        // Http::baseUrl("https://eastus.api.cognitive.microsoft.com/")
        //     ->attach(
        //         'audio',
        //         base64_decode(Arr::get($event, 'media.payload'))
        //     )
        //     ->withHeader("Ocp-Apim-Subscription-Key", config('services.azure.key'))
        //     ->withQueryParameters([
        //         'api-version' => '2024-11-15'
        //     ])
        //     ->post('/speechtotext/transcriptions:transcribe');

        // $event->connection->send(json_encode([
        //     'event' => 'media',
        //     'media' => [
        //         'payload' => ''
        //     ],
        // ]));

        $rawData = base64_decode($data['media']['payload']);
        $rawData = $this->decodeG711uLaw($rawData);
        $chunk = $data['media']['chunk'];
        $sampleRate = 8000; // Frecuencia de muestreo de Telnyx
        $bitsPerSample = 16;
        $channels = 1;

        $header = pack('N', 0x52494646) . // RIFF
            pack('V', 36 + strlen($rawData)) . // Tamaño total
            pack('N', 0x57415645) . // WAVE
            pack('N', 0x666d7420) . // fmt 
            pack('V', 16) . // Tamaño del bloque fmt
            pack('v', 1) . // Formato PCM
            pack('v', $channels) . // Canales
            pack('V', $sampleRate) . // Frecuencia de muestreo
            pack('V', $sampleRate * $channels * $bitsPerSample / 8) . // ByteRate
            pack('v', $channels * $bitsPerSample / 8) . // BlockAlign
            pack('v', $bitsPerSample) . // BitsPerSample
            pack('N', 0x64617461) . // data
            pack('V', strlen($rawData)); // Tamaño de los datos

        Storage::put("audio/chunk_$chunk.wav", $header . $rawData);
    }

    private function decodeG711uLaw($data)
    {
        $decoded = '';
        foreach (str_split($data) as $byte) {
            $decoded .= $this->ulawDecode(ord($byte));
        }
        return $decoded;
    }

    private function ulawDecode($byte)
    {
        $byte = ~$byte;
        $sign = ($byte & 0x80) ? -1 : 1;
        $exponent = ($byte >> 4) & 0x07;
        $mantissa = $byte & 0x0F;
        $sample = $sign * ((1 << ($exponent + 3)) + ($mantissa << ($exponent + 3)) + (1 << ($exponent + 2)));
        return pack('s', $sample); // Devuelve un valor PCM lineal de 16 bits
    }
}
