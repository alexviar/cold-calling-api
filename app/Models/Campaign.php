<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    const PENDING_STATUS = 1;
    const ACTIVE_STATUS = 2;

    protected $fillable = [
        'name',
        'prompt',
        'greeting',
        'greeting_audio_path',
        'file_path',
        'phone_numbers',
        'start_date',
        'closed_at'
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'start_date' => 'datetime',
        'closed_at' => 'datetime'
    ];

    protected $appends = [
        'status_text'
    ];

    #region Attributes

    public function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                1 => 'Pendiente',
                2 => 'Activa',
                3 => 'Pausada',
                4 => 'Terminada',
                default => '-'
            }
        );
    }

    public function information(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->file_path ? base64_encode(Storage::disk('public')->get($this->file_path)) : '',
        );
    }

    public function greetingAudioContent(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->file_path ? base64_encode(Storage::disk('public')->get($this->greeting_audio_path)) : '',
        );
    }
    #endregion

    public function calls(): HasMany
    {
        return $this->hasMany(CampaignCall::class);
    }
}
