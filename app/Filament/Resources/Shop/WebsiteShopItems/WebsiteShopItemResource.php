<?php

namespace App\Filament\Resources\Shop\WebsiteShopItems;

use App\Enums\ShopItemType;
use App\Filament\Concerns\TranslatableResource;
use App\Models\Shop\WebsiteShopItem;
use App\Support\PermissionRanks;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class WebsiteShopItemResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = WebsiteShopItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'shop/items';

    public static string $translateIdentifier = 'shop-items';

    /** @return array<int, Component> */
    public static function getFormFields(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Select::make('type')
                ->options([
                    'currency' => 'Currency',
                    'furniture' => 'Furniture',
                    'badge' => 'Badge',
                    'rank' => 'Rank',
                ])
                ->required()
                ->native(false),

            TextInput::make('type_value')
                ->required()
                ->maxLength(255)
                ->rule(
                    fn (): In => Rule::in(array_keys(PermissionRanks::grantableRanks())),
                    fn (Get $get): bool => $get('type') === ShopItemType::Rank->value,
                )
                ->validationMessages(['in' => 'A rank item must use an existing rank below the staff rank.'])
                ->helperText('Currency: type:amount (e.g. credits:100). Furniture: item_id. Badge: badge_code. Rank: rank_id below the staff rank.'),

            FileUpload::make('image')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                ->directory('shop-items')
                ->visibility('public'),

            Toggle::make('is_active')
                ->default(true),
        ];
    }

    /** @return array<int, Column> */
    public static function getTableColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('image')
                ->square()
                ->size(40),

            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->sortable(),

            Tables\Columns\TextColumn::make('type_value')
                ->label('Value'),

            Tables\Columns\IconColumn::make('is_active')
                ->boolean()
                ->sortable(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema(static::getFormFields())
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(static::getTableColumns())
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Items')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'currency' => 'Currency',
                        'furniture' => 'Furniture',
                        'badge' => 'Badge',
                        'rank' => 'Rank',
                    ]),
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebsiteShopItems::route('/'),
            'create' => Pages\CreateWebsiteShopItem::route('/create'),
            'edit' => Pages\EditWebsiteShopItem::route('/{record}/edit'),
        ];
    }
}
