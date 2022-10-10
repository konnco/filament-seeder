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
        $this->setupModelFilesystem();

        return collect(Storage::disk('filament-seeder')->allFiles())
            ->map(fn($item) => str($item)->remove(".php")->start("App\\Models\\"))
            ->filter(fn($model) => is_subclass_of((string)$model, Model::class))
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



//    private function getModelRelations($model, $type = "has", $returnType = 1)
//    {
//        $hasMap = [
//            HasMany::class,
//            HasOne::class,
//        ];
//
//        $forMap = [
//            BelongsTo::class
//        ];
//
//        $allMethods = collect((new \ReflectionClass(new $model))->getMethods());
//
//        return $allMethods->filter(function (\ReflectionMethod $method) use ($model, $hasMap, $forMap, $type) {
//            $methodName = $method->getName();
//            $returnTypeName = (new \ReflectionClass(new $model))->getMethod($methodName)->getReturnType()?->getName();
//
//            if ($returnTypeName == null) {
//                return false;
//            }
//
//            if ($type == "has") {
//                return in_array($returnTypeName, $hasMap);
//            }
//
//            return in_array($returnTypeName, $forMap);
//        })->mapWithKeys(function (\ReflectionMethod $method) use ($returnType) {
//            $source = file($method->getFileName());
//            $start_line = $method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
//            $end_line = $method->getEndLine();
//            $length = $end_line - $start_line;
//
//            $body = Str::of(implode("", array_slice($source, $start_line, $length)));
//            $body = preg_split('/\r\n|\r|\n/', $body->toString());
//            $return = collect($body)->filter(fn($line) => Str::contains($line, "return"))->first();
//
//            if ($return == null) {
//                return [];
//            }
//
//            preg_match('#\((.*?)\)#', $return, $match);
//            $modelName = Str::of($match[1])->before('::class')->toString();
//
//            $model = "App\\Models\\" . $modelName;
//
//            if ($returnType == 2) {
//                return [$model => $modelName];
//            }
//
//            return [$method->getName() => $modelName];
//        });
//    }
}