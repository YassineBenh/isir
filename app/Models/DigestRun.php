<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigestRun extends Model
{
    /** @use HasFactory<\Database\Factories\DigestRunFactory> */
    use HasFactory;

    protected $fillable = [
        'digest_id',
        'period_start_at',
        'period_end_at',
        'status',
        'rendered_content',
        'started_at',
        'finished_at',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start_at' => 'datetime',
            'period_end_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Digest, $this>
     */
    public function digest(): BelongsTo
    {
        return $this->belongsTo(Digest::class);
    }

    /**
     * @return BelongsToMany<SourceItem, $this>
     */
    public function sourceItems(): BelongsToMany
    {
        return $this->belongsToMany(SourceItem::class)->withPivot('position')->withTimestamps();
    }

    /**
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }
}
