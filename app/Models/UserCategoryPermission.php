<?php

namespace App\Models;

use App\Models\Category;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserCategoryPermission extends Model
{
    use HasUuids;

    protected $table = 'user_category_permissions';

    protected $fillable = [
        'user_id',
        'category_id',
        'permission_id',
    ];


    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }
}
