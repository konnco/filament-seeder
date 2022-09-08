<?php

namespace Konnco\FilamentSeeder;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;
// use RyanChandler\FilamentTools\Tools;
// use RyanChandler\FilamentTools\Tool;
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
        $package->name('filament-seeder');
    }

    public function boot()
    {
        $this->registerFactoryTools();
        // Tools::register(function (Tool $tool): Tool {
        //     return $tool->label('Clear Cache');
        // });

        // Tools::register(function (Tool $tool): Tool {
        //     return $tool
        //         ->label('Data Faker')
        //         ->schema([
        //             Grid::make()
        //                 ->columns(3)
        //                 ->schema([
        //                     Select::make('data')
        //                         ->options([
        //                             Product::class => 'Produk',
        //                             ProductCategory::class => 'Kategori Produk',
        //                             BlogPost::class => 'Blog',
        //                             Order::class => 'Order',
        //                         ])
        //                         ->required()
        //                         ->columnSpan(2),
        //                     TextInput::make('qty')->label('Jumlah')
        //                         ->required(),
        //                 ]),
        //         ])
        //         ->onSubmit(function (ToolInput $input) {
        //             $data = new $input['data'];
        //             $qty = $input['qty'];

        //             $data->factory()->count((int) $qty, 1)->create();
        //             $input->notify('success', 'Data berhasil dibuat!');
        //         })
        //         ->submitButtonLabel('Buat')
        //         ->columnSpan(8);
        // });

        // Tools::register(function (Tool $tool): Tool {
        //     return $tool->label('Reset Database')
        //         ->onSubmit(function (ToolInput $input) {
        //             Artisan::call('migrate:fresh --seed');
        //             $input->notify('success', 'Data berhasil direset!');
        //         })
        //         ->submitButtonLabel('Reset')
        //         ->columnSpan(8);
        // });

        // Tools::navigationIcon('heroicon-o-adjustments');
        // Tools::navigationGroup('Pengaturan');
    }

    private function registerFactoryTools(){
        Tools::register(function (Tool $tool): Tool {
            return $tool
                ->label('Data Faker')
                ->schema([
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            Select::make('data')
                                ->options([
                                    Product::class => 'Produk',
                                    ProductCategory::class => 'Kategori Produk',
                                    BlogPost::class => 'Blog',
                                    Order::class => 'Order',
                                ])
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('qty')->label('Jumlah')
                                ->required(),
                        ]),
                ])
                ->onSubmit(function (ToolInput $input) {
                    $data = new $input['data'];
                    $qty = $input['qty'];

                    $data->factory()->count((int) $qty, 1)->create();
                    $input->notify('success', 'Data berhasil dibuat!');
                })
                ->submitButtonLabel('Buat')
                ->columnSpan(8);
        });
    }
}
