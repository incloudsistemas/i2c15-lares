<?php

namespace App\Filament\Resources\Polymorphics\RelationManagers;

use App\Enums\ProfileInfos\UfEnum;
use App\Models\Polymorphics\Address;
use App\Services\Polymorphics\AddressService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Endereços';

    protected static ?string $modelLabel = 'Endereço';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Tipo de endereço'))
                    ->helperText(__('Nome identificador. Ex: Casa, Trabalho...'))
                    ->datalist([
                        'Casa',
                        'Trabalho',
                        'Outros'
                    ])
                    ->autocomplete(false)
                    ->maxLength(255),
                Forms\Components\TextInput::make('zipcode')
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
                Forms\Components\Select::make('uf')
                    ->label(__('Estado'))
                    ->options(UfEnum::class)
                    ->searchable()
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('city')
                    ->label(__('Cidade'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('district')
                    ->label(__('Bairro'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('address_line')
                    ->label(__('Endereço'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('number')
                    ->label(__('Número'))
                    ->minLength(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('complement')
                    ->label(__('Complemento'))
                    ->helperText(__('Apto / Bloco / Casa'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference')
                    ->label(__('Ponto de referência'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_main')
                    ->label(__('Utilizar como endereço principal'))
                    ->default(
                        fn(): bool =>
                        $this->ownerRecord->addresses->count() === 0
                    )
                    ->accepted(
                        fn(): bool =>
                        $this->ownerRecord->addresses->count() === 0
                    )
                    ->disabled(
                        fn(?Address $record): bool =>
                        isset($record) && $record->is_main === true
                    )
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(
                fn(Address $record): string =>
                $record->display_full_address
            )
            ->striped()
            ->columns(static::getTableColumns())
            ->defaultSort(
                fn(AddressService $service, Builder $query): Builder =>
                $service->tableDefaultSort(query: $query),
            )
            ->filters(static::getTableFilters(), layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->before($this->setUniqueMainAddressCallback())
                    ->after(
                        function (AddressService $service, Address $record, array $data) {
                            $service->afterCreateAction(
                                ownerRecord: $this->ownerRecord,
                                address: $record,
                                data: $data
                            );
                        }
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make()
                            ->mutateFormDataUsing(
                                fn(AddressService $service, Address $record, array $data): array =>
                                $service->mutateFormDataToEdit(
                                    ownerRecord: $this->ownerRecord,
                                    address: $record,
                                    data: $data
                                ),
                            )
                            ->before($this->setUniqueMainAddressCallback())
                            ->after(
                                function (AddressService $service, Address $record, array $data) {
                                    $service->afterEditAction(
                                        ownerRecord: $this->ownerRecord,
                                        address: $record,
                                        data: $data
                                    );
                                }
                            ),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->before(
                            fn(AddressService $service, Tables\Actions\DeleteAction $action, Address $record) =>
                            $service->preventDeleteIf(action: $action, address: $record, ownerRecord: $this->ownerRecord),
                        )
                        ->after(
                            fn(AddressService $service, Address $record) =>
                            $service->afterDeleteAction(ownerRecord: $this->ownerRecord, address: $record)
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
                            fn(AddressService $service, Collection $records) =>
                            $service->deleteBulkAction(records: $records, ownerRecord: $this->ownerRecord)
                        )
                        ->after(
                            fn(AddressService $service, Collection $records) =>
                            $service->afterDeleteBulkAction(ownerRecord: $this->ownerRecord, records: $records)
                        ),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->before($this->setUniqueMainAddressCallback()),
            ]);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(__('Tipo'))
                ->badge()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('display_short_address')
                ->label(__('Endereço')),
            Tables\Columns\TextColumn::make('zipcode')
                ->label(__('CEP'))
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('city')
                ->label(__('Cidade/Uf'))
                ->formatStateUsing(
                    fn(Address $record): string =>
                    "{$record->city}-{$record->uf->name}"
                )
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\IconColumn::make('is_main')
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

    protected function getTableFilters(): array
    {
        return [
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
                    fn(AddressService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(AddressService $service, mixed $state): ?string =>
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
                    fn(AddressService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(AddressService $service, mixed $state): ?string =>
                    $service->tableFilterIndicateUsingByUpdatedAt(data: $state),
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('name')
                    ->label(__('Tipo de endereço'))
                    ->badge()
                    ->visible(
                        fn(mixed $state): bool =>
                        !empty($state),
                    ),
                Infolists\Components\TextEntry::make('zipcode')
                    ->label(__('CEP')),
                Infolists\Components\TextEntry::make('city')
                    ->label(__('Cidade/Uf'))
                    ->formatStateUsing(
                        fn(Address $record): string =>
                        "{$record->city}-{$record->uf->name}"
                    ),
                Infolists\Components\TextEntry::make('district')
                    ->label(__('Bairro'))
                    ->visible(
                        fn(mixed $state): bool =>
                        !empty($state),
                    ),
                Infolists\Components\TextEntry::make('address_line')
                    ->label(__('Endereço'))
                    ->visible(
                        fn(mixed $state): bool =>
                        !empty($state),
                    ),
                Infolists\Components\TextEntry::make('number')
                    ->label(__('Número'))
                    ->visible(
                        fn(mixed $state): bool =>
                        !empty($state),
                    ),
                Infolists\Components\TextEntry::make('complement')
                    ->label(__('Complemento'))
                    ->visible(
                        fn(mixed $state): bool =>
                        !empty($state),
                    ),
                Infolists\Components\TextEntry::make('reference')
                    ->label(__('Ponto de referência'))
                    ->visible(
                        fn(mixed $state): bool =>
                        !empty($state),
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
                Infolists\Components\Grid::make(['default' => 3])
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Cadastro'))
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('Últ. atualização'))
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ])
            ->columns(3);
    }

    private function setUniqueMainAddressCallback(): Closure
    {
        return function (AddressService $service, array $data, ?Address $record): void {
            $service->setUniqueMainAddress(data: $data, address: $record, ownerRecord: $this->ownerRecord);
        };
    }
}
