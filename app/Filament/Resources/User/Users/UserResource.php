<?php

namespace App\Filament\Resources\User\Users;

use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Emulator\Contracts\RankRepository;
use App\Emulator\Data\Feature;
use App\Emulator\Emulator;
use App\Filament\Concerns\TranslatableResource;
use App\Filament\Resources\User\Users\Pages\CreateUser;
use App\Filament\Resources\User\Users\Pages\EditUser;
use App\Filament\Resources\User\Users\Pages\ListUsers;
use App\Filament\Resources\User\Users\Pages\ViewUser;
use App\Filament\Tables\Columns\UserAvatarColumn;
use App\Models\Community\Teams\WebsiteTeam;
use App\Models\User;
use App\Rules\WebsiteWordfilterRule;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $slug = 'user-management/users';

    public static string $translateIdentifier = 'users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Main')
                    ->tabs([
                        Tab::make(__('filament::resources.tabs.General Information'))
                            ->schema([
                                // The emulator owns renames, so housekeeping only
                                // names an account while it is being created.
                                TextInput::make('username')
                                    ->label(__('filament::resources.inputs.username'))
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->unique(column: fn (): string => Emulator::playerSchema()->column('username'), ignoreRecord: true)
                                    ->rule(fn (): string => User::usernameFormatRule())
                                    ->rule(new WebsiteWordfilterRule)
                                    ->maxLength(fn (): int => Emulator::constraints()->usernameLength),

                                TextInput::make('motto')
                                    ->label(__('filament::resources.inputs.motto'))
                                    ->required()
                                    ->maxLength(fn (): int => Emulator::constraints()->mottoLength),

                                Select::make('gender')
                                    ->native(false)
                                    ->label(__('filament::resources.inputs.gender'))
                                    ->options([
                                        'M' => __('filament::resources.common.Male'),
                                        'F' => __('filament::resources.common.Female'),
                                    ])
                                    ->required(),

                                DateTimePicker::make('account_created')
                                    ->hiddenOn('create')
                                    ->native(false)
                                    ->displayFormat('Y-m-d H:i:s')
                                    ->dehydrateStateUsing(fn (User $record) => $record->account_created)
                                    ->disabled()
                                    ->label(__('filament::resources.inputs.created_at')),

                                DateTimePicker::make('last_login')
                                    ->hiddenOn('create')
                                    ->native(false)
                                    ->displayFormat('Y-m-d H:i:s')
                                    ->dehydrateStateUsing(fn (User $record) => $record->last_login)
                                    ->disabled()
                                    ->label(__('filament::resources.inputs.last_login')),

                                DateTimePicker::make('last_online')
                                    ->hiddenOn('create')
                                    ->native(false)
                                    ->displayFormat('Y-m-d H:i:s')
                                    ->dehydrateStateUsing(fn (User $record) => $record->last_online)
                                    ->disabled()
                                    ->label(__('filament::resources.inputs.last_online')),

                                TextInput::make('ip_register')
                                    ->hiddenOn('create')
                                    ->label(__('filament::resources.inputs.ip_register'))
                                    ->disabled(),

                                TextInput::make('ip_current')
                                    ->hiddenOn('create')
                                    ->label(__('filament::resources.inputs.ip_current'))
                                    ->disabled(),

                                TextInput::make('referral_code')
                                    ->hiddenOn('create')
                                    ->label(__('filament::resources.inputs.referral_code'))
                                    ->disabled(),

                                Select::make('team_id')
                                    ->native(false)
                                    ->label(__('filament::resources.inputs.team_id'))
                                    ->options(fn () => WebsiteTeam::query()->pluck('rank_name', 'id'))
                                    ->columnSpanFull(),
                            ])->columns(['sm' => 2]),

                        // A new account starts on the hotel's configured balances;
                        // the editor is where they are adjusted, with the emulator
                        // in the loop for an online player.
                        Tab::make(__('filament::resources.tabs.Currencies'))
                            ->hiddenOn('create')
                            ->schema([
                                TextInput::make('credits')
                                    ->label(__('filament::resources.common.Credits'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpanFull(),

                                TextInput::make('currency_0')
                                    ->label(__('filament::resources.common.Duckets'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpanFull(),

                                TextInput::make('currency_5')
                                    ->label(__('filament::resources.common.Diamonds'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpanFull(),

                                TextInput::make('currency_101')
                                    ->label(__('filament::resources.common.Points'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpanFull(),
                            ])
                            ->columns(['sm' => 2]),

                        Tab::make(__('filament::resources.tabs.Security'))
                            ->schema([
                                Section::make(__('filament::resources.tabs.Change Username'))
                                    ->description(__('filament::resources.helpers.change_username_description'))
                                    ->hiddenOn('create')
                                    ->schema([
                                        Toggle::make('allow_change_username')
                                            ->label(__('filament::resources.inputs.allow_change_username'))
                                            ->visible(fn (): bool => Emulator::supports(Feature::NameChangePermission)),
                                    ])->collapsible()->collapsed(),

                                Section::make(__('filament::resources.tabs.Change Email'))
                                    ->schema([
                                        TextInput::make('mail')
                                            ->label(__('filament::resources.inputs.email'))
                                            ->email()
                                            ->unique(column: fn (): string => Emulator::playerSchema()->column('mail'), ignoreRecord: true)
                                            ->maxLength(fn (): int => Emulator::constraints()->emailLength)
                                            ->required(),
                                    ])->collapsible()->collapsed(),

                                Section::make(__('filament::resources.tabs.Change Password'))
                                    ->description(__('filament::resources.helpers.change_password_description'))
                                    ->schema([
                                        TextInput::make('password')
                                            ->label(__('filament::resources.inputs.new_password'))
                                            // A new account goes through the registration
                                            // action, which hashes the password itself.
                                            ->dehydrateStateUsing(fn (string $operation, $state) => $operation === 'create' ? $state : Hash::make($state))
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->password()
                                            ->revealable()
                                            ->suffixAction(
                                                Action::make('generatePassword')
                                                    ->label(__('filament::resources.actions.generate_password'))
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->action(function (Set $set): void {
                                                        $password = self::generatedPassword();

                                                        $set('password', $password);
                                                        $set('password_confirmation', $password);
                                                    }),
                                            )
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->rule(Password::default())
                                            ->confirmed(),

                                        TextInput::make('password_confirmation')
                                            ->label(__('filament::resources.inputs.new_password_confirmation'))
                                            // The registration rules expect the
                                            // confirmation beside the password.
                                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->password()
                                            ->revealable(),
                                    ])->collapsible()
                                    // An account cannot be created without a password,
                                    // whoever is allowed to create one.
                                    ->visible(fn (string $operation): bool => $operation === 'create'
                                        || hasHousekeepingPermission('reset_user_password', Filament::auth()->user()))
                                    ->columns(['sm' => 2])
                                    ->collapsed(fn (string $operation): bool => $operation !== 'create'),

                                Section::make(__('filament::resources.tabs.Change Rank'))
                                    ->schema([
                                        Select::make('rank')
                                            ->native(false)
                                            ->label(__('filament::resources.inputs.rank'))
                                            ->options(function () {
                                                $actor = Filament::auth()->user();

                                                if (! $actor instanceof User) {
                                                    return [];
                                                }

                                                return app(RankRepository::class)->optionsBelow($actor->rank);
                                            })
                                            ->required(),

                                        Toggle::make('hidden_staff')
                                            ->label(__('filament::resources.inputs.is_hidden'))
                                            ->default(false),
                                    ])->collapsible()
                                    ->collapsed(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    /**
     * The listing shows every account, including the actor's own and those of
     * their peers. Which of them a staff member may open is decided here
     * instead: a record at or above their own rank stays unresolvable, so its
     * pages answer 404 rather than render a form the policy then refuses.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();
        $actor = Filament::auth()->user();

        if (! $actor instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('rank', '<', $actor->rank);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('filament::resources.columns.id'))
                    ->searchable(),

                UserAvatarColumn::make('avatar')
                    ->toggleable()
                    ->label(__('filament::resources.columns.avatar'))
                    ->options('&size=m&head_direction=3&gesture=sml&headonly=1'),

                TextColumn::make('username')
                    ->label(__('filament::resources.columns.username'))
                    ->searchable(),

                TextColumn::make('mail')
                    ->label(__('filament::resources.columns.email'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),

                TextColumn::make('motto')
                    ->label(__('filament::resources.columns.motto'))
                    ->toggleable()
                    ->limit(30)
                    ->searchable(),

                IconColumn::make('online')
                    ->label(__('filament::resources.columns.online'))
                    ->icon(fn (User $record) => $record->online ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->colors([
                        'danger' => false,
                        'success' => true,
                    ]),

                TextColumn::make('account_created')
                    ->toggleable()
                    ->date('Y-m-d H:i')
                    ->label(__('filament::resources.columns.created_at')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return Emulator::userRelationManagers();
    }

    /**
     * A password the application's policy accepts: Str::password() always
     * mixes in a number and a symbol, while the loop covers the upper and
     * lower case the policy asks for as well.
     */
    private static function generatedPassword(): string
    {
        do {
            $password = Str::password(20);
        } while (preg_match('/(?=.*[a-z])(?=.*[A-Z])/', $password) !== 1);

        return $password;
    }

    /**
     * @param  array<string, mixed>  $formData
     *
     * @return array<string, mixed>
     */
    public static function fillWithOutsideData(User $record, array $formData): array
    {
        $formData['currency_0'] = $record->currency('duckets');
        $formData['currency_5'] = $record->currency('diamonds');
        $formData['currency_101'] = $record->currency('points');

        if (Emulator::supports(Feature::NameChangePermission)) {
            $formData['allow_change_username'] = app(PlayerSettingsRepository::class)->canChangeName($record);
        }

        return $formData;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
