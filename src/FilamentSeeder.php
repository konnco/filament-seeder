<?php
namespace Konnco\FilamentSeeder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Storage;
use Str;

class FilamentSeeder {
    public static function make(){
        return new self;
    }

    public function scanModelHasFactories(){
        config(['filesystems.disks.filament-seeder' => [
            'driver' => 'local',
            'root' => app_path('Models'),
        ]]);

        $scannedModels = collect(Storage::disk('filament-seeder')->allFiles())
                    ->map(fn($item)=>Str::of($item)->replace(".php",'')->start("App\\Models\\"))
                    ->filter(fn($model)=>is_subclass_of((string) $model, Model::class))
                    ->filter(fn($model)=>in_array(HasFactory::class, class_uses_recursive((string) $model)));

        $excludedConfigModels = collect(config('filament-seeder.excludes', []));

        $finalModels = collect(array_merge(
                                array_diff($scannedModels->toArray(), $excludedConfigModels->toArray()),
                                array_diff($excludedConfigModels->toArray(), $scannedModels->toArray()),
                        ));

        return $finalModels
                        ->map(function($item){
                            return [
                                'label' => config('filament-seeder.nicknames.'.$item, (string) $item),
                                'value' => $item
                            ];
                        })->sort();
    }

    /**
     * Return all model that has factories
     */
    public function getModelHasFactories():array
    {
        $models = $this->scanModelHasFactories();
        return $models->pluck('label', 'value')->toArray();
    }
}
