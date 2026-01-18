<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** @use HasFactory<\Database\Factories\SourceFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'canonical_key',
        'name',
        'url',
        'config',
        'is_enabled',
        'last_fetched_at',
        'fetch_state',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'fetch_state' => 'array',
            'is_enabled' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SourceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SourceItem::class);
    }

    /**
     * @return BelongsToMany<Digest, $this>
     */
    public function digests(): BelongsToMany
    {
        return $this->belongsToMany(Digest::class)->withTimestamps();
    }
}
