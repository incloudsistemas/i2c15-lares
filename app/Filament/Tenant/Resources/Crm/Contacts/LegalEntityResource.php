<?php

namespace App\Filament\Tenant\Resources\Crm\Contacts;

use App\Enums\ProfileInfos\UserStatusEnum;
use App\Filament\Resources\Polymorphics\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Polymorphics\RelationManagers\MediaRelationManager;
use App\Filament\Tenant\Resources\Crm\Contacts\LegalEntityResource\Pages;
use App\Filament\Tenant\Resources\Crm\Contacts\LegalEntityResource\RelationManagers;
use App\Models\Crm\Contacts\LegalEntity;
use App\Models\Polymorphics\Address;
use App\Services\Crm\Contacts\ContactService;
use App\Services\Crm\Contacts\IndividualService;
use App\Services\Crm\Contacts\LegalEntityService;
use App\Services\Crm\Contacts\RoleService;
use App\Services\Crm\SourceService;
use App\Services\System\UserService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LegalEntityResource extends Resource
{
    protected static ?string $model = LegalEntity::class;

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationParentItem = 'Contatos';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('Infos. Gerais'))
                            ->schema([
                                static::getGeneralInfosFormSection(),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('Infos. Complementares'))
                            ->schema([
                                static::getAdditionalInfosFormSection(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function getGeneralInfosFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Infos. Gerais'))
            ->description(__('Visão geral e informações fundamentais sobre o contato.'))
            ->schema([
                Forms\Components\TextInput::make('contact.name')
                    ->label(__('Nome'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Fieldset::make(__('Tipos de contato'))
                    ->schema([
                        Forms\Components\CheckboxList::make('contact.roles')
                            ->hiddenLabel()
                            ->options(
                                fn(RoleService $service): array =>
                                $service->getOptionsByRoles(),
                            )
                            ->columns(6)
                            ->gridDirection('row')
                            ->columnSpanFull(),
                    ])
                    ->columns(6),
                Forms\Components\TextInput::make('contact.email')
                    ->label(__('Email'))
                    ->email()
                    ->rules([
                        function (ContactService $service, ?LegalEntity $record): Closure {
                            return function (
                                string $attribute,
                                mixed $state,
                                Closure $fail
                            ) use ($service, $record): void {
                                $contactableType = MorphMapByClass(model: LegalEntity::class);

                                $service->validateEmail(
                                    contact: $record?->contact,
                                    contactableType: $contactableType,
                                    attribute: $attribute,
                                    state: $state,
                                    fail: $fail,
                                );
                            };
                        },
                    ])
                    // ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('contact.additional_emails')
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
                            ->datalist([
                                'Pessoal',
                                'Trabalho',
                                'Outros'
                            ])
                            ->minLength(2)
                            ->maxLength(255)
                            ->autocomplete(false),
                    ])
                    ->itemLabel(
                        fn(mixed $state): ?string =>
                        $state['email'] ?? null,
                    )
                    ->addActionLabel(__('Adicionar email'))
                    ->defaultItems(0)
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->collapseAllAction(
                        fn(Forms\Components\Actions\Action $action) =>
                        $action->label(__('Minimizar todos')),
                    )
                    ->deleteAction(
                        fn(Forms\Components\Actions\Action $action) =>
                        $action->requiresConfirmation(),
                    )
                    ->columnSpanFull()
                    ->columns(2),
                Forms\Components\Repeater::make('contact.phones')
                    ->label(__('Telefone(s) de contato'))
                    ->schema([
                        Forms\Components\TextInput::make('number')
                            ->label(__('Nº do telefone'))
                            ->mask(
                                Support\RawJs::make(<<<'JS'
                                    $input.length === 14 ? '(99) 9999-9999' : '(99) 99999-9999'
                                JS)
                            )
                            ->rules([
                                function (ContactService $service, ?LegalEntity $record): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($service, $record): void {
                                        $contactableType = MorphMapByClass(model: LegalEntity::class);

                                        $service->validatePhone(
                                            contact: $record?->contact,
                                            contactableType: $contactableType,
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail,
                                        );
                                    };
                                },
                            ])
                            ->maxLength(255)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Tipo de contato'))
                            ->helperText(__('Nome identificador. Ex: Celular, Whatsapp, Casa, Trabalho...'))
                            ->datalist([
                                'Celular',
                                'Whatsapp',
                                'Casa',
                                'Trabalho',
                                'Outros'
                            ])
                            ->minLength(2)
                            ->maxLength(255)
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
                Forms\Components\Select::make('individuals')
                    ->label(__('Pessoa(s) relacionada(s)'))
                    ->getSearchResultsUsing(
                        fn(IndividualService $service, string $search): array =>
                        $service->getIndividualOptionsBySearch(search: $search),
                    )
                    ->getOptionLabelsUsing(
                        fn(IndividualService $service, array $values): array =>
                        $service->getIndividualOptionLabels(values: $values),
                    )
                    ->multiple()
                    // ->selectablePlaceholder(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    // ->required()
                    ->when(
                        auth()->user()->can('Cadastrar [CRM] Contatos'),
                        fn(Forms\Components\Select $component): Forms\Components\Select =>
                        $component->suffixAction(
                            fn(IndividualService $service): Forms\Components\Actions\Action =>
                            $service->quickCreateActionByContactIndividuals(field: 'individuals', multiple: true),
                        ),
                    )
                    ->columnSpanFull(),
                Forms\Components\Select::make('contact.source_id')
                    ->label(__('Origem da captação'))
                    ->options(
                        fn(SourceService $service): array =>
                        $service->getOptionsBySources(),
                    )
                    // ->multiple()
                    // ->selectablePlaceholder(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    // ->required()
                    ->when(
                        auth()->user()->can('Cadastrar [CRM] Origens dos Contatos/Negócios'),
                        fn(Forms\Components\Select $component): Forms\Components\Select =>
                        $component->suffixAction(
                            fn(SourceService $service): Forms\Components\Actions\Action =>
                            $service->quickCreateActionBySources(field: 'contact.source_id'),
                        ),
                    ),
                Forms\Components\Select::make('contact.user_id')
                    ->label(__('Captador'))
                    ->getSearchResultsUsing(
                        fn(UserService $service, string $search): array =>
                        $service->getUserOptionsBySearch(search: $search),
                    )
                    ->getOptionLabelUsing(
                        fn(UserService $service, int $value): string =>
                        $service->getUserOptionLabel(value: $value),
                    )
                    ->searchable()
                    ->preload()
                    ->default(auth()->user()->id),
                Forms\Components\Select::make('contact.status')
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
                Forms\Components\TextInput::make('trade_name')
                    ->label(__('Nome fantasia'))
                    ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('cnpj')
                    ->label(__('CNPJ'))
                    ->mask('99.999.999/9999-99')
                    ->rules([
                        function (LegalEntityService $service, ?LegalEntity $record): Closure {
                            return function (
                                string $attribute,
                                mixed $state,
                                Closure $fail
                            ) use ($service, $record): void {
                                $service->validateCnpj(
                                    legalEntity: $record,
                                    attribute: $attribute,
                                    state: $state,
                                    fail: $fail
                                );
                            };
                        },
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('municipal_registration')
                    ->label(__('Inscrição municipal'))
                    ->helperText(__('É o número de identificação municipal da sua empresa cadastrado na prefeitura.'))
                    ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('state_registration')
                    ->label(__('Inscrição estadual'))
                    ->helperText(__('É o número de registro fornecido pela Secretaria da Fazenda (SEFAZ) de cada estado.'))
                    ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->label(__('URL do site'))
                    ->url()
                    // ->prefix('https://')
                    ->helperText('https://...')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sector')
                    ->label(__('Setor'))
                    ->datalist([
                        'Serviços',
                        'Comércio',
                        'Indústria',
                        'Outros'
                    ])
                    ->maxLength(255)
                    ->autocomplete(false),
                Forms\Components\Select::make('num_employees')
                    ->label(__('Nº de funcionários?'))
                    ->options([
                        '1-5'      => '1-5',
                        '6-10'     => '6-10',
                        '11-50'    => '11-50',
                        '51-250'   => '51-250',
                        '251-1000' => '251-1000',
                    ])
                    // ->default(1)
                    // ->selectablePlaceholder(false)
                    ->native(false),
                Forms\Components\Select::make('monthly_income')
                    ->label(__('Faturamento mensal'))
                    ->options([
                        'até R$ 10.000'                 => 'até R$ 10.000',
                        'entre R$ 11.000 e R$ 50.000'   => 'entre R$ 11.000 e R$ 50.000',
                        'entre R$ 51.000 e R$ 100.000'  => 'entre R$ 51.000 e R$ 100.000',
                        'entre R$ 101.000 e R$ 300.000' => 'entre R$ 101.000 e R$ 300.000',
                        'acima de R$ 300.000'           => 'acima de R$ 300.000',
                    ])
                    // ->default(1)
                    // ->selectablePlaceholder(false)
                    ->native(false),
                Forms\Components\Textarea::make('contact.complement')
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
                        fn(TemporaryUploadedFile $file, callable $get): string =>
                        (string) str('-' . md5(uniqid()) . '-' . time() . '.' . $file->guessExtension())
                            ->prepend(Str::slug($get('contact.name'))),
                    )
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
            ->defaultSort(column: 'contact.created_at', direction: 'desc')
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
                                        fn(LegalEntity $record): string =>
                                        self::getUrl('edit', ['record' => $record]),
                                    )
                                    ->hidden(
                                        fn(): bool =>
                                        !auth()->user()->can('Editar [CRM] Contatos'),
                                    ),
                            ]),
                        Tables\Actions\EditAction::make(),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->label(__('Excluir'))
                        ->before(
                            fn(ContactService $service, Tables\Actions\DeleteAction $action, LegalEntity $record) =>
                            $service->preventDeleteIf(action: $action, contact: $record->contact),
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
                            fn(ContactService $service, Collection $records) =>
                            $service->deleteBulkAction(records: $records),
                        )
                        ->hidden(
                            fn(): bool =>
                            !auth()->user()->can('Deletar [CRM] Contatos'),
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
            Tables\Columns\TextColumn::make('contact.id')
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
            Tables\Columns\TextColumn::make('contact.name')
                ->label(__('Nome'))
                ->description(
                    fn(LegalEntity $record): ?string =>
                    $record->cnpj ?? null,
                )
                ->searchable(
                    query: fn(ContactService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByContactNameAndCpfOrCnpj(query: $query, search: $search),
                )
                ->sortable(),
            Tables\Columns\TextColumn::make('contact.roles.name')
                ->label(__('Tipo(s)'))
                ->badge()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('contact.email')
                ->label(__('Email'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('contact.display_main_phone')
                ->label(__('Telefone'))
                ->searchable(
                    query: fn(ContactService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByContactMainPhone(query: $query, search: $search),
                )
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('contact.source.name')
                ->label(__('Origem'))
                ->badge()
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('contact.owner.name')
                ->label(__('Captador'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('contact.status')
                ->label(__('Status'))
                ->badge()
                ->searchable(
                    query: fn(ContactService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByContactStatus(query: $query, search: $search),
                )
                ->sortable(
                    query: fn(ContactService $service, Builder $query, string $direction): Builder =>
                    $service->tableSortByContactStatus(query: $query, direction: $direction),
                )
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('contact.created_at')
                ->label(__('Cadastro'))
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('contact.updated_at')
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
            Tables\Filters\SelectFilter::make('contact.roles')
                ->label(__('Tipo(s)'))
                ->relationship(
                    name: 'contact.roles',
                    titleAttribute: 'name',
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('contact.sources')
                ->label(__('Origem(s)'))
                ->relationship(
                    name: 'contact.source',
                    titleAttribute: 'name',
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('contact.owners')
                ->label(__('Captador(es)'))
                ->relationship(
                    name: 'contact.owner',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(ContactService $service, Builder $query): Builder =>
                    $service->getQueryByElementsWhereHasContactsBasedOnAuthUserRoles(query: $query),
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('contact.status')
                ->label(__('Status'))
                ->multiple()
                ->options(UserStatusEnum::class)
                ->query(
                    fn(ContactService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByContactStatuses(query: $query, data: $data),
                ),
            Tables\Filters\Filter::make('contact.created_at')
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
                    fn(ContactService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByContactCreatedAt(query: $query, data: $data),
                )
                ->indicateUsing(
                    fn(ContactService $service, mixed $state): ?string =>
                    $service->tableFilterIndicateUsingByCreatedAt(data: $state),
                ),
            Tables\Filters\Filter::make('contact.updated_at')
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
                    fn(ContactService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByContactUpdatedAt(query: $query, data: $data),
                )
                ->indicateUsing(
                    fn(ContactService $service, mixed $state): ?string =>
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
                                Infolists\Components\TextEntry::make('contact.id')
                                    ->label(__('#ID')),
                                Infolists\Components\SpatieMediaLibraryImageEntry::make('avatar')
                                    ->label(__('Avatar'))
                                    ->hiddenLabel()
                                    ->collection('avatar')
                                    ->conversion('thumb')
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.name')
                                    ->label(__('Nome'))
                                    ->helperText(
                                        fn(LegalEntity $record): ?string =>
                                        $record->trade_name,
                                    ),
                                Infolists\Components\TextEntry::make('contact.roles.name')
                                    ->label(__('Tipo(s)'))
                                    ->badge()
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.email')
                                    ->label(__('Email'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.display_additional_emails')
                                    ->label(__('Emails adicionais'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.display_main_phone_with_name')
                                    ->label(__('Telefone'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.display_additional_phones')
                                    ->label(__('Telefones adicionais'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                // Infolists\Components\TextEntry::make('trade_name')
                                //     ->label(__('Nome fantasia'))
                                //     ->visible(
                                //         fn(mixed $state): bool =>
                                //         !empty($state),
                                //     ),
                                Infolists\Components\TextEntry::make('cnpj')
                                    ->label(__('CNPJ'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('municipal_registration')
                                    ->label(__('Inscrição municipal'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('state_registration')
                                    ->label(__('Inscrição estadual'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('url')
                                    ->label(__('URL do site'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('sector')
                                    ->label(__('Setor'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('monthly_income')
                                    ->label(__('Faturamento mensal'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.source.name')
                                    ->label(__('Origem'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.owner.name')
                                    ->label(__('Captador'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contact.complement')
                                    ->label(__('Sobre'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    )
                                    ->columnSpanFull(),
                                Infolists\Components\Grid::make(['default' => 3])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('contact.status')
                                            ->label(__('Status'))
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('contact.created_at')
                                            ->label(__('Cadastro'))
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('contact.updated_at')
                                            ->label(__('Últ. atualização'))
                                            ->dateTime('d/m/Y H:i'),
                                    ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make(__('Endereço(s)'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('addresses')
                                    ->hiddenLabel()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label(__('Tipo'))
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('display_short_address')
                                            ->label(__('Endereço'))
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('zipcode')
                                            ->label(__('CEP')),
                                        Infolists\Components\TextEntry::make('city')
                                            ->label(__('Cidade/Uf'))
                                            ->formatStateUsing(
                                                fn(Address $record): string =>
                                                "{$record->city}-{$record->uf->name}"
                                            ),
                                        Infolists\Components\IconEntry::make('is_main')
                                            ->label(__('Principal'))
                                            ->icon(
                                                fn(mixed $state): string =>
                                                match ($state) {
                                                    false => 'heroicon-m-minus-small',
                                                    true  => 'heroicon-o-check-circle',
                                                }
                                            )
                                            ->color(
                                                fn(mixed $state): string =>
                                                match ($state) {
                                                    true    => 'success',
                                                    default => 'gray',
                                                }
                                            ),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ])
                            ->visible(
                                fn(LegalEntity $record): bool =>
                                $record->addresses?->count() > 0
                            ),
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
                                fn(LegalEntity $record): bool =>
                                $record->attachments?->count() > 0
                            ),
                        Infolists\Components\Tabs\Tab::make(__('Histórico de Interações'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('logActivities')
                                    ->label('Interação(ões)')
                                    ->hiddenLabel()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('description')
                                            ->hiddenLabel()
                                            ->html()
                                            ->columnSpan(3),
                                        Infolists\Components\TextEntry::make('causer.name')
                                            ->label(__('Por:')),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label(__('Cadastro'))
                                            ->dateTime('d/m/Y H:i'),
                                    ])
                                    ->columns(5)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
            MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLegalEntities::route('/'),
            'create' => Pages\CreateLegalEntity::route('/create'),
            'edit'   => Pages\EditLegalEntity::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with('contact');

        if ($user->hasAnyRole(['Superadministrador', 'Administrador'])) {
            return $query->whereHas('contact');
        }

        $service = app(ContactService::class);
        $usersIds = $service->getOwnedUsersByAuthUserRolesAgenciesAndTeams(user: $user);

        return $query->whereHas('contact', fn(Builder $query): Builder => $query->whereIn('user_id', $usersIds));
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['cnpj'];
    }
}
