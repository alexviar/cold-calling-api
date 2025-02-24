<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    protected function transaction(Closure $callback, Closure $rollback)
    {
        try {
            return DB::transaction($callback);
        } catch (\Throwable $t) {
            $rollback();
            throw $t;
        }
    }
}
