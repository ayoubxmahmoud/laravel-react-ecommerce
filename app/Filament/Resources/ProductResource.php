<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ProductImages;
use App\Filament\Resources\ProductResource\Pages\ProductVariations;
use App\Filament\Resources\ProductResource\Pages\ProductVariationTypes;
use App\Models\Product;
use App\ProductStatusEnum;
use App\RolesEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    /**
     * Customize the Eloquent query for retrieving products
     * This applies for vendor scope to ensure that only products
     * created by the authenticated vendor are retrieved.
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forVendor();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (string $operation, $state, callable $set) {
                                    $set('slug', Str::slug($state));
                                }
                            ),
                        TextInput::make('slug')->required(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->label(__('Department'))
                            ->preload()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(
                                function (callable $set) {
                                    $set('category_id', null);
                                }
                            ),
                        Select::make('category_id')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, callable $get) {
                                    $departmentId = $get('department_id');
                                    if ($departmentId) {
                                        $query->where('department_id', $departmentId);
                                    }
                                }
                            )->label(__('Category'))
                            ->preload()
                            ->searchable()
                            ->required(),
                        RichEditor::make('description')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('price')
                            ->required()
                            ->numeric(),
                        TextInput::make('quantity')
                            ->integer(),
                        Select::make('status')
                            ->options(ProductStatusEnum::labels())
                            ->default(ProductStatusEnum::Draft->value),
                        Section::make('SEO')
                            ->collapsible()
                            ->schema([
                                TextInput::make('meta_title'),
                                TextInput::make('meta_description')
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->collection('images')
                    ->limit(1)
                    ->conversion('thumb')
                    ->label('image'),
                TextColumn::make('title')
                    ->sortable()
                    ->words(10)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors(ProductStatusEnum::colors()),
                TextColumn::make('department.name'),
                TextColumn::make('category.name'),
                TextColumn::make('created_at')->dateTime()
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ProductStatusEnum::labels()),
                SelectFilter::make('department_id')
                    ->relationship('department', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {

        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' => Pages\ProductImages::route('/{record}/images'),
            'variation-types' => Pages\ProductVariationTypes::route('/{record}/variation-types'),
            'variations' => Pages\ProductVariations::route('/{record}/variations')
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditProduct::class,
            ProductImages::class,
            ProductVariationTypes::class,
            ProductVariations::class
        ]);
    }
    public static function canViewAny(): bool
    {
        /**
         * @var \App\Models\User $user
         */
        $user = Filament::auth()->user();
        return $user && $user->hasRole(RolesEnum::Vendor);
    }
}
