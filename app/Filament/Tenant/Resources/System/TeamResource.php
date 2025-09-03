<?php

namespace App\Filament\Tenant\Resources\System;

use App\Enums\DefaultStatusEnum;
use App\Filament\Tenant\Resources\System\TeamResource\Pages;
use App\Filament\Tenant\Resources\System\TeamResource\RelationManagers;
use App\Models\System\Team;
use App\Models\System\User;
use App\Services\System\AgencyService;
use App\Services\System\TeamService;
use App\Services\System\UserService;
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
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Equipes';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationParentItem = 'Agências';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

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
                        Forms\Components\Tabs\Tab::make(__('Membros da Equipe'))
                            ->schema([
                                static::getUsersFormSection(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function getGeneralInfosFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Infos. Gerais'))
            ->description(__('Visão geral e informações fundamentais sobre a equipe.'))
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn(Set $set, mixed $state): ?string =>
                        $set('slug', Str::slug($state))
                    )
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('slug')
                    ->label(__('Slug'))
                    ->helperText(__('O "slug" é a versão do nome amigável para URL. Geralmente é todo em letras minúsculas e contém apenas letras, números e hifens.'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Forms\Components\Select::make('agency_id')
                    ->label(__('Agência'))
                    ->relationship(
                        name: 'agency',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(AgencyService $service, Builder $query): Builder =>
                        $service->getQueryByAgencies(query: $query)
                    )
                    // ->multiple()
                    // ->selectablePlaceholder(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    // ->required()
                    ->columnSpanFull(),
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
                    ),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->options(DefaultStatusEnum::class)
                    ->default(1)
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->required()
                    ->visibleOn('edit'),
            ])
            ->columns(2)
            ->collapsible();
    }

    protected static function getUsersFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Membros da Equipe'))
            ->description(__('Gerencie os usuários da equipe e atribua papéis.'))
            ->schema([
                Forms\Components\Select::make('coordinators')
                    ->label(__('Coordenador(es)'))
                    ->getSearchResultsUsing(
                        fn(UserService $service, string $search): array =>
                        // 3 - Administrador, 4 - Líder, 5 - Coordenador
                        $service->getUserOptionsBySearch(search: $search, roles: [3, 4, 5]),
                    )
                    ->getOptionLabelsUsing(
                        fn(UserService $service, array $values): array =>
                        $service->getUserOptionLabels(values: $values),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                Forms\Components\Select::make('collaborators')
                    ->label(__('Colaborador(es)'))
                    ->getSearchResultsUsing(
                        fn(UserService $service, string $search): array =>
                        // 6 - Colaborador
                        $service->getUserOptionsBySearch(search: $search, roles: [6]),
                    )
                    ->getOptionLabelsUsing(
                        fn(UserService $service, array $values): array =>
                        $service->getUserOptionLabels(values: $values),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
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
                                        fn(Team $record): string =>
                                        self::getUrl('edit', ['record' => $record]),
                                    )
                                    ->hidden(
                                        fn(): bool =>
                                        !auth()->user()->can('Editar Equipes')
                                    ),
                            ]),
                        Tables\Actions\EditAction::make(),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->before(
                            fn(TeamService $service, Tables\Actions\DeleteAction $action, Team $record) =>
                            $service->preventDeleteIf(action: $action, team: $record)
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
                            fn(TeamService $service, Collection $records) =>
                            $service->deleteBulkAction(records: $records)
                        )
                        ->hidden(
                            fn(): bool =>
                            !auth()->user()->can('Deletar Equipes'),
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
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                ->label('')
                ->collection('avatar')
                ->conversion('thumb')
                ->size(45)
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Equipe'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('agency.name')
                ->label(__('Agência'))
                ->badge()
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('coordinators.name')
                ->label(__('Coordenador(es)'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->searchable(
                    query: fn(TeamService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByStatus(query: $query, search: $search, enumClass: DefaultStatusEnum::class)
                )
                ->sortable(
                    query: fn(TeamService $service, Builder $query, string $direction): Builder =>
                    $service->tableSortByStatus(query: $query, direction: $direction, enumClass: DefaultStatusEnum::class)
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
            Tables\Filters\SelectFilter::make('agencies')
                ->label(__('Agência(s)'))
                ->relationship(
                    name: 'agency',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(Builder $query): Builder =>
                    $query->orderBy('name', 'asc')
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('coordinators')
                ->label(__('Coordenador(es)'))
                ->relationship(
                    name: 'coordinators',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(Builder $query): Builder =>
                    $query->orderBy('name', 'asc')
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->multiple()
                ->options(DefaultStatusEnum::class),
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
                    fn(TeamService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(TeamService $service, mixed $state): ?string =>
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
                    fn(TeamService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(TeamService $service, mixed $state): ?string =>
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
                                    ->label(__('Equipe')),
                                Infolists\Components\TextEntry::make('agency.name')
                                    ->label(__('Agência'))
                                    ->badge(),
                                // Infolists\Components\TextEntry::make('coordinators.name')
                                //     ->label(__('Coordenador(es)'))
                                //     ->visible(
                                //         fn(mixed $state): bool =>
                                //         !empty($state),
                                //     ),
                                // Infolists\Components\TextEntry::make('collaborators.name')
                                //     ->label(__('Colaborador(es)'))
                                //     ->visible(
                                //         fn(mixed $state): bool =>
                                //         !empty($state),
                                //     ),
                                Infolists\Components\TextEntry::make('complement')
                                    ->label(__('Sobre'))
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
                        Infolists\Components\Tabs\Tab::make(__('Coordenadores'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('coordinators')
                                    ->label(__('Coordenador(es)'))
                                    ->hiddenLabel()
                                    ->schema([
                                        Infolists\Components\SpatieMediaLibraryImageEntry::make('avatar')
                                            ->label(__('Avatar'))
                                            ->hiddenLabel()
                                            ->collection('avatar')
                                            ->conversion('thumb')
                                            ->circular()
                                            ->size(45)
                                            ->visible(
                                                fn(mixed $state): bool =>
                                                !empty($state),
                                            ),
                                        Infolists\Components\Grid::make(['default' => 3])
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label(__('Nome'))
                                                    ->helperText(
                                                        fn(User $record): ?string =>
                                                        $record->cpf,
                                                    ),
                                                Infolists\Components\TextEntry::make('email')
                                                    ->label(__('Email')),
                                                Infolists\Components\TextEntry::make('display_main_phone_with_name')
                                                    ->label(__('Telefone'))
                                                    ->visible(
                                                        fn(mixed $state): bool =>
                                                        !empty($state),
                                                    ),
                                            ])
                                            ->columnSpan(9),
                                    ])
                                    ->columns(10)
                                    ->columnSpanFull(),
                            ]),
                        Infolists\Components\Tabs\Tab::make(__('Colaboradores'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('collaborators')
                                    ->label(__('Colaborador(es)'))
                                    ->hiddenLabel()
                                    ->schema([
                                        Infolists\Components\SpatieMediaLibraryImageEntry::make('avatar')
                                            ->label(__('Avatar'))
                                            ->hiddenLabel()
                                            ->collection('avatar')
                                            ->conversion('thumb')
                                            ->circular()
                                            ->size(45)
                                            ->visible(
                                                fn(mixed $state): bool =>
                                                !empty($state),
                                            ),
                                        Infolists\Components\Grid::make(['default' => 3])
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label(__('Nome'))
                                                    ->helperText(
                                                        fn(User $record): ?string =>
                                                        $record->cpf,
                                                    ),
                                                Infolists\Components\TextEntry::make('email')
                                                    ->label(__('Email')),
                                                Infolists\Components\TextEntry::make('display_main_phone_with_name')
                                                    ->label(__('Telefone'))
                                                    ->visible(
                                                        fn(mixed $state): bool =>
                                                        !empty($state),
                                                    ),
                                            ])
                                            ->columnSpan(9),
                                    ])
                                    ->columns(10)
                                    ->columnSpanFull(),
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
                                fn(Team $record): bool =>
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
