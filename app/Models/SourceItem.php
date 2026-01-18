<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceItem extends Model
{
    /** @use HasFactory<\Database\Factories\SourceItemFactory> */
    use HasFactory;

    protected $fillable = [
        'source_id',
        'external_id',
        'title',
        'url',
        'published_at',
        'raw_content',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return HasMany<DigestItemSummary, $this>
     */
    public function summaries(): HasMany
    {
        return $this->hasMany(DigestItemSummary::class);
    }

    /**
     * @return BelongsToMany<DigestRun, $this>
     */
    public function digestRuns(): BelongsToMany
    {
        return $this->belongsToMany(DigestRun::class)->withPivot('position')->withTimestamps();
    }
}
