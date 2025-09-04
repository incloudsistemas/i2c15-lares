<?php

namespace App\Services\Crm\Contacts;

use App\Enums\ProfileInfos\UserStatusEnum;
use App\Models\Crm\Contacts\Contact;
use App\Models\Crm\Contacts\Individual;
use App\Models\Crm\Contacts\LegalEntity;
use App\Models\System\User;
use App\Services\BaseService;
use App\Services\Crm\SourceService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Support;

class ContactService extends BaseService
{
    protected string $contactTable;
    protected string $individualContactable;
    protected string $legalEntityContactable;

    public function __construct(
        protected Contact $contact,
        protected Individual $individual,
        protected LegalEntity $legalEntity
    ) {
        parent::__construct();

        $this->contactTable = $contact->getTable();

        $this->individualContactable = MorphMapByClass(model: get_class($this->individual));
        $this->legalEntityContactable = MorphMapByClass(model: get_class($this->legalEntity));
    }

    public function tableSearchByNameAndContactableCpfOrCnpj(Builder $query, string $search): Builder
    {
        return $query->whereHas('contactable', function (Builder $query) use ($search): Builder {
            $morphKey = MorphMapByClass(model: $query->getModel()::class);

            return $query->when(
                $morphKey === $this->individualContactable,
                function (Builder $query) use ($search): Builder {
                    return $query->where('cpf', 'like', "%{$search}%")
                        ->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') LIKE ?",
                            ["%{$search}%"]
                        );
                }
            )
                ->when(
                    $morphKey === $this->legalEntityContactable,
                    function (Builder $query) use ($search): Builder {
                        return $query->where('cnpj', 'like', "%{$search}%")
                            ->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') LIKE ?",
                                ["%{$search}%"]
                            );
                    }
                );
        })
            ->orWhere('name', 'like', "%{$search}%");
    }

    public function tableSearchByContactNameAndCpfOrCnpj(Builder $query, string $search): Builder
    {
        return $query->whereHas('contact', function (Builder $query) use ($search): Builder {
            return $query->where('name', 'like', "%{$search}%");
        })
            ->orWhere(function (Builder $query) use ($search): Builder {
                $morphKey = MorphMapByClass(model: $query->getModel()::class);

                return $query->when(
                    $morphKey === $this->individualContactable,
                    function (Builder $query) use ($search): Builder {
                        return $query->where('cpf', 'like', "%{$search}%")
                            ->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') LIKE ?",
                                ["%{$search}%"]
                            );
                    }
                )
                    ->when(
                        $morphKey === $this->legalEntityContactable,
                        function (Builder $query) use ($search): Builder {
                            return $query->where('cnpj', 'like', "%{$search}%")
                                ->orWhereRaw(
                                    "REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') LIKE ?",
                                    ["%{$search}%"]
                                );
                        }
                    );
            });
    }

    public function tableSearchByMainPhone(Builder $query, string $search): Builder
    {
        return $query->whereRaw("JSON_EXTRACT(phones, '$[0].number') LIKE ?", ["%$search%"]);
    }

    public function tableSearchByContactMainPhone(Builder $query, string $search): Builder
    {
        return $query->whereHas('contact', function (Builder $query) use ($search): Builder {
            return $query->whereRaw("JSON_EXTRACT(phones, '$[0].number') LIKE ?", ["%$search%"]);
        });
    }

    public function tableSearchByContactStatus(Builder $query, string $search): Builder
    {
        $statuses = UserStatusEnum::getAssociativeArray();

        $matchingStatuses = [];
        foreach ($statuses as $index => $status) {
            if (stripos($status, $search) !== false) {
                $matchingStatuses[] = $index;
            }
        }

        if ($matchingStatuses) {
            return $query->whereHas('contact', function (Builder $query) use ($matchingStatuses): Builder {
                return $query->whereIn('status', $matchingStatuses);
            });
        }

        return $query;
    }

    public function tableSortByContactStatus(Builder $query, string $direction): Builder
    {
        $contactableType = MorphMapByClass(model: $query->getModel()::class);
        $statuses = UserStatusEnum::getAssociativeArray();

        $caseParts = [];
        $bindings = [];

        foreach ($statuses as $key => $status) {
            $caseParts[] = "WHEN (SELECT status FROM {$this->contactTable} WHERE {$this->contactTable}.contactable_type = '{$contactableType}' AND {$this->contactTable}.contactable_id = {$contactableType}.id) = ? THEN ?";
            $bindings[] = $key;
            $bindings[] = $status;
        }

        $orderByCase = "CASE " . implode(' ', $caseParts) . " END";

        return $query->selectRaw("*, ({$orderByCase}) as display_status", $bindings)
            ->orderBy('display_status', $direction);
    }

    public function getQueryByElementsWhereHasContactsBasedOnAuthUserRoles(Builder $query): Builder
    {
        $user = auth()->user();

        $query = $query->with('contacts');

        if ($user->hasAnyRole(['Superadministrador', 'Administrador'])) {
            return $query->whereHas('contacts');
        }

        $usersIds = $this->getOwnedUsersByAuthUserRolesAgenciesAndTeams(user: $user);

        return $query->whereHas('contacts', fn(Builder $query): Builder => $query->whereIn('user_id', $usersIds));
    }

    public function tableFilterByContactStatuses(Builder $query, array $data): Builder
    {
        if (!$data['values'] || empty($data['values'])) {
            return $query;
        }

        return $query->whereHas('contact', function (Builder $query) use ($data): Builder {
            return $query->whereIn('status', $data['values']);
        });
    }

    public function tableFilterByContactCreatedAt(Builder $query, array $data): Builder
    {
        return $query->when(
            $data['created_from'],
            fn(Builder $query, $date): Builder =>
            $query->whereHas('contact', function (Builder $query) use ($date): Builder {
                return $query->whereDate('created_at', '>=', $date);
            }),
        )
            ->when(
                $data['created_until'],
                fn(Builder $query, $date): Builder =>
                $query->whereHas('contact', function (Builder $query) use ($date): Builder {
                    return $query->whereDate('created_at', '<=', $date);
                }),
            );
    }

    public function tableFilterByContactUpdatedAt(Builder $query, array $data): Builder
    {
        return $query->when(
            $data['updated_from'],
            fn(Builder $query, $date): Builder =>
            $query->whereHas('contact', function (Builder $query) use ($date): Builder {
                return $query->whereDate('updated_at', '>=', $date);
            }),
        )
            ->when(
                $data['updated_until'],
                fn(Builder $query, $date): Builder =>
                $query->whereHas('contact', function (Builder $query) use ($date): Builder {
                    return $query->whereDate('updated_at', '<=', $date);
                }),
            );
    }

    public function validateEmail(
        ?Contact $contact,
        string $contactableType,
        string $attribute,
        mixed $state,
        Closure $fail
    ): void {
        $userId = auth()->user()->id;

        if ($contact) {
            $userId = $contact->user_id;
        }

        $exists = $this->contact->where('email', $state)
            ->where('user_id', $userId)
            ->where('contactable_type', $contactableType)
            ->when($contact, function (Builder $query) use ($contact): Builder {
                return $query->where('contactable_id', '<>', $contact->contactable_id);
            })
            ->first();

        if ($exists) {
            $fail(__('O valor informado para o campo email já está em uso.', ['attribute' => $attribute]));
        }
    }

    public function validatePhone(
        ?Contact $contact,
        string $contactableType,
        string $attribute,
        mixed $state,
        Closure $fail
    ): void {
        $userId = auth()->user()->id;

        if ($contact) {
            $userId = $contact->user_id;
        }

        $exists = $this->contact->whereRaw("JSON_EXTRACT(phones, '$[0].number') = ?", ["$state"])
            ->where('user_id', $userId)
            ->where('contactable_type', $contactableType)
            ->when($contact, function (Builder $query) use ($contact): Builder {
                return $query->where('contactable_id', '<>', $contact->contactable_id);
            })
            ->first();

        if ($exists) {
            $fail(__('O valor informado para o campo telefone já está em uso.', ['attribute' => $attribute]));
        }
    }

    public function getContactOptionsBySearch(?string $search): array
    {
        $user = auth()->user();

        $query = $this->contact->with('contactable')
            ->byStatuses(statuses: [1]) // 1 - Ativo
            ->where(function (Builder $query) use ($search): Builder {
                return $query->whereHas('contactable', function (Builder $query) use ($search): Builder {
                    return $query->when(!empty($search), function (Builder $query) use ($search): Builder {
                        $morphKey = MorphMapByClass(model: $query->getModel()::class);

                        return $query->when(
                            $morphKey === $this->individualContactable,
                            function (Builder $query) use ($search): Builder {
                                return $query->where('cpf', 'like', "%{$search}%")
                                    ->orWhereRaw(
                                        "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') LIKE ?",
                                        ["%{$search}%"]
                                    );
                            }
                        )
                            ->when(
                                $morphKey === $this->legalEntityContactable,
                                function (Builder $query) use ($search): Builder {
                                    return $query->where('cnpj', 'like', "%{$search}%")
                                        ->orWhereRaw(
                                            "REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') LIKE ?",
                                            ["%{$search}%"]
                                        );
                                }
                            );
                    });
                })
                    ->when(!empty($search), function (Builder $query) use ($search): Builder {
                        return $query->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });

        if (!$user->hasAnyRole(['Superadministrador', 'Administrador'])) {
            $usersIds = $this->getOwnedUsersByAuthUserRolesAgenciesAndTeams(user: $user);
            $query->whereIn('user_id', $usersIds);
        }

        return $query->limit(50)
            ->get()
            ->mapWithKeys(function (Contact $contact): array {
                $cpfCnpj = $contact->contactable->cpf ?? $contact->contactable->cnpj;
                $label = $contact->name . (!empty($cpfCnpj) ? " - {$cpfCnpj}" : '');

                return [$contact->id => $label];
            })
            ->toArray();
    }

    // Single
    public function getContactOptionLabel(?string $value): string
    {
        return $this->contact->find($value)?->name;
    }

    // Multiple
    public function getContactOptionLabels(array $values): array
    {
        return $this->contact->whereIn('id', $values)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function quickCreateActionByContacts(string $field, bool $multiple = false): Forms\Components\Actions\Action
    {
        return Forms\Components\Actions\Action::make($field)
            ->label(__('Criar Contato'))
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\Grid::make(['default' => 2])
                    ->schema([
                        Forms\Components\Radio::make('contactable_type')
                            ->label(__('Tipo de contato'))
                            ->options([
                                $this->individualContactable  => 'P. Física',
                                $this->legalEntityContactable => 'P. Jurídica',
                            ])
                            ->default($this->individualContactable)
                            ->inline()
                            ->inlineLabel(false)
                            ->required()
                            ->live()
                            ->columnSpanFull(),
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
                                function (callable $get): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($get): void {
                                        $this->validateEmail(
                                            contact: null,
                                            contactableType: $get('contactable_type'),
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail
                                        );
                                    };
                                },
                            ])
                            // ->required()
                            ->maxLength(255),
                        Forms\Components\Hidden::make('contact.phones.0.name')
                            ->default(null),
                        Forms\Components\TextInput::make('contact.phones.0.number')
                            ->label(__('Nº do telefone'))
                            ->mask(
                                Support\RawJs::make(<<<'JS'
                                    $input.length === 14 ? '(99) 9999-9999' : '(99) 99999-9999'
                                JS)
                            )
                            ->rules([
                                function (callable $get): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($get): void {
                                        $this->validatePhone(
                                            contact: null,
                                            contactableType: $get('contactable_type'),
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail
                                        );
                                    };
                                },
                            ])
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cpf')
                            ->label(__('CPF'))
                            ->mask('999.999.999-99')
                            ->rules([
                                function (IndividualService $service): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($service): void {
                                        $service->validateCpf(
                                            individual: null,
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail
                                        );
                                    };
                                },
                            ])
                            ->maxLength(255)
                            ->visible(
                                fn(callable $get): bool =>
                                $get('contactable_type') === $this->individualContactable
                            ),
                        Forms\Components\TextInput::make('cnpj')
                            ->label(__('CNPJ'))
                            ->mask('99.999.999/9999-99')
                            ->rules([
                                function (LegalEntityService $service): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($service): void {
                                        $service->validateCnpj(
                                            legalEntity: null,
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail
                                        );
                                    };
                                },
                            ])
                            ->maxLength(255)
                            ->visible(
                                fn(callable $get): bool =>
                                $get('contactable_type') === $this->legalEntityContactable
                            ),
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
                    ]),
            ])
            ->action(
                function (array $data, Set $set, mixed $state) use ($field, $multiple): void {
                    if ($data['contactable_type'] === $this->individualContactable) {
                        $contactable = $this->individual->create($data);
                    } elseif ($data['contactable_type'] === $this->legalEntityContactable) {
                        $contactable = $this->legalEntity->create($data);
                    }

                    $data['contact']['user_id'] = auth()->user()->id;

                    $contact = $contactable->contact()
                        ->create($data['contact']);

                    $contact->roles()
                        ->sync($data['contact']['roles']);

                    $key = $contact->id;

                    if ($multiple) {
                        $values = is_array($state) ? $state : [];
                        $set($field, array_merge($values, [$key]));
                    } else {
                        $set($field, $key);
                    }
                }
            );
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Contact $contact): void
    {
        $title = __('Ação proibida: Exclusão de contato');

        // if ($this->isAssignedToBusiness(contact: $contact)) {
        //     Notification::make()
        //         ->title($title)
        //         ->warning()
        //         ->body(__('Este contato possui negócios associados. Para excluir, você deve primeiro desvincular todos os negócios que estão associados a ele.'))
        //         ->send();

        //     $action->halt();
        // }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $record) {
            $contact = $record instanceof Contact ? $record : $record->contact;

            if (
                !$this->checkOwnerAccess(contact: $contact)
                // || $this->isAssignedToBusiness(contact: $contact)
            ) {
                $blocked[] = $contact->name;
                continue;
            }

            $allowed[] = $contact;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('Os seguintes contatos não podem ser excluídos: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Alguns contatos não puderam ser excluídos'))
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

    public function checkOwnerAccess(?User $user = null, Contact $contact): bool
    {
        $user = $user ?? auth()->user();

        if ($user->hasAnyRole(['Superadministrador', 'Administrador'])) {
            return true;
        }

        if ($contact->user_id === $user->id) {
            return true;
        }

        $usersIds = $this->getOwnedUsersByAuthUserRolesAgenciesAndTeams(user: $user);

        return in_array($contact->user_id, $usersIds);
    }

    protected function isAssignedToBusiness(Contact $contact): bool
    {
        return $contact->business()
            ->exists();
    }
}
