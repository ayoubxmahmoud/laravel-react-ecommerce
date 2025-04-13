<?php

namespace App\Models;

use App\VendorStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $primaryKey = 'user_id';

    public function scopeEligibleForPayout(Builder $query): Builder
    {
        return $query->where('status', VendorStatusEnum::Approved);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }    
}
