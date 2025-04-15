<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function scopeActivated(Builder $query)
    {
        return $query->where('active', true);
    }
}
