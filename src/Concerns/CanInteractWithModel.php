<?php
/*
 * Copyright (c) 2022 Konnco Studio. Got any issue?, Don't hesitate to send email to hello@konnco.com
 */

namespace Konnco\FilamentSeeder\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\ModelInfo\ModelFinder;

trait CanInteractWithModel
{
    /**
     * setup model scan directory
     *
     * @return void
     */
    protected function setupModelFilesystem(): void
    {
        config(['filesystems.disks.filament-seeder' => [
            'driver' => 'local',
            'root' => app_path('Models'),
        ]]);
    }

    /**
     * Get excepted models from config
     *
     * @return Collection
     */
    protected function getExceptedModelFromConfig(): Collection
    {
        return collect(config('filament-seeder.excludes', []));
    }

    /**
     * Get all models from factories
     *
     * @return Collection
     */
    protected function factoredModels(): Collection
    {
        return collect(ModelFinder::all())
            ->filter(fn($model) => method_exists((string)$model, 'factory'));
    }

    /**
     * Clean models without exception from config
     *
     * @param $models
     * @param $excepts
     * @return Collection
     */
    public function cleanUpModels($models, $excepts): Collection
    {
        return collect(array_merge(
            array_diff($models->toArray(), $excepts->toArray()),
            array_diff($excepts->toArray(), $models->toArray()),
        ));
    }

    /**
     * Get all model that has factories
     *
     * @return Collection
     */
    public function getModels(): Collection
    {
        $factoredModels = $this->factoredModels();

        $exceptModels = $this->getExceptedModelFromConfig();

        $models = $this->cleanUpModels(models: $factoredModels, excepts: $exceptModels);

        return $models
            ->map(function ($item) {
                return [
                    'label' => str($item)
                        ->afterLast("\\")
                        ->headline()
                        ->plural(),
                    'value' => $item
                ];
            })->sort();
    }
}
