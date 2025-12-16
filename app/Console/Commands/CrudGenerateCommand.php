<?php

namespace App\Console\Commands;

use Brick\VarExporter\VarExporter;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CrudGenerateCommand extends Command
{
    public static array $integerTypes = [
        'smallint' => ['-32768', '32767'],
        'integer' => ['-2147483648', '2147483647'],
        'bigint' => ['-9223372036854775808', '9223372036854775807'],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate  {name? : Model (singular) for example User} {path? : Class (singular) for example User Api} {table? : Class (singular) for example users} {--module=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRUD generate';

    private ?string $model_name;

    private ?string $controller_path;

    private ?string $table;

    private ?string $module;

    private bool $is_module = false;

    private array $notUse = ['id', 'created_at', 'updated_at', 'deleted_at', 'is_deleted'];

    private array $tableInfo = [];

    private array $namespaces = [];

    /**
     * Execute the console command.
     */
    #[NoReturn]
    public function handle(): void
    {
        $this->setParameters();
        $this->setTableInfo();
        $this->generateModel();
        $this->generateRequest();
        $this->generateRepository();
        $this->generateController();
        $this->writeRoutes();

        $this->info('CRUD generated successfully');
    }

    private function setParameters(): void
    {
        $this->model_name = $this->argument('name');
        $this->controller_path = $this->argument('path');
        $this->table = $this->argument('table');
        $this->module = $this->option('module');
        if (empty($this->model_name)) {
            $this->model_name = text('Model', '0', required: true);
        }
        if (empty($this->controller_path)) {
            $this->controller_path = text('0', default: 'Api/v1');
        }
        if (empty($this->table)) {
            $this->table = text('Table ', default: Str::snake(Str::pluralStudly($this->model_name)), required: true);

        }
        if (empty($this->module)) {
            $confirm = confirm('Modulgami', false);
            if ($confirm == 'yes') {
                $modules = File::json(base_path('modules_statuses.json'));
                $modules = ['no' => true] + $modules;
                $this->module = select('Modules', array_keys($modules), 'no');
            }

        }
        if (!empty($this->module) && $this->module !== 'no') {
            $this->is_module = true;
        }
    }

    public function setTableInfo(): void
    {
        $databaseName = config('database.connections.pgsql.database');
        $tableColumns = collect(DB::select(
            '
            SELECT
                column_name as name,
                data_type as type,
                character_maximum_length as maximum_length,
                is_nullable,
                column_default as default
                FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = ?',
            [$this->table]
        ))->keyBy('name')->toArray();

        $foreignKeys = DB::select("
            SELECT
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                  AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name=? AND tc.table_catalog=?
        ", [$this->table, $databaseName]);

        foreach ($foreignKeys as $foreignKey) {
            $tableColumns[$foreignKey->column_name]->foreign = [
                'table' => $foreignKey->foreign_table_name,
                'id' => $foreignKey->foreign_column_name,
            ];
        }

        foreach ($tableColumns as $key => $column) {
            $type = Str::of($column->type);
            $type = match (true) {
                $type == 'boolean' => 'boolean',
                $type == 'text', $type->contains('char') => 'string',
                $type->contains('int') => 'integer',
                $type->contains('double'),
                $type->contains('decimal'),
                $type->contains('numeric'),
                $type->contains('real') => 'numeric',
                $type == 'date', $type->contains('time ') => 'date',
                $type->contains('json') => 'json',
                default => $column->type,
            };
            $tableColumns[$key]->type = $type;
        }

        $this->tableInfo = $tableColumns;
    }

    private function generateModel(): void
    {
        if ($this->is_module) {
            $namespace = "Modules\\$this->module\Entities";
        } else {
            $namespace = 'App\Models';
        }
        $attributes = array_keys($this->tableInfo);
        $casts = '';
        $usePath = "use Illuminate\Database\Eloquent\Relations\BelongsTo;\n";
        $useTraits = '';
        $relations = '';
        foreach ($this->tableInfo as $column_name => $attribute) {
            if (in_array($column_name, $this->notUse)) {
                continue;
            }

            if ($column_name == 'json' || $column_name == 'jsonb') {
                $casts .= "\n\t\t'$column_name' => 'array',";
            }
            /** create relation functions */
            if (!empty($attribute->foreign)) {
                $foreign = $attribute->foreign;
                $modelName = Str::studly(Str::singular($foreign['table']));
                $functionName = rtrim($column_name, '_id');
                if ($this->is_module and class_exists($namespace . "\\$modelName")) {
                    $usePath .= "use $namespace\\$modelName;\n";
                }
                $relations .= "\n\tpublic function $functionName(): BelongsTo" .
                    "\n\t{\n\t\treturn \$this->belongsTo($modelName::class);\n\t}\n";
            }
        }
        if (in_array('deleted_at', $attributes)) {
            $usePath .= "use Illuminate\Database\Eloquent\SoftDeletes;\n";
            $useTraits .= "\n\tuse SoftDeletes;\n";
        }

        $cast = "\n\tprotected \$casts = [" . $casts . "\n\t];\n";
        $modelTemplate = str_replace(
            [
                '{{namespace}}',
                '{{modelName}}',
                '{{fillable}}',
                '{{table}}',
                '{{casts}}',
                '{{path}}',
                '{{useTraits}}',
                '{{translatableFunctions}}',
                '{{relations}}',
            ],
            [
                $namespace,
                $this->model_name,
                json_encode($attributes, JSON_PRETTY_PRINT),
                $this->table,
                $cast,
                $usePath,
                $useTraits,
                '',
                $relations,
            ],
            $this->getStub('CrudModel')
        );

        if (!empty($this->module) && $this->module !== 'no') {
            $this->namespaces['model'] = "Modules\\$this->module\\Entities\\$this->model_name";
            file_put_contents(base_path("/Modules/$this->module/Entities/$this->model_name.php"), $modelTemplate);
        } else {
            $this->namespaces['model'] = "App\Models";
            file_put_contents(app_path("/Models/$this->model_name.php"), $modelTemplate);
        }
    }

    protected function getStub(string $type): string
    {
        return (string)file_get_contents(base_path("stubs/$type.stub"));
    }

    protected function generateRequest(): void
    {
        $createRules = [];
        $updateRules = [];
        foreach ($this->tableInfo as $column_name => $column) {
            if (in_array($column_name, $this->notUse)) {
                continue;
            }
            $columnRules = [];
            $columnRules[] = $column->is_nullable === 'YES' ? 'nullable' : 'required';

            $type = Str::of($column->type);
            switch (true) {
                case $type == 'boolean':
                    $columnRules[] = 'boolean';

                    break;
                case $type->contains('char'):
                    $columnRules[] = 'string';
                    $columnRules[] = 'max:' . $column->maximum_length;

                    break;
                case $type == 'text':
                    $columnRules[] = 'string';
                    break;
                case $type->contains('int'):
                    $columnRules[] = 'integer';
                    $columnRules[] = 'min:' . self::$integerTypes[$type->__toString()][0];
                    $columnRules[] = 'max:' . self::$integerTypes[$type->__toString()][1];

                    break;
                case $type->contains('double') ||
                    $type->contains('decimal') ||
                    $type->contains('numeric') ||
                    $type->contains('real'):
                    // should we do more specific here?
                    // some kind of regex validation for double, double unsigned, double(8, 2), decimal etc...?
                    $columnRules[] = 'numeric';

                    break;
                case $type == 'date' || $type->contains('time '):
                    $columnRules[] = 'date';

                    break;
                case $type->contains('json'):
                    $columnRules[] = 'json';
                    break;
                default:
                    $columnRules[] = $column->type;
                    break;

            }
            if (!empty($column->foreign)) {
                $columnRules[] = 'exists:' . implode(',', $column->foreign);
            }
            $stringRules = implode('|', $columnRules);
            $createRules[$column_name] = $stringRules;
            $updateRules[$column_name] = Str::replace('required', 'nullable', $stringRules);

        }
        $this->createRequestFile($createRules, 'store');
        $this->createRequestFile($updateRules, 'update');
    }

    private function createRequestFile(array $rules, string $type): void
    {
        $file_name = Str::title($type) . $this->model_name . 'Request';
        $path = "$this->model_name/$file_name";
        Artisan::call('make:request', [
            'name' => $path,
            '--force' => true,
        ]);

        $output = trim(Artisan::output());

        preg_match('/\[(.*?)]/', $output, $matches);

        // The original $file we passed to the command may have changed on creation validation inside the command.
        // We take the actual path which was used to create the file!
        $actualFile = $matches[1] ?? null;
        if ($actualFile && file_exists($actualFile)) {
            try {
                $rules = VarExporter::export($rules, VarExporter::INLINE_SCALAR_LIST);
                $fileContent = File::get($actualFile);

                // Add spaces to indent the array in the request class file.
                $rulesFormatted = str_replace("\n", "\n        ", $rules);
                $pattern = '/(public function rules\(\): array\n\s*{\n\s*return )\[.*](;)/s';
                $replaceContent = preg_replace($pattern, '$1' . $rulesFormatted . '$2', $fileContent);
                File::put($actualFile, $replaceContent);
                if ($this->is_module) {
                    if (!File::isDirectory(base_path("Modules/$this->module/Http/Requests/$this->model_name"))) {
                        File::makeDirectory(base_path("Modules/$this->module/Http/Requests/$this->model_name/"), recursive: true);
                    }
                    $module_path = base_path("Modules/$this->module/Http/Requests/$this->model_name/$file_name.php");
                    File::move($actualFile, $module_path);
                    $fileContent = File::get($module_path);

                    $result = str_replace("App\\Http\\Requests\\$this->model_name", "Modules\\$this->module\\Http\\Requests\\$this->model_name", $fileContent);
                    File::put($module_path, $result);
                    $this->namespaces[$type . '_request'] = "Modules\\$this->module\\Http\\Requests\\$this->model_name";
                } else {
                    $result = str_replace('App\\Http\\Requests\\{{path}};', "App\\Http\\Requests\\$this->model_name;", $replaceContent);
                    File::put($actualFile, $result);
                    $this->namespaces[$type . '_request'] = "App\\Http\\Requests\\$this->model_name";
                }
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
        }
    }

    protected function generateInterface(): void
    {
        $stub = $this->getStub('Interface');

        if ($this->is_module) {
            if (!File::isDirectory(base_path("Modules/$this->module/Http/Interfaces"))) {
                File::makeDirectory(base_path("Modules/$this->module/Http/Interfaces"));
            }

            $namespace = "Modules\\$this->module\\Http\\Interfaces";
            $path = "Modules/$this->module/Http/Interfaces/{$this->model_name}Interface.php";
        } else {
            if (!File::isDirectory(app_path('Http/Interfaces/'))) {
                File::makeDirectory(app_path('Http/Interfaces/'));
            }

            $namespace = "App\Http\Interfaces";
            $path = "app/Http/Interfaces/{$this->model_name}Interface.php";
        }
        $repositoryTemplate = str_replace(['{{modelName}}', '{{paramName}}', '{{namespace}}'], [$this->model_name, Str::snake($this->model_name), $namespace], $stub);
        file_put_contents(base_path($path), $repositoryTemplate);
    }

    public function generateRepository(): void
    {
        $stub = $this->getStub('Repository');

        if ($this->is_module) {
            if (!File::isDirectory(base_path("Modules/$this->module/Http/Repositories"))) {
                File::makeDirectory(base_path("Modules/$this->module/Http/Repositories"));
            }

            $namespace = "Modules\\$this->module\\Http\\Repositories";
            $path = "Modules/$this->module/Http/Repositories/v1/{$this->model_name}Repository.php";
        } else {
            if (!File::isDirectory(app_path('Http/Repositories/v1'))) {
                File::makeDirectory(app_path('Http/Repositories/v1'));
            }

            $namespace = "App\Http\Repositories\\v1";
            $path = "app/Http/Repositories/v1/{$this->model_name}Repository.php";
        }
        $repositoryTemplate = str_replace(['{{modelName}}', '{{paramName}}', '{{namespace}}'], [$this->model_name, lcfirst($this->model_name), $namespace], $stub);
        file_put_contents(base_path($path), $repositoryTemplate);
    }

    protected function generateController(): void
    {
        $paramName = lcfirst($this->model_name);

        $fields = '';
        $response = '';
        $stub = '';
        foreach ($this->tableInfo as $key => $column) {
            $type = $column->type;
            switch ($type) {
                case 'text':
                    $type = 'string';
                    break;
                case 'bigint':
                    $type = 'integer';
                    break;
            }
            if (strtoupper('asd') === 'SWAGGER') {
                if (!in_array($key, $this->notUse)) {
                    $fields .= "\n\t *  \t\t\t@OA\Property(property='$key',type='$type'),";
                    $fields = str_replace("'", '"', $fields);
                    $stub = $this->getStub('ControllerSwagger');

                }
            } else {
                if (!in_array($key, $this->notUse)) {
                    $fields .= "     * @bodyParam $key $type\n";
                }
                $response .= "     *  \"$key\": \"$type\",\n";
                $stub = $this->getStub('Controller');

            }

        }
        $response = trim($response);

        if ($this->is_module) {
            if (!File::isDirectory(base_path("Modules/$this->module/Http/Controllers/" . $this->controller_path))) {
                File::makeDirectory(base_path("Modules/$this->module/Http/Controllers/" . $this->controller_path), recursive: true);
            }
            $controller_path = str_replace('/', '\\', $this->controller_path);

            $namespace = "Modules\\$this->module\\Http\\Controllers\\$controller_path";
            $path = "Modules/$this->module/Http/Controllers/$this->controller_path/{$this->model_name}Controller.php";
        } else {
            if (!File::isDirectory(app_path('Http/Controllers/' . $this->controller_path))) {
                File::makeDirectory(app_path('Http/Controllers/' . $this->controller_path), recursive: true);
            }
            $controller_path = str_replace('/', '\\', $this->controller_path);

            $namespace = "App\\Http\\Controllers\\$controller_path";
            $path = "app/Http/Controllers/$this->controller_path/{$this->model_name}Controller.php";
        }

        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}',
                '{{namespace}}',
                '{{paramName}}',
                '{{routeName}}',
            ],
            [
                $this->model_name,
                strtolower(Str::plural($this->model_name)),
                strtolower($this->model_name),
                $fields,
                $namespace,
                $paramName,
                $this->table,
            ],
            $stub
        );
        $this->namespaces['controller'] = $namespace;
        //
        file_put_contents(base_path($path), $controllerTemplate);
        //        $artisanCall = $documentation === 'SWAGGER' ? 'l5-swagger:generate' : 'scribe:generate';
        //        Artisan::call($artisanCall);
    }

    public function getColumnInfo($table, $column)
    {
        $info = DB::select("SELECT is_nullable, character_maximum_length
                                    FROM information_schema.columns
                                    WHERE table_name = '$table'
                                    AND column_name='$column';");

        return $info[0];
    }

    public function writeRoutes(): void
    {
        $name = $this->model_name;
        $name_upper = Str::upper($name);
        $name_lower = Str::lower($name);
        $namespace = $this->namespaces['controller'];
        $prefix = Str::plural($name_lower);
        $routes = "
Route::prefix('{$prefix}')->group(function () {
    Route::get('/', [{$namespace}\\{$name}Controller::class, 'adminIndex']);
    Route::post('/', [{$namespace}\\{$name}Controller::class, 'store']);
    Route::put('/{{$name_lower}}', [{$namespace}\\{$name}Controller::class, 'update'])->whereNumber('{$name_lower}');
    Route::get('/{{$name_lower}}', [{$namespace}\\{$name}Controller::class, 'show'])->whereNumber('{$name_lower}');
    Route::delete('/{{$name_lower}}', [{$namespace}\\{$name}Controller::class, 'destroy'])->whereNumber('{$name_lower}');
});
Route::prefix('{$prefix}')->group(function () {
    Route::get('/', [{$namespace}\\{$name}Controller::class, 'index']);
    Route::get('/{{$name_lower}}', [{$namespace}\\{$name}Controller::class, 'show'])->whereNumber('{$name_lower}');
});";

        File::append(base_path('routes/api.php'), $routes);
    }
}
