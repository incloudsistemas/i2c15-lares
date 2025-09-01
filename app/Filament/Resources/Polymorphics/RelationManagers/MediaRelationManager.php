<?php

namespace App\Filament\Resources\Polymorphics\RelationManagers;

use App\Services\Polymorphics\MediaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Anexos';

    protected static ?string $modelLabel = 'Anexo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('attachments')
                    ->label(__('Anexar arquivo(s)'))
                    ->helperText(__('Máx. 10 arqs. // 5 mb.'))
                    ->directory('attachments')
                    ->multiple(
                        fn(string $operation): bool =>
                        $operation === 'create'
                    )
                    ->reorderable()
                    ->appendFiles()
                    ->downloadable()
                    ->required()
                    ->maxSize(5120)
                    ->maxFiles(10)
                    // ->panelLayout('grid')
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file, Get $get): string =>
                        (string) str('-' . md5(uniqid()) . '-' . time() . '.' . $file->guessExtension())
                            ->prepend(Str::slug($get('name'))),
                    )
                    ->hidden(
                        fn(string $operation): bool =>
                        $operation === 'edit'
                    )
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(
                fn(Builder $query): Builder =>
                $query->where('collection_name', 'attachments')
            )
            ->striped()
            ->columns(static::getTableColumns())
            ->reorderable('order_column')
            ->defaultSort(column: 'order_column', direction: 'asc')
            ->filters(static::getTableFilters(), layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(
                        fn(MediaService $service, array $data): array =>
                        $service->mutateFormDataToCreate(ownerRecord: $this->ownerRecord, data: $data)
                    )
                    ->using(
                        fn(MediaService $service, array $data): Model =>
                        $service->createAction(data: $data, ownerRecord: $this->ownerRecord),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make()
                            ->extraModalFooterActions([
                                Tables\Actions\Action::make('download')
                                    ->label(__('Download'))
                                    ->button()
                                    ->action(
                                        fn(Media $record) =>
                                        response()->download($record->getPath(), $record->file_name)
                                    ),
                            ]),
                        Tables\Actions\EditAction::make()
                            ->mutateFormDataUsing(
                                fn(MediaService $service, Media $record, array $data): array =>
                                $service->mutateFormDataToEdit(
                                    ownerRecord: $this->ownerRecord,
                                    media: $record,
                                    data: $data
                                ),
                            )
                            ->after(
                                function (MediaService $service, Media $record, array $data) {
                                    $service->afterEditAction(
                                        ownerRecord: $this->ownerRecord,
                                        media: $record,
                                        data: $data
                                    );
                                }
                            ),
                        Tables\Actions\Action::make('download')
                            ->icon('heroicon-s-arrow-down-tray')
                            ->action(
                                fn(Media $record) =>
                                response()->download($record->getPath(), $record->file_name)
                            ),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->after(
                            fn(MediaService $service, Media $record) =>
                            $service->afterDeleteAction(ownerRecord: $this->ownerRecord, media: $record)
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
                        ->after(
                            fn(MediaService $service, Collection $records) =>
                            $service->afterDeleteBulkAction(ownerRecord: $this->ownerRecord, records: $records)
                        ),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#ID'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Nome'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('mime_type')
                ->label(__('Mime'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('size')
                ->label(__('Tamanho'))
                ->state(
                    fn(Media $record): string =>
                    AbbrNumberFormat($record->size),
                )
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('order_column')
                ->label(__('Ordem'))
                ->sortable()
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
                    fn(MediaService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(MediaService $service, mixed $state): ?string =>
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
                    fn(MediaService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(MediaService $service, mixed $state): ?string =>
                    $service->tableFilterIndicateUsingByUpdatedAt(data: $state),
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('id')
                    ->label(__('#ID')),
                Infolists\Components\TextEntry::make('name')
                    ->label(__('Nome'))
                    ->helperText(
                        fn(Media $record): string =>
                        $record->file_name
                    ),
                Infolists\Components\TextEntry::make('mime_type')
                    ->label(__('Mime')),
                Infolists\Components\TextEntry::make('size')
                    ->label(__('Tamanho'))
                    ->state(
                        fn(Media $record): string =>
                        AbbrNumberFormat($record->size),
                    ),
                Infolists\Components\TextEntry::make('order_column')
                    ->label(__('Ordem')),
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

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // $ownerRecord->getTable();

        return true;
    }
}
