<?php

namespace App\Filament\Resources\System;

use App\Enums\DefaultStatusEnum;
use App\Enums\ProfileInfos\SocialMediaEnum;
use App\Enums\ProfileInfos\UfEnum;
use App\Filament\Resources\System\TenantAccountResource\Pages;
use App\Filament\Resources\System\TenantAccountResource\RelationManagers;
use App\Filament\Resources\Polymorphics\RelationManagers\MediaRelationManager;
use App\Models\System\Tenant;
use App\Models\System\TenantAccount;
use App\Services\Polymorphics\AddressService;
use App\Services\System\TenantAccountService;
use App\Services\System\TenantCategoryService;
use App\Services\System\TenantPlanService;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Support;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TenantAccountResource extends Resource
{
    protected static ?string $model = TenantAccount::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Conta';

    protected static ?string $pluralModelLabel = 'Contas de Clientes';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Contas de Clientes';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('Infos. Gerais e Acesso'))
                            ->schema([
                                static::getGeneralInfosFormSection(),
                                static::getSystemAccessFormSection()
                                    ->visibleOn('create'),
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
            ->description(__('Visão geral e informações fundamentais sobre a conta.'))
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome da conta'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        function (Set $set, mixed $state): void {
                            if (!filled($state)) {
                                return;
                            }

                            $slug = SlugService::createSlug(
                                model: Tenant::class,
                                attribute: 'id',
                                fromString: $state
                            );

                            $set('tenant_id', $slug);
                        }
                    )
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('tenant_id')
                    ->default(null),
                Forms\Components\TextInput::make('cpf_cnpj')
                    ->label(__('CPF / CNPJ'))
                    ->mask(
                        Support\RawJs::make(<<<'JS'
                            $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
                        JS)
                    )
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->live(onBlur: true),
                Forms\Components\Select::make('plan_id')
                    ->label(__('Plano da conta'))
                    ->relationship(
                        name: 'plan',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(TenantPlanService $service, Builder $query): Builder =>
                        $service->getQueryByPlans(query: $query)
                    )
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('holder_name')
                    ->label(__('Nome do titular da conta'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Forms\Components\Select::make('categories')
                    ->label(__('Categoria(s)'))
                    ->relationship(
                        name: 'categories',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(TenantCategoryService $service, Builder $query): Builder =>
                        $service->getQueryByTenantCategories(query: $query)
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    // ->required()
                    ->when(
                        auth()->user()->can('Cadastrar Categorias de Contas'),
                        fn(Forms\Components\Select $component): Forms\Components\Select =>
                        $component->suffixAction(
                            fn(TenantCategoryService $service): Forms\Components\Actions\Action =>
                            $service->quickCreateActionByTenantCategories(field: 'categories', multiple: true),
                        ),
                    )
                    ->columnSpanFull(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                    ->label(__('Logo/Avatar'))
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
                    ),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->options(DefaultStatusEnum::class)
                    ->default(1)
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->required(),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getSystemAccessFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Acesso ao Sistema'))
            ->description(__('Gerencie o nível de acesso do usuário.'))
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->label(__('Nome do titular da conta'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('user.email')
                    ->label(__('Email'))
                    ->placeholder(__('Preencha o email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('user.password')
                    ->label(__('Senha'))
                    ->password()
                    ->helperText(
                        fn(string $operation): string =>
                        $operation === 'create'
                            ? __('Senha com mín. de 8 digitos.')
                            : __('Preencha apenas se desejar alterar a senha. Min. de 8 dígitos.'),
                    )
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required(
                        fn(string $operation): bool =>
                        $operation === 'create',
                    )
                    ->confirmed()
                    ->minLength(8)
                    ->maxLength(255)
                    ->hidden(
                        fn(Get $get): ?bool =>
                        $get('user.id'),
                    ),
                Forms\Components\TextInput::make('user.password_confirmation')
                    ->label(__('Confirmar senha'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required(
                        fn(string $operation): bool =>
                        $operation === 'create',
                    )
                    ->maxLength(255)
                    ->dehydrated(false)
                    ->hidden(
                        fn(Get $get): ?bool =>
                        $get('user.id')
                    ),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getAdditionalInfosFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Infos. Complementares'))
            ->description(__('Forneça informações adicionais relevantes.'))
            ->schema([
                // Forms\Components\TextInput::make('domain')
                //     ->label(__('Domínio'))
                //     ->helperText('Ex: seudominio.com.br')
                //     // ->url()
                //     ->unique(ignoreRecord: true)
                //     ->maxLength(255)
                //     ->columnSpanFull(),
                Forms\Components\Repeater::make('emails')
                    ->label(__('Email(s)'))
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
                            // ->required()
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
                Forms\Components\Textarea::make('complement')
                    ->label(__('Sobre'))
                    ->rows(4)
                    ->minLength(2)
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Fieldset::make(__('Configs. do tema'))
                    ->schema([
                        Forms\Components\ColorPicker::make('theme.primary_color')
                            ->label(__('Cor primária (hexadecimal)')),
                        Forms\Components\ColorPicker::make('theme.secondary_color')
                            ->label(__('Cor secundária (hexadecimal)')),
                        Forms\Components\ColorPicker::make('theme.background_color')
                            ->label(__('Cor do fundo (hexadecimal)')),
                    ])
                    ->columns(3),
                Forms\Components\Repeater::make('social_media')
                    ->label('Redes Sociais')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label(__('Rede social'))
                            ->options(SocialMediaEnum::class)
                            ->selectablePlaceholder(false)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('url')
                            ->label(__('Link'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->itemLabel(
                        fn(mixed $state): ?string =>
                        $state['name'] ?? null
                    )
                    ->addActionLabel(__('Adicionar rede social'))
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
                Forms\Components\Repeater::make('opening_hours')
                    ->label(__('Horário de funcionamento'))
                    ->simple(
                        Forms\Components\TextInput::make('value')
                            ->label(__('Horário de funcionamento'))
                            ->required()
                            ->maxLength(255),
                    )
                    ->itemLabel(
                        fn(mixed $state): ?string =>
                        $state['value'] ?? null
                    )
                    ->addActionLabel(__('Adicionar nova linha'))
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
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getAddressFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Endereço'))
            ->description(__('Detalhes do endereço da conta de cliente.'))
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\TextInput::make('address.zipcode')
                            ->label(__('CEP'))
                            ->mask('99999-999')
                            // ->required()
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
                    ->placeholder(__('Informe primeiramente o CEP'))
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->native(false)
                    // ->required()
                    // ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('address.city')
                    ->label(__('Cidade'))
                    ->placeholder(__('Informe primeiramente o CEP'))
                    // ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    // ->disabled()
                    ->dehydrated(),
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
                Forms\Components\Textarea::make('address.gmap_coordinates')
                    ->label(__('Incorporar Google Maps'))
                    ->rows(4)
                    ->minLength(2)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns(static::getTableColumns())
            ->defaultSort(column: 'id', direction: 'desc')
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
                                        fn(TenantAccount $record): string =>
                                        self::getUrl('edit', ['record' => $record]),
                                    )
                                    ->hidden(
                                        fn(): bool =>
                                        !auth()->user()->can('Editar Contas de Clientes')
                                    ),
                            ]),
                        Tables\Actions\EditAction::make(),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->before(
                            fn(TenantAccountService $service, Tables\Actions\DeleteAction $action, TenantAccount $record) =>
                            $service->preventDeleteIf(action: $action, tenantAccount: $record)
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
                        ->hidden(
                            fn(): bool =>
                            !auth()->user()->can('Deletar Contas de Clientes'),
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
            Tables\Columns\TextColumn::make('id')
                ->label(__('#ID'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                ->label('')
                ->collection('avatar')
                ->conversion('thumb')
                ->size(45)
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Nome'))
                ->description(
                    fn(TenantAccount $record): ?string =>
                    $record->cpf_cnpj,
                )
                ->searchable(
                    query: fn(TenantAccountService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByNameAndCpfCnpj(query: $query, search: $search)
                )
                ->sortable(),
            Tables\Columns\TextColumn::make('holder_name')
                ->label(__('Titular da conta'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            // Tables\Columns\TextColumn::make('role')
            //     ->label(__('Tipo'))
            //     ->badge()
            //     ->searchable()
            //     ->sortable()
            //     ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('plan.name')
                ->label(__('Plano'))
                ->badge()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('categories.name')
                ->label(__('Categoria(s)'))
                ->badge()
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('address.city')
                ->label(__('Cidade, Uf'))
                ->formatStateUsing(
                    fn(TenantAccount $record): string =>
                    $record->address->city . ', ' . $record->address->uf?->value
                )
                ->searchable(
                    query: fn(TenantAccountService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByCityAndState(query: $query, search: $search)
                )
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->searchable(
                    query: fn(TenantAccountService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByStatus(query: $query, search: $search)
                )
                ->sortable(
                    query: fn(TenantAccountService $service, Builder $query, string $direction): Builder =>
                    $service->tableSortByStatus(query: $query, direction: $direction)
                )
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('tenant.created_at')
                ->label(__('Cadastro'))
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('tenant.updated_at')
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
            Tables\Filters\SelectFilter::make('plans')
                ->label(__('Plano(s)'))
                ->relationship(
                    name: 'plan',
                    titleAttribute: 'name'
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('categories')
                ->label(__('Categoria(s)'))
                ->relationship(
                    name: 'categories',
                    titleAttribute: 'name'
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('cities')
                ->label(__('Cidade(s)'))
                ->relationship(
                    name: 'address',
                    titleAttribute: 'city',
                    modifyQueryUsing: fn(Builder $query): Builder =>
                    $query->whereNotNull('city')
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('ufs')
                ->label(__('Estado(s)'))
                ->options(UfEnum::class)
                ->query(
                    fn(TenantAccountService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUfs(query: $query, data: $data)
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->options(DefaultStatusEnum::class)
                ->multiple(),
            Tables\Filters\Filter::make('tenant.created_at')
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
                    fn(TenantAccountService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(TenantAccountService $service, mixed $state): ?string =>
                    $service->tableFilterIndicateUsingByCreatedAt(data: $state),
                ),
            Tables\Filters\Filter::make('tenant.updated_at')
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
                    fn(TenantAccountService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(TenantAccountService $service, mixed $state): ?string =>
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
                                // Infolists\Components\TextEntry::make('role.name')
                                //     ->label(__('Tipo'))
                                //     ->badge(),
                                Infolists\Components\TextEntry::make('cpf_cnpj')
                                    ->label(__('CPF/CNPJ'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('plan.name')
                                    ->label(__('Plano'))
                                    ->badge()
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('holder_name')
                                    ->label(__('Titular da conta'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('categories.name')
                                    ->label(__('Categoria(s)'))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('display_main_email')
                                    ->label(__('Email'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
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
                                        Infolists\Components\TextEntry::make('tenant.created_at')
                                            ->label(__('Cadastro'))
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('tenant.updated_at')
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
                                fn(TenantAccount $record): bool =>
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
            'index'  => Pages\ListTenantAccounts::route('/'),
            'create' => Pages\CreateTenantAccount::route('/create'),
            'edit'   => Pages\EditTenantAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('tenant');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'cpf_cnpj', 'holder_name'];
    }
}
