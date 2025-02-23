<?php

namespace App\Services;

interface CallServiceContract
{
    public function call(string $phoneNumber, $data = []): string;
}
