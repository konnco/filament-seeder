<?php

namespace Konnco\FilamentSeeder;

use Illuminate\Support\Collection;

class FilamentSeeder
{
    use Concerns\CanInteractWithModel;
    use Concerns\CanListingModelMethod;
    use Concerns\CanInteractWithMethodAndClass;

    public static function make(): self
    {
        return new self();
    }

    public static function models(): Collection
    {
        self::make()->setupCanListingModelMethod();

        return self::make()->getModels();
    }
}
