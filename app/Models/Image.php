<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Author;
use App\Models\User;
use App\Models\Books;
use App\Models\Category;


class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    protected $fillable = [
        'url',
        'alt_text',
        'image_type',
    ];

    public function author()
    {
        return $this->hasOne(Author::class, 'image_id', 'id');
    }

    public function userProfile()
    {
        return $this->hasMany(User::class, 'profile_image_id', 'id');
    }

    public function category()
    {
        return $this->hasMany(Category::class, 'image_id', 'id');
    }

    public function categoryBanner()
    {
        return $this->hasMany(Category::class, 'banner_image_id', 'id');
    }

    public function books()
    {
        return $this->hasMany(Books::class, 'image_id', 'id');
    }
}
