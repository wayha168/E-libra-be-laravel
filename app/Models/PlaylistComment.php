<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlaylistComment extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'playlist_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function playlist()
    {
        return $this->belongsTo(Playlist::class, 'playlist_id', 'id');
    }
}
