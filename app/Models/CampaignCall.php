<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignCall extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignCallFactory> */
    use HasFactory;

    const STATUS_PENDING = 1;
    const STATUS_CALLING = 2;
    const STATUS_DONE = 3;
    const STATUS_FAILED = 4;

    protected $attributes = [
        'status' => 1
    ];

    protected $fillable = [
        'phone_number',
        'status',
        'call_control_id',
        // 'contacted_at',
        // 'conversation_excerpt',
        // 'recording_path',
        // 'interest_level',
        'telnyx_data',
        'campaign_id'
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
                2 => 'En curso',
                3 => 'Finalizada',
                4 => 'Fallida'
            }
        );
    }

    #endregion

    #region Relations

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    #endregion

    public function casts(): array
    {
        return [
            'telnyx_data' => 'array'
        ];
    }
}
