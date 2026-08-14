<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlaylistBook extends Pivot
{
    use HasUuids;

    protected $table = 'playlist_books';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'playlist_id',
        'book_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, 'playlist_id', 'id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Books::class, 'book_id', 'id');
    }
}
