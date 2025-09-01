<?php

namespace App\Services\System;

use App\Models\System\TenantCategory;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TenantCategoryService extends BaseService
{
    public function __construct(protected TenantCategory $tenantCategory)
    {
        parent::__construct();
    }

    public function tableFilterByFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function getQueryByTenantCategories(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]); // 1 - Ativo
    }

    public function getOptionsByTenantCategories(): array
    {
        return $this->tenantCategory->byStatuses(statuses: [1]) // 1 - Ativo
            ->pluck('name', 'id')
            ->toArray();
    }

    public function quickCreateActionByTenantCategories(
        string $field,
        bool $multiple = false
    ): Forms\Components\Actions\Action {
        return Forms\Components\Actions\Action::make($field)
            ->label(__('Criar Categoria'))
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\Grid::make(['default' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nome'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->live(debounce: 1000)
                            ->afterStateUpdated(
                                fn(Set $set, mixed $state): ?string =>
                                $set('slug', Str::slug($state))
                            ),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->unique(TenantCategory::class, 'slug', ignoreRecord: true)
                            ->maxLength(255),
                    ]),
            ])
            ->action(
                function (array $data, Set $set, mixed $state) use ($field, $multiple): void {
                    $category = TenantCategory::create($data);

                    if ($multiple) {
                        array_push($state, $category->id);
                        $set($field, $state);
                    } else {
                        $set($field, $category->id);
                    }
                }
            );
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, TenantCategory $tenantCategory): void
    {
        $title = __('Ação proibida: Exclusão de categoria');

        if ($this->isAssignedToTenantAccounts(tenantCategory: $tenantCategory)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Esta categoria possui contas associadas. Para excluir, você deve primeiro desvincular todas as contas que estão associados a ela.'))
                ->send();

            // $action->cancel();
            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $tenantCategory) {
            if ($this->isAssignedToTenantAccounts(tenantCategory: $tenantCategory)) {
                $blocked[] = $tenantCategory->name;
                continue;
            }

            $allowed[] = $tenantCategory;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('As seguintes categorias não podem ser excluídas: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Algumas categorias não puderam ser excluídas'))
                ->warning()
                ->body($message)
                ->send();
        }

        collect($allowed)->each->delete();

        if (!empty($allowed)) {
            Notification::make()
                ->title(__('Excluído'))
                ->success()
                ->send();
        }
    }

    protected function isAssignedToTenantAccounts(TenantCategory $tenantCategory): bool
    {
        return $tenantCategory->tenantAccounts()
            ->exists();
    }
}
