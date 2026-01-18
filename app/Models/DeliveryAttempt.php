<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'digest_run_id',
        'destination_id',
        'status',
        'sent_at',
        'provider_message_id',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DigestRun, $this>
     */
    public function digestRun(): BelongsTo
    {
        return $this->belongsTo(DigestRun::class);
    }

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
