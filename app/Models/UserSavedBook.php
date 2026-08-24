<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserSavedBook extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'book_id', 'notes'];

    protected $table = 'user_saved_books';

    /**
     * UserSavedBook belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * UserSavedBook belongs to a Book.
     */
    public function book()
    {
        return $this->belongsTo(Books::class, 'book_id', 'id');
    }
}
