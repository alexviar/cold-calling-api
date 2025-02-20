<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function casts(): array
    {
        return [
            'telnyx_data' => 'array'
        ];
    }
}
