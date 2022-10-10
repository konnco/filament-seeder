<?php

namespace Konnco\FilamentSeeder;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Storage;
use Str;

class SeederPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament-seeder::seeders';

    protected static ?string $slug = "seeders";

    protected static ?string $title = "Seeder";

    public array $data = [
        'count' => 1
    ];

    public function scanModelHasFactories(): Collection
    {
        config(['filesystems.disks.filament-seeder' => [
            'driver' => 'local',
            'root' => app_path('Models'),
        ]]);

        $scannedModels = collect(Storage::disk('filament-seeder')->allFiles())
            ->map(fn($item) => Str::of($item)->replace(".php", '')->start("App\\Models\\"))
            ->filter(fn($model) => is_subclass_of((string)$model, Model::class))
            ->filter(fn($model) => method_exists((string)$model, 'factory'));

        $excludedConfigModels = collect(config('filament-seeder.excludes', []));

        $finalModels = collect(array_merge(
            array_diff($scannedModels->toArray(), $excludedConfigModels->toArray()),
            array_diff($excludedConfigModels->toArray(), $scannedModels->toArray()),
        ));

        return $finalModels
            ->map(function ($item) {
                $name = Str::of($item)->explode("\\")->last();
                $name = Str::of($name)->headline()->plural();
                return [
                    'label' => config('filament-seeder.nicknames.' . $item, $name),
                    'value' => $item
                ];
            })->sort();
    }

    /**
     * Return all model that has factories
     */
    public function getModelHasFactories(): array
    {
        $models = $this->scanModelHasFactories();
        return $models->pluck('label', 'value')->toArray();
    }

    private function getModelRelations($model, $type = "has", $returnType = 1)
    {
        $hasMap = [
            HasMany::class,
            HasOne::class,
        ];

        $forMap = [
            BelongsTo::class
        ];

        $allMethods = collect((new \ReflectionClass(new $model))->getMethods());

        return $allMethods->filter(function (\ReflectionMethod $method) use ($model, $hasMap, $forMap, $type) {
            $methodName = $method->getName();
            $returnTypeName = (new \ReflectionClass(new $model))->getMethod($methodName)->getReturnType()?->getName();

            if ($returnTypeName == null) {
                return false;
            }

            if ($type == "has") {
                return in_array($returnTypeName, $hasMap);
            }

            return in_array($returnTypeName, $forMap);
        })->mapWithKeys(function (\ReflectionMethod $method) use ($returnType) {
            $source = file($method->getFileName());
            $start_line = $method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
            $end_line = $method->getEndLine();
            $length = $end_line - $start_line;

            $body = Str::of(implode("", array_slice($source, $start_line, $length)));
            $body = preg_split('/\r\n|\r|\n/', $body->toString());
            $return = collect($body)->filter(fn($line) => Str::contains($line, "return"))->first();

            if ($return == null) {
                return [];
            }

            preg_match('#\((.*?)\)#', $return, $match);
            $modelName = Str::of($match[1])->before('::class')->toString();

            $model = "App\\Models\\" . $modelName;

            if ($returnType == 2) {
                return [$model => $modelName];
            }

            return [$method->getName() => $modelName];
        });
    }

    // need to refactor after this done

    protected function getActions(): array
    {
        return [
            Action::make('reset-db')
                ->label("Reset Database")
                ->color('danger')
                ->icon("heroicon-o-trash")
                ->form([
                    TextInput::make('confirm')
                        ->label('Type *confirm* to reset database'),
                ])
                ->action(function ($data) {
                    if ($data['confirm'] == "confirm") {
                        \Artisan::call('migrate:fresh --seed');
                        $this->notify("success", "Database has been reset");
                        return;
                    }

                    $this->notify("warning", "Failed to reset database");
                })
                ->requiresConfirmation()
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function submit()
    {
        $model = $this->data['model'];

        $factory = $model::factory();

        foreach (@$this->data['states'] ?? [] as $state => $status) {
            if ($status) {
                $factory = $factory->{$state}();
            }
        }

        if (@$this->data['has_relation'] ?? false) {
            foreach ($this->data['relations'] as $relation) {
                if ($relation['type'] == 'has') {
                    $method = "has" . str($relation['related_model'])->ucfirst();
                    $factory = $factory->{$method}($relation['count']);
                    continue;
                }

                $factory = $factory->for($relation['related_model']::find($relation['for']));
            }
        }

        $factory
            ->count($this->data['count'])
            ->create();

        $this->notify("success", "Records has been created!");
    }

    public function modelStates(): array
    {
        if (!@$this->data['model']) {
            return [];
        }

        $factory = Factory::factoryForModel($this->data['model']);

        return collect((new \ReflectionClass(new $factory))->getMethods())
            ->filter(fn(\ReflectionMethod $method) => $method->class == $factory::class)
            ->filter(fn(\ReflectionMethod $method) => $method->getName() != "definition")
            ->map(function (\ReflectionMethod $method) {
                return Forms\Components\Checkbox::make('states.' . $method->getName())
                    ->label(str($method->getName())->headline());
            })->toArray();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    TextInput::make('count')
                        ->default(1)
                        ->numeric()
                        ->label(__('filament-seeder::seed.make')),
                    Forms\Components\Select::make('model')
                        ->options(FilamentSeeder::models()
                            ->pluck('label', 'value')
                            ->toArray())
                        ->label(__('filament-seeder::seed.source'))
                        ->required()
                        ->columnSpan(5)
                        ->reactive(),
                ])
                ->columns(6),
            Forms\Components\Grid::make()
                ->schema($this->modelStates())
                ->columns(3),
            Forms\Components\Checkbox::make('has_relation')
                ->label(__('filament-seeder::seed.has_relation'))
                ->reactive(),
            Forms\Components\Repeater::make('relations')
                ->label(__('filament-seeder::seed.relations'))
                ->createItemButtonLabel(__('filament-seeder::seed.add_relation'))
                ->defaultItems(1)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options([
                            'has' => __('filament-seeder::seed.has'),
                            'for' => __('filament-seeder::seed.for')
                        ])
                        ->reactive(),
                    TextInput::make('count')
                        ->default(1)
                        ->numeric()
                        ->label(__('filament-seeder::seed.count'))
                        ->visible(fn($get) => $get('type') == 'has'),
                    Forms\Components\Select::make('related_model')
                        ->label(__('filament-seeder::seed.related_model'))
                        ->options(function (\Closure $get) {
                            return $this->getModelRelations(($get('data.model', true)), $get('type'), $get('type') == 'has' ? 1 : 2);
                        }),
                    Forms\Components\Select::make('for')
                        ->label(__('filament-seeder::seed.for'))
                        ->options(function ($get) {
                            if ($get('related_model') == null) {
                                return [];
                            }
                            return $get('related_model')::get()->pluck('name', 'id');
                        })
                        ->searchable()
                        ->visible(fn($get) => $get('type') == 'for')
                ])
                ->reactive()
                ->disableItemMovement()
                ->columns(3)
                ->hidden(fn($get) => $get('has_relation') == false)
        ];
    }
}
