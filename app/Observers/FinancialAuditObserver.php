<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinancialAuditObserver
{
    protected function getModelName(Model $model): string
    {
        return class_basename($model);
    }

    protected function getUserIdentifier(): string
    {
        return Auth::check() ? (Auth::user()->email ?? 'sistema') : 'sistema';
    }

    public function created(Model $model): void
    {
        Log::info('[Auditoria] Criado', [
            'model' => $this->getModelName($model),
            'id' => $model->getKey(),
            'user' => $this->getUserIdentifier(),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        Log::info('[Auditoria] Atualizado', [
            'model' => $this->getModelName($model),
            'id' => $model->getKey(),
            'changes' => $changes,
            'user' => $this->getUserIdentifier(),
        ]);
    }

    public function deleted(Model $model): void
    {
        Log::info("[Auditoria] Exclu\u00eddo", [
            'model' => $this->getModelName($model),
            'id' => $model->getKey(),
            'user' => $this->getUserIdentifier(),
        ]);
    }

    public function restored(Model $model): void
    {
        Log::info('[Auditoria] Restaurado', [
            'model' => $this->getModelName($model),
            'id' => $model->getKey(),
            'user' => $this->getUserIdentifier(),
        ]);
    }

    public function forceDeleted(Model $model): void
    {
        Log::info("[Auditoria] Exclu\u00eddo permanentemente", [
            'model' => $this->getModelName($model),
            'id' => $model->getKey(),
            'user' => $this->getUserIdentifier(),
        ]);
    }
}
