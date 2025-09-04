<?php

namespace App\Services\Crm\Contacts;

use App\Models\Crm\Contacts\Contact;
use App\Models\Crm\Contacts\LegalEntity;
use App\Services\BaseService;
use App\Services\Crm\SourceService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Support;

class LegalEntityService extends BaseService
{
    public function __construct(protected Contact $contact, protected LegalEntity $legalEntity)
    {
        parent::__construct();
    }

    public function validateCnpj(?LegalEntity $legalEntity, string $attribute, mixed $state, Closure $fail): void
    {
        $userId = auth()->user()->id;

        if ($legalEntity) {
            $userId = $legalEntity->contact->user_id;
        }

        $exists = $this->legalEntity->where('cnpj', $state)
            ->whereHas('contact', function (Builder $query) use ($userId): Builder {
                return $query->where('user_id', $userId);
            })
            ->when($legalEntity, function (Builder $query) use ($legalEntity): Builder {
                return $query->where('id', '<>', $legalEntity->id);
            })
            ->first();

        if ($exists) {
            $fail(__('O valor informado para o campo cnpj já está em uso.', ['attribute' => $attribute]));
        }
    }

    public function getLegalEntityOptionsBySearch(?string $search, string $returnKey = 'legalEntityId'): array
    {
        $user = auth()->user();

        return $this->legalEntity->with('contact')
            ->whereHas('contact', function (Builder $query) use ($user, $search): Builder {
                $query->where('status', 1) // 1 - Ativo
                    ->when(!empty($search), function (Builder $query) use ($search): Builder {
                        return $query->where(function (Builder $query) use ($search): Builder {
                            return $query->where('name', 'like', "%{$search}%");
                        });
                    });

                if ($user->hasAnyRole(['Superadministrador', 'Administrador'])) {
                    return $query;
                }

                return $query->where('user_id', $user->id);
            })
            ->when(!empty($search), function (Builder $query) use ($search): Builder {
                return $query->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') LIKE ?",
                        ["%{$search}%"]
                    );
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(function (LegalEntity $legalEntity) use ($returnKey): array {
                $label = $legalEntity->contact->name . ($legalEntity->cnpj ? " - {$legalEntity->cnpj}" : '');

                $key = $returnKey === 'contactId' ? $legalEntity->contact->id : $legalEntity->id;

                return [$key => $label];
            })
            ->toArray();
    }

    // Single
    public function getLegalEntityOptionLabel(?string $value): string
    {
        return $this->legalEntity->find($value)?->contact->name;
    }

    // Multiple
    public function getLegalEntityOptionLabels(array $values, $returnKey = 'legalEntityId'): array
    {
        return $this->legalEntity->whereIn('id', $values)
            ->get()
            ->mapWithKeys(function (LegalEntity $legalEntity) use ($returnKey): array {
                $key = $returnKey === 'contactId' ? $legalEntity->contact->id : $legalEntity->id;

                return [$key => $legalEntity->contact->name];
            })
            ->toArray();
    }

    public function quickCreateActionByContactLegalEntities(
        string $field,
        bool $multiple = false,
        string $returnKey = 'legalEntityId'
    ): Forms\Components\Actions\Action {
        return Forms\Components\Actions\Action::make($field)
            ->label(__('Criar Empresa'))
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\Grid::make(['default' => 2])
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
                                function (ContactService $service): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($service): void {
                                        $contactableType = MorphMapByClass(model: LegalEntity::class);

                                        $service->validateEmail(
                                            contact: null,
                                            contactableType: $contactableType,
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
                                function (ContactService $service): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ) use ($service): void {
                                        $contactableType = MorphMapByClass(model: LegalEntity::class);

                                        $service->validatePhone(
                                            contact: null,
                                            contactableType: $contactableType,
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail
                                        );
                                    };
                                },
                            ])
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cnpj')
                            ->label(__('CNPJ'))
                            ->mask('99.999.999/9999-99')
                            ->rules([
                                function (): Closure {
                                    return function (
                                        string $attribute,
                                        mixed $state,
                                        Closure $fail
                                    ): void {
                                        $this->validateCnpj(
                                            legalEntity: null,
                                            attribute: $attribute,
                                            state: $state,
                                            fail: $fail
                                        );
                                    };
                                },
                            ])
                            ->maxLength(255),
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
                function (array $data, Set $set, mixed $state) use ($field, $multiple, $returnKey): void {
                    $legalEntity = $this->legalEntity->create($data);

                    $data['contact']['user_id'] = auth()->user()->id;

                    $contact = $legalEntity->contact()
                        ->create($data['contact']);

                    $contact->roles()
                        ->sync($data['contact']['roles']);

                    $key = $returnKey === 'contactId' ? $contact->id : $legalEntity->id;

                    if ($multiple) {
                        $values = is_array($state) ? $state : [];
                        $set($field, array_merge($values, [$key]));
                    } else {
                        $set($field, $key);
                    }
                }
            );
    }
}
