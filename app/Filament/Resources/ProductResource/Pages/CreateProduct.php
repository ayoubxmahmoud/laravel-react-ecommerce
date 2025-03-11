<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /**
         * @var \App\Models\User $user
         */
        $user = Auth::user();
        $data['created_by'] = $user?->id;
        $data['updated_by'] = $user?->id;
        return $data;
    }
}
