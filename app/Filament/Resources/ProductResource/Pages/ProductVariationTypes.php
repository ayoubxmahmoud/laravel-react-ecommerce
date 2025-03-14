<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\ProductVariationTypesEnum;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class ProductVariationTypes extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected static ?string $title = 'Variation Types';
    protected static ?string $navigationIcon = 'heroicon-m-numbered-list';
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form {
        return $form
            ->schema([
                // Repeater for managing multiple variation type linked to a product
                Repeater::make('variationTypes')
                    ->label(false)
                    ->relationship() // use 'variationTypes' relationship defined in Product model
                    ->collapsible()
                    ->defaultItems(1)
                    ->addActionLabel('Add new variation type')
                    ->columns(2)
                    ->columnSpan(2) // Spans across 2 columns in the parent layout 
                    ->schema([
                        // input for variation type name(e.g Color, Size)
                        TextInput::make('name')
                            ->required(),
                        // Dropdown for selecting the type of the variation
                        Select::make('type')
                            ->options(ProductVariationTypesEnum::labels()),
                        // Nested repeater for defining variation type options
                        Repeater::make('options')
                            ->relationship() // using the options relatioship defined in the VariationType
                            ->collapsible()
                            ->schema([
                                // Input for option name
                                TextInput::make('name')
                                    ->columnSpan(2)
                                    ->required(),
                                // File upload for option images
                                SpatieMediaLibraryFileUpload::make('images')
                                    ->image()
                                    ->multiple()
                                    ->openable()
                                    ->panelLayout('grid')
                                    ->collection('images') // Assigns images to a specific media collection
                                    ->reorderable()
                                    ->appendFiles()
                                    ->preserveFilenames()
                                    ->columnSpan(3)
                            ])->columnSpan(2)

                    ])
            ]);
    }
}
