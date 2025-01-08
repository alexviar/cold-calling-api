<?php

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/telnyx/webhook', function (Request $request) {
    logger("telnyx webhook", $request->all());
})->withoutMiddleware('web');

Route::get('/call', function () {
    $apiKey = config('services.telnyx.key');
    $connId = config('services.telnyx.connection_id');
    // $response = Http::baseUrl('https://api.telnyx.com/v2')
    //     ->withHeader('Authorization', "Bearer $apiKey")
    //     ->acceptJson()
    //     ->asJson()
    //     ->post('calls', [
    //         'connection_id' => env('TELNYX_CONNECTION_ID', $connId),
    //         'to' => '+5215527399115',
    //         'from' => '+16413001752',
    //         'stream_url' => 'wss://e325-190-180-69-197.ngrok-free.app/app/c4paajozb5fvobrdyx3z',
    //         // 'stream_track' => 'both_tracks',
    //     ]);

    $response = Http::baseUrl('https://api.telnyx.com/v2')
        ->withHeader('Authorization', "Bearer $apiKey")
        ->acceptJson()
        ->get("call_events");

    logger("Telnyx response", [
        $response->status(),
        $response->json()
    ]);

    return $response->json();
});

Route::get('benchmark', function () {
    $audioData = Storage::get('audio.wav');
    $startTime = microtime(true);

    $sttResponse = Http::baseUrl("https://eastus.api.cognitive.microsoft.com/")
        ->attach(
            'audio',
            $audioData,
            'audio.wav'
        )
        ->withHeader("Ocp-Apim-Subscription-Key", config('services.azure.key'))
        ->withQueryParameters([
            'api-version' => '2024-11-15'
        ])
        ->post('/speechtotext/transcriptions:transcribe');

    $currenTime = microtime(true);
    $sttTime = $currenTime - $startTime;


    $text = Arr::get($sttResponse->json(), 'combinedPhrases.0.text');

    $pdfData = Storage::get('pdfData.txt');


    $startTime = microtime(true);

    $genAiResponse = Http::baseUrl("https://generativelanguage.googleapis.com/")
        ->withQueryParameters([
            'key' => config('services.gemini.key')
        ])
        ->post('/v1beta/models/gemini-1.5-flash:generateContent', [
            "system_instruction" => [
                "parts" => [
                    "text" => "Sos un funcionario del gobierno encargado de socializar este decreto supremo con las personas que se van a comunicar contingo, no me tenes que responder a mi, sino a ellos. Se breve en tus respuestas y solo responde a lo que te pregunten, no te anticipes demasiado."
                ]
            ],
            "contents" => [
                [
                    "parts" => [
                        // [
                        //     "inline_data" => [
                        //         "mime_type" => "application/pdf",
                        //         "data" => $pdfData
                        //     ]
                        // ],
                        [
                            "text" => $text
                        ]
                    ]
                ]
            ]
        ]);

    $currenTime = microtime(true);
    $geminiTime = $currenTime - $startTime;

    $generatedText = Arr::get($genAiResponse->json(), 'candidates.0.content.parts.0.text');

    $startTime = microtime(true);

    $ttsResponse =  Http::baseUrl("https://eastus.tts.speech.microsoft.com/cognitiveservices/v1")
        ->withHeader("Ocp-Apim-Subscription-Key", config('services.azure.key'))
        ->withHeader("Content-Type", 'application/ssml+xml')
        ->withHeader("X-Microsoft-OutputFormat", 'riff-24khz-16bit-mono-pcm')
        ->withBody(<<<XML
        <speak version='1.0' xml:lang='es-MX'><voice xml:lang='es-MX' xml:gender='Female'
    name='en-US-JennyMultilingualNeural'>
        {$generatedText}
</voice></speak>
XML, "application/ssml+xml")
        ->post('/');

    $currenTime = microtime(true);
    $ttsTime = $currenTime - $startTime;

    $genAudio = $ttsResponse->body();

    logger('Tiempo de ejecución', compact('sttTime', 'geminiTime', 'ttsTime', 'text', 'generatedText'));

    return response($genAudio, 200)
        ->header('Content-Type', 'audio/wav');
})->withoutMiddleware('web');
