<?php

namespace App\Filament\Tenant\Resources\Crm\Contacts;

use App\Enums\ProfileInfos\UserStatusEnum;
use App\Filament\Tenant\Resources\Crm\Contacts\ContactResource\Pages;
use App\Filament\Tenant\Resources\Crm\Contacts\ContactResource\RelationManagers;
use App\Models\Crm\Contacts\Contact;
use App\Models\Crm\Contacts\Individual;
use App\Models\Crm\Contacts\LegalEntity;
use App\Models\Polymorphics\Address;
use App\Services\Crm\Contacts\ContactService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $slug = 'crm/contacts';

    protected static ?string $modelLabel = 'Contato';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

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
                                        function (Contact $record): string {
                                            if ($record->contactable_type === MorphMapByClass(model: Individual::class)) {
                                                return IndividualResource::getUrl('edit', ['record' => $record->contactable]);
                                            }

                                            return LegalEntityResource::getUrl('edit', ['record' => $record->contactable]);
                                        }
                                    )
                                    ->hidden(
                                        fn(): bool =>
                                        !auth()->user()->can('Editar [CRM] Contatos'),
                                    ),
                            ]),
                        Tables\Actions\EditAction::make()
                            ->label(__('Editar'))
                            ->url(
                                function (Contact $record): string {
                                    if ($record->contactable_type === MorphMapByClass(model: Individual::class)) {
                                        return IndividualResource::getUrl('edit', ['record' => $record->contactable]);
                                    }

                                    return LegalEntityResource::getUrl('edit', ['record' => $record->contactable]);
                                }
                            ),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->label(__('Excluir'))
                        ->before(
                            fn(ContactService $service, Tables\Actions\DeleteAction $action, Contact $record) =>
                            $service->preventDeleteIf(action: $action, contact: $record)
                        )
                        ->action(
                            fn(Contact $record) =>
                            $record->contactable->delete(),
                        )
                        ->after(
                            fn() =>
                            Notification::make()
                                ->success()
                                ->title(__('Excluído'))
                                ->send()
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('create-individual')
                        ->label(__('Criar Pessoa'))
                        ->url(IndividualResource::getUrl('create')),
                    Tables\Actions\Action::make('create-legal-entity')
                        ->label(__('Criar Empresa'))
                        ->url(LegalEntityResource::getUrl('create')),
                ])
                    ->label(__('Criar Contato'))
                    ->icon('heroicon-m-chevron-down')
                    ->color('primary')
                    ->button()
                    ->hidden(
                        fn(): bool =>
                        !auth()->user()->can('Cadastrar [CRM] Contatos')
                    ),
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
            Tables\Columns\SpatieMediaLibraryImageColumn::make('contactable.avatar')
                ->label('')
                ->collection('avatar')
                ->conversion('thumb')
                ->size(45)
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Nome'))
                ->description(
                    fn(Contact $record): ?string =>
                    $record->contactable->cpf ?? $record->contactable->cnpj ?? null,
                )
                ->searchable(
                    query: fn(ContactService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByNameAndContactableCpfOrCnpj(query: $query, search: $search),
                )
                ->sortable(),
            Tables\Columns\TextColumn::make('roles.name')
                ->label(__('Tipo(s)'))
                ->badge()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('email')
                ->label(__('Email'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('display_main_phone')
                ->label(__('Telefone'))
                ->searchable(
                    query: fn(ContactService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByMainPhone(query: $query, search: $search),
                )
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('source.name')
                ->label(__('Origem'))
                ->badge()
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('owner.name')
                ->label(__('Captador'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->searchable(
                    query: fn(ContactService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByStatus(query: $query, search: $search, enumClass: UserStatusEnum::class),
                )
                ->sortable(
                    query: fn(ContactService $service, Builder $query, string $direction): Builder =>
                    $service->tableSortByStatus(query: $query, direction: $direction, enumClass: UserStatusEnum::class),
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
            Tables\Filters\SelectFilter::make('contactable_type')
                ->label(__('Tipo de pessoa'))
                ->options([
                    MorphMapByClass(model: Individual::class)  => 'Pessoas',
                    MorphMapByClass(model: LegalEntity::class) => 'Empresas',
                ])
                ->native(false),
            Tables\Filters\SelectFilter::make('roles')
                ->label(__('Tipo(s)'))
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('sources')
                ->label(__('Origem(s)'))
                ->relationship(
                    name: 'source',
                    titleAttribute: 'name',
                )
                ->multiple()
                ->preload(),
            Tables\Filters\SelectFilter::make('owners')
                ->label(__('Captador(es)'))
                ->relationship(
                    name: 'owner',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(ContactService $service, Builder $query): Builder =>
                    $service->getQueryByElementsWhereHasContactsBasedOnAuthUserRoles(query: $query),
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
                    fn(ContactService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(ContactService $service, mixed $state): ?string =>
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
                    fn(ContactService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data),
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
                                Infolists\Components\TextEntry::make('id')
                                    ->label(__('#ID')),
                                Infolists\Components\SpatieMediaLibraryImageEntry::make('contactable.avatar')
                                    ->label(__('Avatar'))
                                    ->hiddenLabel()
                                    ->collection('avatar')
                                    ->conversion('thumb')
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('Nome')),
                                Infolists\Components\TextEntry::make('roles.name')
                                    ->label(__('Tipo(s)'))
                                    ->badge()
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('email')
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
                                Infolists\Components\TextEntry::make('contactable.cnpj')
                                    ->label(__('CNPJ'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contactable.url')
                                    ->label(__('URL do site'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contactable.cpf')
                                    ->label(__('CPF'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contactable.rg')
                                    ->label(__('RG'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contactable.gender')
                                    ->label(__('Sexo'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('contactable.display_birth_date')
                                    ->label(__('Dt. nascimento'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('source.name')
                                    ->label(__('Origem'))
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('owner.name')
                                    ->label(__('Captador'))
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
                        Infolists\Components\Tabs\Tab::make(__('Endereço(s)'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('contactable.addresses')
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
                                fn(Contact $record): bool =>
                                $record->contactable->addresses?->count() > 0
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
                                fn(Contact $record): bool =>
                                $record->contactable->attachments?->count() > 0
                            ),
                        Infolists\Components\Tabs\Tab::make(__('Histórico de Interações'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('contactable.logActivities')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit'   => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with('contactable')
            ->whereHas('contactable');

        if ($user->hasAnyRole(['Superadministrador', 'Administrador'])) {
            return $query;
        }

        $service = app(ContactService::class);
        $usersIds = $service->getOwnedUsersByAuthUserRolesAgenciesAndTeams(user: $user);

        return $query->whereIn('user_id', $usersIds);
    }
}
