<?php

namespace App\Services\Polymorphics;

use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaService extends BaseService
{
    public function __construct(protected Media $media, protected ActivityLogService $logService)
    {
        parent::__construct();
    }

    public function mutateFormDataToCreate(Model $ownerRecord, array $data): array
    {
        $data['model_type'] = MorphMapByClass(model: $ownerRecord::class);
        $data['model_id'] = $ownerRecord->id;

        return $data;
    }

    public function createAction(Model $ownerRecord, array $data): Model
    {
        return DB::transaction(function () use ($data, $ownerRecord): Model {
            foreach ($data['attachments'] as $attachment) {
                $filePath = Storage::disk('public')
                    ->path($attachment);

                $media = $ownerRecord->addMedia($filePath)
                    ->usingName($data['name'] ?? basename($attachment))
                    ->toMediaCollection($data['collection_name'] ?? 'attachments');

                $this->logService->logOwnerRecordRelationCreatedActivity(
                    ownerRecord: $ownerRecord,
                    currentRecord: $media,
                    description: $this->getActivityLogDescription(
                        media: $media,
                        event: 'created'
                    )
                );
            }

            return $ownerRecord;
        });
    }

    public function mutateFormDataToEdit(Model $ownerRecord, Media $media, array $data): array
    {
        $data['_old_record'] = $media->replicate()
            ->getAttributes();

        return $data;
    }

    public function afterEditAction(Model $ownerRecord, Media $media, array $data): void
    {
        $this->logService->logOwnerRecordRelationUpdatedActivity(
            ownerRecord: $ownerRecord,
            currentRecord: $media,
            oldRecord: $data['_old_record'],
            description: $this->getActivityLogDescription(
                media: $media,
                event: 'updated'
            )
        );
    }

    public function afterDeleteAction(Model $ownerRecord, Media $media): void
    {
        $this->logService->logOwnerRecordRelationDeletedActivity(
            ownerRecord: $ownerRecord,
            oldRecord: $media,
            description: $this->getActivityLogDescription(
                media: $media,
                event: 'deleted'
            )
        );
    }

    public function afterDeleteBulkAction(Model $ownerRecord, Collection $records): void
    {
        foreach ($records as $media) {
            $this->logService->logOwnerRecordRelationDeletedActivity(
                ownerRecord: $ownerRecord,
                oldRecord: $media,
                description: $this->getActivityLogDescription(
                    media: $media,
                    event: 'deleted'
                )
            );
        }
    }

    protected function getActivityLogDescription(Media $media, string $event): string
    {
        $user = auth()->user();

        return match ($event) {
            'updated' => "Anexo <b>{$media->name}</b> atualizado por <b>{$user->name}</b>",
            'deleted' => "Anexo <b>{$media->name}</b> excluído por <b>{$user->name}</b>",
            default   => "Novo anexo <b>{$media->name}</b> cadastrado por <b>{$user->name}</b>",
        };
    }
}
