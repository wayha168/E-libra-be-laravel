<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Books extends Model
{
    /** @use HasFactory<\Database\Factories\BooksFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'author_id',
        'category_id',
        'image_id',
        'public_date',
        'price',
        'pdf_file',
        'pdf_preview_path',
    ];

    protected $hidden = [
        'pdf_file',
        'pdf_preview_path',
    ];

    protected $casts = [
        'price' => 'float',
        'public_date' => 'date',
    ];

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id', 'id');
    }

    public function images()
    {
        return $this->belongsToMany(Image::class, 'book_images', 'book_id', 'image_id')
            ->withPivot(['sort_order'])
            ->orderBy('book_images.sort_order')
            ->withTimestamps();
    }

    public function galleryImages()
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            return $this->images;
        }

        if ($this->image) {
            return collect([$this->image]);
        }

        return collect();
    }

    public function likes()
    {
        return $this->hasMany(BookLike::class, 'book_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(BookComment::class, 'book_id', 'id');
    }

    public function purchases()
    {
        return $this->hasMany(UserBuyBook::class, 'book_id', 'id');
    }

    public function paidPurchases()
    {
        return $this->hasMany(UserBuyBook::class, 'book_id', 'id')->where('status', 'paid');
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'book_id', 'id');
    }
}
