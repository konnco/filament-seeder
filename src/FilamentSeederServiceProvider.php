<?php

namespace Konnco\FilamentSeeder;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FilamentSeederServiceProvider extends PluginServiceProvider
{
    protected array $pages = [
        SeederPage::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package->name('filament-seeder')
            ->hasViews()
            ->hasConfigFile()
            ->hasTranslations();
    }
}
