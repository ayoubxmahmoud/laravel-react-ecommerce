<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
