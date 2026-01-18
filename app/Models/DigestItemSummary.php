<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigestItemSummary extends Model
{
    /** @use HasFactory<\Database\Factories\DigestItemSummaryFactory> */
    use HasFactory;

    protected $fillable = [
        'digest_id',
        'source_item_id',
        'summary_markdown',
        'summary_json',
        'provider',
        'model',
        'status',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
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
     * @return BelongsTo<SourceItem, $this>
     */
    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(SourceItem::class);
    }
}
