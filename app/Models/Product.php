<?php

namespace App\Models;

use App\Filament\Resources\ProductResource\Pages\ProductVariations;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'variations' => 'array'
    ];
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100);

        $this->addMediaConversion('small')
            ->width(480);

        $this->addMediaConversion('large')
            ->width(1200);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variationTypes()
    {
        return $this->hasMany(VariationType::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class, 'product_id');
    }
}
