<?php

namespace App\Services\Crm;

use App\Models\Crm\Source;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms;
use Filament\Forms\Set;

class SourceService extends BaseService
{
    public function __construct(protected Source $source)
    {
        parent::__construct();
    }

    public function getQueryBySources(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]); // 1 - Ativo
    }

    public function getOptionsBySources(): array
    {
        return $this->source->byStatuses(statuses: [1]) // 1 - Ativo
            ->pluck('name', 'id')
            ->toArray();
    }

    public function quickCreateActionBySources(
        string $field,
        bool $multiple = false
    ): Forms\Components\Actions\Action {
        return Forms\Components\Actions\Action::make($field)
            ->label(__('Criar Origem do Contato'))
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\Grid::make(['default' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nome'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(
                function (array $data, Set $set, mixed $state) use ($field, $multiple): void {
                    $source = $this->source->create($data);

                    if ($multiple) {
                        $values = is_array($state) ? $state : [];
                        $set($field, array_merge($values, [$source->id]));
                    } else {
                        $set($field, $source->id);
                    }
                }
            );
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Source $source): void
    {
        $title = __('Ação proibida: Exclusão de origem');

        if ($this->isAssignedToContacts(source: $source)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Esta origem possui contatos associados. Para excluir, você deve primeiro desvincular todos os contatos que estão associados a ela.'))
                ->send();

            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $source) {
            if ($this->isAssignedToContacts(source: $source)) {
                $blocked[] = $source->name;
                continue;
            }

            $allowed[] = $source;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('As seguintes origens não podem ser excluídas: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Algumas origens não puderam ser excluídas'))
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

    protected function isAssignedToContacts(Source $source): bool
    {
        return $source->contacts()
            ->exists();
    }
}
