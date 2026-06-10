<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'parent_id',
        'image_id',
        'banner_image_id',
    ];

    public function books()
    {
        return $this->hasMany(Books::class, 'category_id', 'id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id', 'id');
    }

    public function bannerImage()
    {
        return $this->belongsTo(Image::class, 'banner_image_id', 'id');
    }
}
