<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\ProductVariationTypesEnum;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;

class ProductVariations extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected static ?string $title = 'Variations';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        $types = $this->record->variationTypes;
        $fields = [];
        foreach ($types as $type) {
            $fields[] = TextInput::make('variation_type_'.$type->id.'.id')
                        ->hidden();
            $fields[] = TextInput::make('variation_type_'.$type->id.'.name')
                        ->label($type->name);
        }
        return $form
            ->schema([
                Repeater::make('variations')
                        ->collapsible()
                        ->label(false)
                        ->addable(false)
                        ->defaultItems(1)
                        ->schema([
                            Section::make()
                                ->schema($fields)
                                ->columns(3),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric(),
                            TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                        ])
                        ->columns(2)
                        ->columnSpan(2)
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Check if the record exists before accessing its properties
        if (!$this->record) {
            return $data; // Prevents null errors
        }
        // Retrieve variations from the record and convert them to an array
        $variations = $this->record->variations ? $this->record->variations->toArray() : [];
        // Merge the existing variations withe the variation types
        $data['variations'] = $this->mergeCartesianWithExisting($this->record->variationTypes, $variations);
        // return the modified data array
        return $data;
    }
    

    private function mergeCartesianWithExisting($variationTypes, $existingData)
    {
        $defaultQuantity = $this->record->quantity;
        $defaultPrice = $this->record->price;
        // Generate all the possible variation combinations for the main product record
        $cartesianProduct = $this->cartesianProduct($variationTypes, $defaultQuantity, $defaultPrice);

        $mergedResult = [];

        foreach ($cartesianProduct as $product) {
            // Extract option IDs from the current product combination as an array
            $optionIds = collect($product)
                ->filter(fn($value, $key) => str_starts_with($key, 'variation_type_'))
                ->map(fn($option) => $option['id'])
                ->values()
                ->toArray();

            // Find a matching_entry in existing data based on the optionIds
            $match = array_filter($existingData, function ($existingOption) use ($optionIds) {
                return $existingOption['variation_type_option_ids'] === $optionIds;            
            });

            if (!empty($match)) {
                // If the match is found, use the existing entry's data
                $existingEntry = reset($match);// Get the first matched entry
                $product['id'] = $existingEntry['id'];
                $product['quantity'] = $existingEntry['quantity'];
                $product['price'] = $existingEntry['price'];
            } else {
                // if no match then set the product quantity and price to default
                $product['quantity'] = $defaultQuantity;
                $product['price'] = $defaultPrice;
            }
            // Add the proccessed product to the merged result array
            $mergedResult[] = $product;
        }
        return $mergedResult;
    }

    private function cartesianProduct($variationTypes, $defaultQuantit = null, $defaultPrice = null)
    {
        $result = [[]];

        foreach ($variationTypes as $index => $variationType) {
            $temp = [];

            foreach ($variationType->options as $option) {
                // Add the current option to all existing combination
                foreach ($result as $combination) {
                    $newCombination = $combination + [
                            'variation_type_' . $variationType->id => [
                            'id' => $option->id,
                            'name' => $option->name,
                            'label' => $variationType->name
                        ],
                    ];
                    $temp[] = $newCombination;// Push the new combination in temp array
                }
            }
            $result = $temp; // Update the result with the new combinations.(copy the temp data into $result)
        }
        // Add quantity and price to completed combination
        foreach ($result as $combination) {
            if (count($combination) === count($variationTypes)) {
                $combination['quantity'] = $defaultQuantit;
                $combination['price'] = $defaultPrice;
            }
        }
        return $result;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Initialize an array to hold the formatted data
        $formattedData = [];

        // loop through each variation to structure it
        foreach ($data['variations'] as $variation) {
            $variationTypeOptionIds = [];
            foreach ($this->record->variationTypes as $i => $type) {
                $variationTypeOptionIds[] = $variation['variation_type_'.$type->id]['id'];
            }
            $quantity = $variation['quantity'];
            $price = $variation['price'];

            // Prepare the data structure for the database
            $formattedData[] = [
                'id' => $variation['id'],
                'variation_type_option_ids' => $variationTypeOptionIds,
                'quantity' => $quantity,
                'price' => $price
            ];
        }
        $data['variations'] = $formattedData;
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract variations data from the request and remove it from the main data array
        $variations = $data['variations'];
        unset($data['variations']); // Remove variations to prevent accidental on the Product model 

        // update the record with remaining data excluding the variations
        $record->update($data);
        $record->variations()->delete(); // Delete old variations and replace them with new ones
        $record->variations()->upsert($variations, ['id'], ['variation_type_option_ids', 'quantity', 'price']);

        return $record;
    }
}
