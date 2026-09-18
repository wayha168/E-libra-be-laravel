<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    /** @use HasFactory<\Database\Factories\AuthorFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'created_by',
        'image_id',
        'bio',
        'website',
        'facebook',
        'instagram',
        'twitter',
        'tiktok',
        'youtube',
        'telegram',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** The staff/admin user who created this author record. */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id', 'id');
    }

    public function books()
    {
        return $this->hasMany(Books::class, 'author_id', 'id');
    }
}
