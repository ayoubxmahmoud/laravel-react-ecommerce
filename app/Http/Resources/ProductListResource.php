<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public static $wrap = false;

    /**
     * Transform the product resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'price' => $this->getFirstOptionsPrice(),
            'quantity' => $this->quantity,
            'image' => $this->getFirstImageUrl(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'store_name' => $this->user->vendor->store_name
            ],
            // Includes the department details where the product belongs
            'department' => [
                'id' => $this->department->id,
                'name' => $this->department->name
            ]
            ];
    }
}
