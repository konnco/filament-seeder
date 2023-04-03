<?php

namespace Konnco\FilamentSeeder;

use App\Models\Artist;
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
use Spatie\ModelInfo\ModelInfo;
use Spatie\ModelInfo\Relations\Relation;
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

    private function getModelRelations($model, $type = "has", $returnType = 1)
    {
        $hasMap = [
            HasMany::class,
            HasOne::class,
        ];

        $forMap = [
            BelongsTo::class
        ];

        $relations = ModelInfo::forModel($model)->relations;

        return $relations->filter(function (Relation $relation) use ($model, $hasMap, $forMap, $type) {
            $returnTypeName = $relation->type;

            if ($returnTypeName == null) {
                return false;
            }

            if ($type == "has") {
                return in_array($returnTypeName, $hasMap);
            }

            return in_array($returnTypeName, $forMap);
        })->mapWithKeys(function (Relation $relation) use ($returnType) {
            $model = $relation->related;

            if ($returnType == 2) {
                return [$model => ModelInfo::forModel($model)->fileName];
            }

            return [$relation->name => $relation->name];
        });
    }

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
                        \Artisan::call('migrate:fresh --seed --force');
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
