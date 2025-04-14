<?php

namespace App\Models;

use App\ProductStatusEnum;
use App\VendorStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100);

        $this->addMediaConversion('small')
            ->width(480);

        $this->addMediaConversion('large')
            ->width(1200);
    }

    public function scopeForVendor(Builder $query): Builder
    {
        return $query->where('created_by', Auth::user()->id);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('products.status', ProductStatusEnum::Published);
    }

    public function scopeForWebsite(Builder $query): Builder
    {
        return $query->published()->vendorApproved();
    }

    public function scopeVendorApproved(Builder $query)
    {
        return $query->join('vendors', 'vendors.user_id', '=', 'products.created_by')
                ->where('vendors.status', VendorStatusEnum::Approved->value);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function getPriceForOptions($optionIds = [])
    {
        $optionIds = array_values($optionIds);
        sort($optionIds);

        foreach ($this->variations as $variation) {
            $a = $variation->variation_type_option_ids;
            sort($a);
            if ($optionIds === $a) {
                return $variation->price !== null ? $variation->price : $this->price;
            }
        }
        return $this->price;
    }
    public function getFirstOptionsPrice(): float
    {
        $firstOptions = $this->getFirstOptionsMap();

        if ($firstOptions){
            return $this->getPriceForOptions($firstOptions);
        }
        return $this->price;
    }

    public function getImageForOptions($optionIds = null)
    {
        if ($optionIds) {
            $optionIds = array_values($optionIds);
            sort($optionIds);
            $options = VariationTypeOption::whereIn('id', $optionIds)->get();

            foreach ($options as $option) {
                $image = $option->getFirstMediaUrl('images', 'small');
                if ($image) {
                    return $image;
                }
            }
        }
        return $this->getFirstMediaUrl('images', 'small');
    }

    public function options()
    {
        return $this->hasManyThrough(
            VariationTypeOption::class, // Target Model
            VariationType::class, // intermediate table
            'product_id', // Foreign key VariationType table
            'variation_type_id', // Foreign key on Option table
            'id', // Local key on Product table
            'id', // Local key on VariationType table
        );
    }

    public function getFirstImageUrl($collectioName = "images", $conversion = "small")
    {
        if ($this->options->count() > 0) {
            foreach ($this->options as $option) {
                $imageUrl = $option->getFirstMediaUrl($collectioName, $conversion);
                if ($imageUrl) {
                    return $imageUrl;
                }
            }
        }
        return $this->getFirstMediaUrl($collectioName, $conversion);
    }

    public function getImages()
    {
        if ($this->options->count() > 0) {
            foreach ($this->options as $option) {
                /**
                 * @var VariationTypeOption $option
                 */
                $images = $option->getMedia('images');
                if ($images) {
                    return $images;
                }
            }
        }
        return $this->getMedia('images');
    }

    public function getFirstOptionsMap()
    {
        return $this->variationTypes
                ->mapWithKeys(fn($type) => [$type->id => $type->options[0]?->id])
                ->toArray();
    }

}
