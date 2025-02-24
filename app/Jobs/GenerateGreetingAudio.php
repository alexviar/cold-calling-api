<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\VoiceSynthesizerContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateGreetingAudio implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Campaign $campaign) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $greeting = $this->campaign->greeting;
        $voiceSynthesizer = resolve(VoiceSynthesizerContract::class);
        $audioContent = $voiceSynthesizer->synthesize($greeting);

        $greetingAudioPath = 'campaign_files/' . Str::random(40) . '.mp3';
        Storage::disk('public')->put($greetingAudioPath, $audioContent);

        $this->campaign->greeting_audio_path = $greetingAudioPath;
        $this->campaign->save();
    }
}
