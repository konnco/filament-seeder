<?php
/*
 * Copyright (c) 2022 Konnco Studio. Got any issue?, Don't hesitate to send email to hello@konnco.com
 */

namespace Konnco\FilamentSeeder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Konnco\FilamentSeeder\Enums\RelationType;
use ReflectionClass;
use Illuminate\Support\Str;
use ReflectionException;
use Spatie\ModelInfo\ModelInfo;
use Spatie\ModelInfo\Relations\Relation;

trait CanListingModelMethod
{
    protected function setupCanListingModelMethod(): mixed
    {
        return $this->factoredModels()
            ->mapWithKeys(fn($model) => $this->mapModelMethods($model));
    }

    protected function mapModelMethods(string $model): array
    {
        $relations = ModelInfo::forModel($model)->relations;
        $result = $relations->map(function (Relation $relation) {
            $type = str($relation->type)->contains("hasMany", "hasOne") ? RelationType::Has : RelationType::For;

            return [
                'name' => $relation->name,
                'type' => $type,
                'class' => $relation->related
            ];
        });

        return [(string)$model => $result];
    }
}
