<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Digest extends Model
{
    /** @use HasFactory<\Database\Factories\DigestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'frequency',
        'timezone',
        'send_time',
        'send_day_of_week',
        'is_enabled',
        'last_successful_run_at',
        'last_dispatched_at',
        'ai_enabled',
        'include_versions_summary',
        'ai_prefs',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'include_versions_summary' => 'boolean',
            'ai_prefs' => 'array',
            'last_successful_run_at' => 'datetime',
            'last_dispatched_at' => 'datetime',
            'send_day_of_week' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Source, $this>
     */
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Destination, $this>
     */
    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class, 'digest_destination')->withTimestamps();
    }

    /**
     * @return HasMany<DigestRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(DigestRun::class);
    }
}
