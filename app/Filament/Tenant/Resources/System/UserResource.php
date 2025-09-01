<?php

namespace App\Filament\Tenant\Resources\System;

use App\Enums\ProfileInfos\EducationalLevelEnum;
use App\Enums\ProfileInfos\GenderEnum;
use App\Enums\ProfileInfos\MaritalStatusEnum;
use App\Enums\ProfileInfos\UfEnum;
use App\Enums\ProfileInfos\UserStatusEnum;
use App\Filament\Resources\Polymorphics\RelationManagers\MediaRelationManager;
use App\Filament\Tenant\Resources\System\UserResource\Pages;
use App\Filament\Tenant\Resources\System\UserResource\RelationManagers;
use App\Models\System\User;
use App\Services\Polymorphics\AddressService;
use App\Services\System\RoleService;
use App\Services\System\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('Infos. Gerais e Acesso'))
                            ->schema([
                                static::getGeneralInfosFormSection(),
                                static::getSystemAccessFormSection(),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('Infos. Complementares e Endereço'))
                            ->schema([
                                static::getAdditionalInfosFormSection(),
                                static::getAddressFormSection(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function getGeneralInfosFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Infos. Gerais'))
            ->description(__('Visão geral e informações fundamentais sobre o usuário.'))
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->confirmed()
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn(Set $set, mixed $state): ?string =>
                        $set('email_confirmation', $state)
                    )
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('additional_emails')
                    ->label(__('Email(s) adicional(is)'))
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            // ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Tipo de email'))
                            ->helperText(__('Nome identificador. Ex: Pessoal, Trabalho...'))
                            ->minLength(2)
                            ->maxLength(255)
                            ->datalist([
                                'Pessoal',
                                'Trabalho',
                                'Outros'
                            ])
                            ->autocomplete(false),
                    ])
                    ->itemLabel(
                        fn(mixed $state): ?string =>
                        $state['email'] ?? null
                    )
                    ->addActionLabel(__('Adicionar email'))
                    ->defaultItems(0)
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->collapseAllAction(
                        fn(Forms\Components\Actions\Action $action) =>
                        $action->label(__('Minimizar todos'))
                    )
                    ->deleteAction(
                        fn(Forms\Components\Actions\Action $action) =>
                        $action->requiresConfirmation()
                    )
                    ->columnSpanFull()
                    ->columns(2),
                Forms\Components\Repeater::make('phones')
                    ->label(__('Telefone(s) de contato'))
                    ->schema([
                        Forms\Components\TextInput::make('number')
                            ->label(__('Nº do telefone'))
                            ->mask(
                                Support\RawJs::make(<<<'JS'
                                    $input.length === 14 ? '(99) 9999-9999' : '(99) 99999-9999'
                                JS)
                            )
                            ->maxLength(255)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Tipo de contato'))
                            ->helperText(__('Nome identificador. Ex: Celular, Whatsapp, Casa, Trabalho...'))
                            ->minLength(2)
                            ->maxLength(255)
                            ->datalist([
                                'Celular',
                                'Whatsapp',
                                'Casa',
                                'Trabalho',
                                'Outros'
                            ])
                            ->autocomplete(false),
                    ])
                    ->itemLabel(
                        fn(mixed $state): ?string =>
                        $state['number'] ?? null
                    )
                    ->addActionLabel(__('Adicionar telefone'))
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->collapseAllAction(
                        fn(Forms\Components\Actions\Action $action) =>
                        $action->label(__('Minimizar todos'))
                    )
                    ->deleteAction(
                        fn(Forms\Components\Actions\Action $action) =>
                        $action->requiresConfirmation()
                    )
                    ->columnSpanFull()
                    ->columns(2),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getSystemAccessFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Acesso ao Sistema'))
            ->description(__('Gerencie o nível de acesso do usuário.'))
            ->schema([
                Forms\Components\TextInput::make('email_confirmation')
                    ->label(__('Usuário'))
                    ->placeholder(__('Preencha o email'))
                    ->required()
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Select::make('roles')
                    ->label(__('Nível(is) de acesso(s)'))
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(RoleService $service, Builder $query): Builder =>
                        $service->getQueryByAuthUserRoles(query: $query)
                    )
                    ->multiple()
                    // ->selectablePlaceholder(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->required()
                    // ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('password')
                    ->label(__('Senha'))
                    ->password()
                    ->helperText(
                        fn(string $operation): string =>
                        $operation === 'create'
                            ? __('Senha com mín. de 8 digitos.')
                            : __('Preencha apenas se desejar alterar a senha. Min. de 8 dígitos.')
                    )
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required(
                        fn(string $operation): bool =>
                        $operation === 'create'
                    )
                    ->confirmed()
                    ->minLength(8)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label(__('Confirmar senha'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required(
                        fn(string $operation): bool =>
                        $operation === 'create'
                    )
                    ->maxLength(255)
                    ->dehydrated(false),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->options(UserStatusEnum::class)
                    ->default(1)
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->required()
                    ->visibleOn('edit'),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getAdditionalInfosFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Infos. Complementares'))
            ->description(__('Forneça informações adicionais relevantes.'))
            ->schema([
                Forms\Components\TextInput::make('cpf')
                    ->label(__('CPF'))
                    ->mask('999.999.999-99')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('rg')
                    ->label(__('RG'))
                    ->maxLength(255),
                Forms\Components\Select::make('gender')
                    ->label(__('Sexo'))
                    ->options(GenderEnum::class)
                    ->native(false),
                Forms\Components\DatePicker::make('birth_date')
                    ->label(__('Dt. nascimento'))
                    ->format('d/m/Y')
                    ->maxDate(now()),
                Forms\Components\Select::make('marital_status')
                    ->label(__('Estado civil'))
                    ->options(MaritalStatusEnum::class)
                    ->searchable()
                    ->native(false),
                Forms\Components\Select::make('educational_level')
                    ->label(__('Escolaridade'))
                    ->options(EducationalLevelEnum::class)
                    ->searchable()
                    ->native(false),
                Forms\Components\TextInput::make('nationality')
                    ->label(__('Nacionalidade'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('citizenship')
                    ->label(__('Naturalidade'))
                    ->maxLength(255),
                Forms\Components\Textarea::make('complement')
                    ->label(__('Sobre'))
                    ->rows(4)
                    ->minLength(2)
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                    ->label(__('Avatar'))
                    ->helperText(__('Tipos de arquivo permitidos: .png, .jpg, .jpeg, .gif. // 500x500px // máx. 5 mb.'))
                    ->collection('avatar')
                    ->image()
                    ->avatar()
                    ->downloadable()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        // '16:9', // ex: 1920x1080px
                        // '4:3',  // ex: 1024x768px
                        '1:1',  // ex: 500x500px
                    ])
                    ->circleCropper()
                    ->imageResizeTargetWidth(500)
                    ->imageResizeTargetHeight(500)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/gif'])
                    ->maxSize(5120)
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file, Get $get): string =>
                        (string) str('-' . md5(uniqid()) . '-' . time() . '.' . $file->guessExtension())
                            ->prepend(Str::slug($get('name'))),
                    )
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getAddressFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Endereço'))
            ->description(__('Detalhes do endereço residencial do usuário.'))
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\TextInput::make('address.zipcode')
                            ->label(__('CEP'))
                            ->mask('99999-999')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (AddressService $service, Set $set, mixed $old, mixed $state): void {
                                    if ($old === $state) {
                                        return;
                                    }

                                    $address = $service->getAddressByZipcodeBrasilApi(zipcode: $state);

                                    if (isset($address['error'])) {
                                        $set('address.uf', null);
                                        $set('address.city', null);
                                        $set('address.district', null);
                                        $set('address.address_line', null);
                                        $set('address.complement', null);
                                    } else {
                                        $set('address.uf', $address['state']);
                                        $set('address.city', $address['city']);
                                        $set('address.district', $address['neighborhood']);
                                        $set('address.address_line', $address['street']);
                                        // $set('address.complement', null);
                                    }
                                }
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Forms\Components\Select::make('address.uf')
                    ->label(__('Estado'))
                    ->options(UfEnum::class)
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->native(false),
                Forms\Components\TextInput::make('address.city')
                    ->label(__('Cidade'))
                    ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('address.district')
                    ->label(__('Bairro'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('address.address_line')
                    ->label(__('Endereço'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('address.number')
                    ->label(__('Número'))
                    // ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('address.complement')
                    ->label(__('Complemento'))
                    ->helperText(__('Apto / Bloco / Casa'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('address.reference')
                    ->label(__('Ponto de referência'))
                    ->maxLength(255),
            ])
            ->columns(2)
            ->collapsible();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns(static::getTableColumns())
            ->defaultSort(column: 'created_at', direction: 'desc')
            ->filters(static::getTableFilters(), layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make()
                            ->extraModalFooterActions([
                                Tables\Actions\Action::make('edit')
                                    ->label(__('Editar'))
                                    ->button()
                                    ->url(
                                        fn(User $record): string =>
                                        self::getUrl('edit', ['record' => $record]),
                                    )
                                    ->hidden(
                                        fn(): bool =>
                                        !auth()->user()->can('Editar Usuários')
                                    ),
                            ]),
                        Tables\Actions\EditAction::make(),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->before(
                            fn(UserService $service, Tables\Actions\DeleteAction $action, User $record) =>
                            $service->preventDeleteIf(action: $action, user: $record)
                        ),
                ])
                    ->label(__('Ações'))
                    ->icon('heroicon-m-chevron-down')
                    ->size(Support\Enums\ActionSize::ExtraSmall)
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(
                            fn(UserService $service, Collection $records) =>
                            $service->deleteBulkAction(records: $records)
                        )
                        ->hidden(
                            fn(): bool =>
                            !auth()->user()->can('Deletar Usuários'),
                        ),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordAction(Tables\Actions\ViewAction::class)
            ->recordUrl(null);
    }

    protected static function getTableColumns(): array
    {
        return [
            // Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
            //     ->label('')
            //     ->collection('avatar')
            //     ->conversion('thumb')
            //     ->size(45)
            //     ->circular(),
            Tables\Columns\ImageColumn::make('avatar')
                ->label('')
                ->getStateUsing(
                    fn(User $record): ?string =>
                    $record->getFirstMediaUrl('avatar', 'thumb')
                )
                ->width(45)
                ->height(45)
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Nome'))
                ->description(
                    fn(User $record): ?string =>
                    $record->cpf,
                )
                ->searchable(
                    query: fn(UserService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByNameAndCpf(query: $query, search: $search)
                )
                ->sortable(),
            Tables\Columns\TextColumn::make('roles.name')
                ->label(__('Nível(is) de acesso(s)'))
                ->badge()
                ->searchable()
                // ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('email')
                ->label(__('Email'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('display_main_phone')
                ->label(__('Telefone'))
                ->searchable(
                    query: fn(UserService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByMainPhone(query: $query, search: $search)
                )
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->searchable(
                    query: fn(UserService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByStatus(query: $query, search: $search)
                )
                ->sortable(
                    query: fn(UserService $service, Builder $query, string $direction): Builder =>
                    $service->tableSortByStatus(query: $query, direction: $direction)
                )
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('created_at')
                ->label(__('Cadastro'))
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('updated_at')
                ->label(__('Últ. atualização'))
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('roles')
                ->label(__('Nível(is) de acesso(s)'))
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(RoleService $service, Builder $query): Builder =>
                    $service->getQueryByAuthUserRoles(query: $query)
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->multiple()
                ->options(UserStatusEnum::class),
            Tables\Filters\Filter::make('created_at')
                ->label(__('Cadastro'))
                ->form([
                    Forms\Components\Grid::make([
                        'default' => 1,
                        'md'      => 2,
                    ])
                        ->schema([
                            Forms\Components\DatePicker::make('created_from')
                                ->label(__('Cadastro de'))
                                ->live(debounce: 500)
                                ->afterStateUpdated(
                                    function (Set $set, Get $get, mixed $state): void {
                                        if (!empty($get('created_until')) && $state > $get('created_until')) {
                                            $set('created_until', $state);
                                        }
                                    }
                                ),
                            Forms\Components\DatePicker::make('created_until')
                                ->label(__('Cadastro até'))
                                ->live(debounce: 500)
                                ->afterStateUpdated(
                                    function (Set $set, Get $get, mixed $state): void {
                                        if (!empty($get('created_from')) && $state < $get('created_from')) {
                                            $set('created_from', $state);
                                        }
                                    }
                                ),
                        ]),
                ])
                ->query(
                    fn(UserService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(UserService $service, mixed $state): ?string =>
                    $service->tableFilterIndicateUsingByCreatedAt(data: $state),
                ),
            Tables\Filters\Filter::make('updated_at')
                ->label(__('Últ. atualização'))
                ->form([
                    Forms\Components\Grid::make([
                        'default' => 1,
                        'md'      => 2,
                    ])
                        ->schema([
                            Forms\Components\DatePicker::make('updated_from')
                                ->label(__('Últ. atualização de'))
                                ->live(debounce: 500)
                                ->afterStateUpdated(
                                    function (Set $set, Get $get, mixed $state): void {
                                        if (!empty($get('updated_until')) && $state > $get('updated_until')) {
                                            $set('updated_until', $state);
                                        }
                                    }
                                ),
                            Forms\Components\DatePicker::make('updated_until')
                                ->label(__('Últ. atualização até'))
                                ->live(debounce: 500)
                                ->afterStateUpdated(
                                    function (Set $set, Get $get, mixed $state): void {
                                        if (!empty($get('updated_from')) && $state < $get('updated_from')) {
                                            $set('updated_from', $state);
                                        }
                                    }
                                ),
                        ]),
                ])
                ->query(
                    fn(UserService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(UserService $service, mixed $state): ?string =>
                    $service->tableFilterIndicateUsingByUpdatedAt(data: $state),
                ),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Label')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make(__('Infos. Gerais'))
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label(__('#ID')),
                                Infolists\Components\SpatieMediaLibraryImageEntry::make('avatar')
                                    ->label(__('Avatar'))
                                    ->hiddenLabel()
                                    ->collection('avatar')
                                    ->conversion('thumb')
                                    ->circular()
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('Nome')),
                                Infolists\Components\TextEntry::make('roles.name')
                                    ->label(__('Nível(is) de acesso(s)'))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('email')
                                    ->label(__('Email')),
                                Infolists\Components\TextEntry::make('display_additional_emails')
                                    ->label(__('Emails adicionais'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('display_main_phone_with_name')
                                    ->label(__('Telefone'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('display_additional_phones')
                                    ->label(__('Telefones adicionais'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('cpf')
                                    ->label(__('CPF'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('rg')
                                    ->label(__('RG'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('gender')
                                    ->label(__('Sexo'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('display_birth_date')
                                    ->label(__('Dt. nascimento'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('marital_status')
                                    ->label(__('Estado civil'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('educational_level')
                                    ->label(__('Escolaridade'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('nationality')
                                    ->label(__('Nacionalidade'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('citizenship')
                                    ->label(__('Naturalidade'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('complement')
                                    ->label(__('Sobre'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    )
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('address.display_full_address')
                                    ->label(__('Endereço'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    )
                                    ->columnSpanFull(),
                                Infolists\Components\Grid::make(['default' => 3])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('status')
                                            ->label(__('Status'))
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label(__('Cadastro'))
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label(__('Últ. atualização'))
                                            ->dateTime('d/m/Y H:i'),
                                    ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make(__('Anexos'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('attachments')
                                    ->label('Arquivo(s)')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label(__('Nome'))
                                            ->helperText(
                                                fn(Media $record): string =>
                                                $record->file_name
                                            )
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('mime_type')
                                            ->label(__('Mime')),
                                        Infolists\Components\TextEntry::make('size')
                                            ->label(__('Tamanho'))
                                            ->state(
                                                fn(Media $record): string =>
                                                AbbrNumberFormat($record->size),
                                            )
                                            ->hintAction(
                                                Infolists\Components\Actions\Action::make('download')
                                                    ->label(__('Download'))
                                                    ->icon('heroicon-s-arrow-down-tray')
                                                    ->action(
                                                        fn(Media $record) =>
                                                        response()->download($record->getPath(), $record->file_name),
                                                    ),
                                            ),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ])
                            ->visible(
                                fn(User $record): bool =>
                                $record->attachments?->count() > 0
                            ),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function getRelations(): array
    {
        return [
            MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->byAuthUserRoles(user: $user);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'cpf'];
    }
}
