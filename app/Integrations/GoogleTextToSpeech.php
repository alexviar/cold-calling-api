<?php

namespace App\Integrations;

use App\Services\VoiceSynthesizerContract;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

class GoogleTextToSpeech implements VoiceSynthesizerContract
{
    public function synthesize(string $text): string
    {
        $textToSpeechClient = new TextToSpeechClient();

        $input = new SynthesisInput();
        $input->setText($text);
        $voice = new VoiceSelectionParams();
        $voice->setLanguageCode('es-US');
        $voice->setName('es-US-Chirp-HD-F');
        $audioConfig = new AudioConfig();
        $audioConfig->setAudioEncoding(AudioEncoding::MP3);

        $request = new SynthesizeSpeechRequest();
        $request->setInput($input);
        $request->setVoice($voice);
        $request->setAudioConfig($audioConfig);

        $response = $textToSpeechClient->synthesizeSpeech($request);
        return $response->getAudioContent();
    }
}
