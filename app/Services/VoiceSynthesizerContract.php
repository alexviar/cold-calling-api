<?php

namespace App\Services;

interface VoiceSynthesizerContract
{
    public function synthesize(string $text): mixed;
}
