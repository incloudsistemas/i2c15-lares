<?php

namespace App\Observers\System;

use App\Models\System\Team;
use App\Services\Polymorphics\ActivityLogService;

class TeamObserver
{
    /**
     * Handle the Team "created" event.
     */
    public function created(Team $team): void
    {
        //
    }

    /**
     * Handle the Team "updated" event.
     */
    public function updated(Team $team): void
    {
        //
    }

    /**
     * Handle the Team "deleted" event.
     */
    public function deleted(Team $team): void
    {
        $team->load([
            'agency:id,name',
            'coordinators:id,name',
            'collaborators:id,name',
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $team,
            description: "Equipe <b>{$team->name}</b> excluída por <b>" . auth()->user()->name . "</b>"
        );

        $team->slug = $team->slug . '//deleted_' . md5(uniqid());
        $team->save();
    }

    /**
     * Handle the Team "restored" event.
     */
    public function restored(Team $team): void
    {
        //
    }

    /**
     * Handle the Team "force deleted" event.
     */
    public function forceDeleted(Team $team): void
    {
        //
    }
}
