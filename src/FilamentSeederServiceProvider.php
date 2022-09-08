<?php

namespace Konnco\FilamentSeeder;

use Artisan;
use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use RyanChandler\FilamentTools\Tool;
use RyanChandler\FilamentTools\ToolInput;
use RyanChandler\FilamentTools\Tools;

class FilamentSeederServiceProvider extends PluginServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-seeder')
            ->hasConfigFile();
    }

    public function boot()
    {
        $this->registerFactoryTools();
        $this->registerResetDatabaseTools();

        Tools::navigationIcon(config('filament-seeder.icon'));
        Tools::navigationGroup(config('filament-seeder.group'));
    }

    private function registerFactoryTools(){
        Tools::register(function (Tool $tool): Tool {
            return $tool
                ->label('Data Factory')
                ->schema([
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            Select::make('data')
                                ->label('Model')
                                ->options(FilamentSeeder::make()->getModelHasFactories())
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('amount')->label('Record Count')
                                ->required(),
                        ]),
                ])
                ->onSubmit(function (ToolInput $input) {
                    $data = new $input['data'];
                    $amount = $input['amount'];

                    $data->factory()->count((int) $amount, 1)->create();
                    $input->notify('success', 'Data berhasil dibuat!');
                })
                ->submitButtonLabel('Buat')
                ->columnSpan(8);
        });
    }

    private function registerResetDatabaseTools(){
        Tools::register(function (Tool $tool): Tool {
            return $tool->label('Danger! Reset Database')
                ->onSubmit(function (ToolInput $input) {
                    Artisan::call('migrate:fresh --seed');
                    $input->notify('success', 'Database has been reset!');
                })
                ->submitButtonLabel('Danger! Please Reset Carefully')
                ->columnSpan(8);
        });
    }
}
