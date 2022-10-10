<?php
/*
 * Copyright (c) 2022 Konnco Studio. Got any issue?, Don't hesitate to send email to hello@konnco.com
 */

namespace Konnco\FilamentSeeder\Concerns;

use Illuminate\Support\Facades\Cache;
use Konnco\FilamentSeeder\Enums\RelationType;
use ReflectionClass;
use Illuminate\Support\Str;
use ReflectionException;

trait CanListingModelMethod
{
    protected function setupCanListingModelMethod(): mixed
    {
        return Cache::remember("filament-seed-models", 120, function () {
            $this->factoredModels()
                ->mapWithKeys(fn($model) => $this->mapModelMethods($model));
        });
    }

    public function getMethodScript(\ReflectionMethod $method, $asArray = false): array|string
    {
        $source = file($method->getFileName());
        $start_line = $method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        $body = Str::of(implode("", array_slice($source, $start_line, $length)));
        if (!$asArray) {
            return $body->toString();
        }

        return preg_split('/\r\n|\r|\n/', $body->toString());
    }

    /**
     * @throws ReflectionException
     */
    protected function mapModelMethods(string $model): array
    {
        $modelMethods = collect((new ReflectionClass(new $model))->getMethods());

        $result = $modelMethods->filter(function (\ReflectionMethod $method) use ($model) {
            if ($method->class != $model) {
                return false;
            }

            $bodyScript = $this->getMethodScript($method);
            return str($bodyScript)->contains(['hasMany', 'hasOne', 'belongsTo']);
        })->map(function (\ReflectionMethod $method) {
            $type = str($this->getMethodScript($method))->contains("hasMany", "hasOne") ? RelationType::Has : RelationType::For;

            return [
                'name' => $method->getName(),
                'reflectionMethod' => $method,
                'type' => $type,
                'class' => str($this->getMethodScript($method))
                    ->after("return")
                    ->before(";")
                    ->before(",")
                    ->before(")")
                    ->after("(")
                    ->start("App\\Models\\")
                    ->toString()
            ];
        });

        return [(string)$model => $result];
    }

    public function methodFor($model, $type): \Illuminate\Support\Collection
    {
        $methods = collect($this->setupCanListingModelMethod());
    }
}