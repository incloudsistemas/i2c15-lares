<?php

namespace App\Filament\Resources\System;

use App\Enums\DefaultStatusEnum;
use App\Filament\Resources\System\TenantPlanResource\Pages;
use App\Filament\Resources\System\TenantPlanResource\RelationManagers;
use App\Models\System\TenantPlan;
use App\Services\System\TenantPlanService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Support;
use Illuminate\Database\Eloquent\Collection;

class TenantPlanResource extends Resource
{
    protected static ?string $model = TenantPlan::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Plano';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationParentItem = 'Contas de Clientes';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

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
                        Forms\Components\Tabs\Tab::make(__('Recursos do Plano'))
                            ->schema([
                                static::getResourcesPlanFormSection(),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('Configs. do Plano'))
                            ->schema([
                                static::getConfigPlanFormSection(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function getGeneralInfosFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Infos. Gerais'))
            ->description(__('Visão geral e informações fundamentais sobre o plano.'))
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
                Forms\Components\Textarea::make('complement')
                    ->label(__('Sobre'))
                    ->rows(4)
                    ->minLength(2)
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Fieldset::make('Preços')
                    ->schema([
                        Forms\Components\TextInput::make('monthly_price')
                            ->label(__('Preço mensal'))
                            // ->numeric()
                            ->prefix('R$')
                            ->mask(
                                Support\RawJs::make(<<<'JS'
                                    $money($input, ',')
                                JS)
                            )
                            ->placeholder('0,00')
                            ->maxValue(42949672.95),
                        Forms\Components\TextInput::make('monthly_price_notes')
                            ->label(__('Observações do preço mensal'))
                            ->minLength(2)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('annual_price')
                            ->label(__('Preço anual'))
                            // ->numeric()
                            ->prefix('R$')
                            ->mask(
                                Support\RawJs::make(<<<'JS'
                                    $money($input, ',')
                                JS)
                            )
                            ->placeholder('0,00')
                            ->maxValue(42949672.95),
                        Forms\Components\TextInput::make('annual_price_notes')
                            ->label(__('Observações do preço anual'))
                            ->minLength(2)
                            ->maxLength(255),
                        Forms\Components\Toggle::make('best_benefit_cost')
                            ->label(__('Melhor custo benefício?'))
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2),
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

    protected static function getResourcesPlanFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Recursos do Plano'))
            ->description(__('Descrição geral da lista de recursos que contemplam o plano.'))
            ->schema([
                Forms\Components\Repeater::make('features')
                    ->hiddenLabel()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nome do recurso'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(
                        fn(mixed $state): ?string =>
                        $state['name'] ?? null
                    )
                    ->addActionLabel(__('Adicionar recurso'))
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
            ]);
    }

    protected static function getConfigPlanFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('Configs. do Plano'))
            ->description(__('Personalize o plano com os recursos desejados.'))
            ->schema([
                Forms\Components\CheckboxList::make('settings')
                    ->hiddenLabel()
                    ->options([
                        'feature_01' => 'Feature 01',
                        'feature_02' => 'Feature 02',
                        'feature_03' => 'Feature 03',
                        'feature_04' => 'Feature 04',
                    ])
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(4)
                    ->gridDirection('row')
                    ->live(),
            ])
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
                                        fn(TenantPlan $record): string =>
                                        self::getUrl('edit', ['record' => $record]),
                                    )
                                    ->hidden(
                                        fn(): bool =>
                                        !auth()->user()->can('Editar Planos')
                                    ),
                            ]),
                        Tables\Actions\EditAction::make(),
                    ])
                        ->dropdown(false),
                    Tables\Actions\DeleteAction::make()
                        ->before(
                            fn(TenantPlanService $service, Tables\Actions\DeleteAction $action, TenantPlan $record) =>
                            $service->preventDeleteIf(action: $action, tenantPlan: $record)
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
                            fn(TenantPlanService $service, Collection $records) =>
                            $service->deleteBulkAction(records: $records)
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
            Tables\Columns\TextColumn::make('name')
                ->label(__('Nome'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('monthly_price')
                ->label(__('Preço mensal R$'))
                ->formatStateUsing(
                    fn(mixed $state): ?string =>
                    $state ? number_format($state, 2, ',', '.') : null,
                )
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('annual_price')
                ->label(__('Preço anual R$'))
                ->formatStateUsing(
                    fn(mixed $state): ?string =>
                    $state ? number_format($state, 2, ',', '.') : null,
                )
                ->description(
                    fn(TenantPlan $record): ?string =>
                    $record->display_annual_discount_margin
                        ? 'economia de ' . $record->display_annual_discount_margin
                        : null,
                )
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\IconColumn::make('best_benefit_cost')
                ->label(__('Melhor custo benefício?'))
                ->boolean()
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->searchable(
                    query: fn(TenantPlanService $service, Builder $query, string $search): Builder =>
                    $service->tableSearchByStatus(query: $query, search: $search)
                )
                ->sortable(
                    query: fn(TenantPlanService $service, Builder $query, string $direction): Builder =>
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
                    fn(TenantPlanService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByCreatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(TenantPlanService $service, mixed $state): ?string =>
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
                    fn(TenantPlanService $service, Builder $query, array $data): Builder =>
                    $service->tableFilterByUpdatedAt(query: $query, data: $data)
                )
                ->indicateUsing(
                    fn(TenantPlanService $service, mixed $state): ?string =>
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
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('Nome')),
                                Infolists\Components\TextEntry::make('display_monthly_price')
                                    ->label(__('Preço mensal R$'))
                                    ->helperText(
                                        fn(TenantPlan $record): ?string =>
                                        $record->monthly_price_notes,
                                    )
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\TextEntry::make('display_annual_price')
                                    ->label(__('Preço anual R$'))
                                    ->state(
                                        function (TenantPlan $record): string {
                                            $annualPrice = $record->display_annual_price;
                                            $discountMargin = $record->display_annual_discount_margin
                                                ? " (economia de {$record->display_annual_discount_margin})"
                                                : '';

                                            return $annualPrice . $discountMargin;
                                        }
                                    )
                                    ->helperText(
                                        fn(TenantPlan $record): ?string =>
                                        $record->monthly_annual_notes,
                                    )
                                    ->visible(
                                        fn(mixed $state): bool =>
                                        !empty($state),
                                    ),
                                Infolists\Components\IconEntry::make('best_benefit_cost')
                                    ->label(__('Melhor custo benefício?')),
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
                        Infolists\Components\Tabs\Tab::make(__('Recursos do Plano'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('features')
                                    ->label(__('Recursos do plano'))
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->hiddenLabel(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->visible(
                                fn(TenantPlan $record): bool =>
                                $record->features && count($record->features) > 0
                            ),
                        Infolists\Components\Tabs\Tab::make(__('Configs. do Plano'))
                            ->schema([
                                Infolists\Components\TextEntry::make('settings')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->columnSpanFull(),
                            ])
                            ->visible(
                                fn(TenantPlan $record): bool =>
                                $record->settings && count($record->settings) > 0
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
            'index'  => Pages\ListTenantPlans::route('/'),
            'create' => Pages\CreateTenantPlan::route('/create'),
            'edit'   => Pages\EditTenantPlan::route('/{record}/edit'),
        ];
    }
}
