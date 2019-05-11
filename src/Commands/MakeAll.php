<?php

namespace Mont4\LaravelMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAll extends Command
{
	const PATHS = [
		'namespace'      => [
			'controller'     => 'App\Http\Controllers\%route_kind%\%namespace%',
			'request_update' => 'App\Http\Requests\%route_kind%\%namespace%\%name%',
			'request_store'  => 'App\Http\Requests\%route_kind%\%namespace%\%name%',
			'model'          => 'App\Models\%namespace%',
			'filter'         => 'App\Filters\%namespace%',
			'resource_index' => 'App\Http\Resources\%route_kind%\%namespace%\%name%',
			'resource_show'  => 'App\Http\Resources\%route_kind%\%namespace%\%name%',
			'resource_list'  => 'App\Http\Resources\%route_kind%\%namespace%\%name%',
			'policy'         => 'App\Policies\%namespace%',
		],
		'full_namespace' => [
			'controller'     => 'App\Http\Controllers\%route_kind%\%namespace%\%name%Controller',
			'request_update' => 'App\Http\Requests\%route_kind%\%namespace%\%name%\Update%name%Request',
			'request_store'  => 'App\Http\Requests\%route_kind%\%namespace%\%name%\Store%name%Request',
			'model'          => 'App\Models\%namespace%\%name%',
			'filter'         => 'App\Filters\%namespace%\%name%Filter',
			'resource_index' => 'App\Http\Resources\%route_kind%\%namespace%\%name%\%name%IndexResource',
			'resource_show'  => 'App\Http\Resources\%route_kind%\%namespace%\%name%\%name%ShowResource',
			'resource_list'  => 'App\Http\Resources\%route_kind%\%namespace%\%name%\%name%ListResource',
			'policy'         => 'App\Policies\%namespace%\%name%Policy',
		],
		'file_path'      => [
			'controller'     => '%route_kind%/%namespace%/%name%Controller',
			'request_update' => '%route_kind%/%namespace%/%name%/Update%name%Request',
			'request_store'  => '%route_kind%/%namespace%/%name%/Store%name%Request',
			'model'          => 'Models/%namespace%/%name%',
			'migration'      => 'create%plural_name%_table',
			'filter'         => 'Filters/%namespace%/%name%Filter',
			'factory'        => '%name%Factory',
			'resource_index' => '%route_kind%/%namespace%/%name%/%name%IndexResource',
			'resource_show'  => '%route_kind%/%namespace%/%name%/%name%ShowResource',
			'resource_list'  => '%route_kind%/%namespace%/%name%/%name%ListResource',
			'policy'         => '%namespace%/%name%Policy',
			'seed'           => '%namespace%_%name%Seeder',
			'fake_seed'      => 'Fake_%namespace%_%name%Seeder',
			'test'           => '%route_kind%/%namespace%/%name%Test',
		],
		'full_file_path' => [
			'controller'     => 'app/Http/Controllers/%route_kind%/%namespace%/%name%Controller.php',
			'request_update' => 'app/Http/Requests/%route_kind%/%namespace%/%name%/Update%name%Request.php',
			'request_store'  => 'app/Http/Requests/%route_kind%/%namespace%/%name%/Store%name%Request.php',
			'model'          => 'app/Models/%namespace%/%name%.php',
			'filter'         => 'app/Filters/%namespace%/%name%Filter.php', // TODO. with route kind or without
			'factory'        => '%name%Factory.php',
			'resource_index' => 'app/Http/Resources/%route_kind%/%namespace%/%name%/%name%IndexResource.php',
			'resource_show'  => 'app/Http/Resources/%route_kind%/%namespace%/%name%/%name%ShowResource.php',
			'resource_list'  => 'app/Http/Resources/%route_kind%/%namespace%/%name%/%name%ListResource.php',
			'policy'         => 'app/Policies/%namespace%/%name%Policy.php',
			'seed'           => '%namespace%_%name%Seeder.php',
			'fake_seed'      => 'Fake_%namespace%_%name%Seeder.php',
			'test'           => '%route_kind%/%namespace%/%name%Test.php',
		],
	];

	const STUBS = [
		'model',
		'filter',
		'request_store',
		'request_update',
		'resource_index',
		'resource_show',
		'resource_list',
		'policy',
		'controller',
	];

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'make:all';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	private $routeKind = '';
	private $namespace = '';
	private $model     = '';
	private $super     = false;

	private $paths;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$this->getInformation(); // make method
		$this->generatePaths(); // make method
		$this->generateByLaravel();
		$this->makeTranslate(); // make method
		$this->makePermissionTranslate(); // make method
		$this->makePermissionList(); // make method
		$this->generateStub();
	}

	private function getInformation() :void
	{
		$this->routeKind = Str::ucfirst(Str::camel($this->choice('Please enter or choose the route kind?', config('laravel_maker.route_kinds'), config('laravel_maker.route_kind_default'))));

		$this->namespace = Str::ucfirst(Str::camel(($this->anticipate('Please enter or choose the namespace?', $this->getCurrentNamespace($this->routeKind)))));

		$this->model = Str::ucfirst(Str::camel($this->ask('Please enter name?')));

		$this->super = $this->confirm("Is it super ?");

		$this->info("------------------------------------------------------------------------");
		$this->info("\t\t\tRoute kind: '<fg=red>$this->routeKind</>'");
		$this->info("\t\t\t Namespace: '<fg=red>$this->namespace</>'");
		$this->info("\t\t\t      Name: '<fg=red>$this->model</>'");
		$this->info("\t\t\tSuper mode: '<fg=red>" . ($this->super ? 'true' : 'false') . "</>'");
		$this->info("------------------------------------------------------------------------");


		// confirm namespace and name
		if (!$this->confirm("Do you wish to continue with '<fg=red>$this->routeKind \\ {$this->namespace} \\ {$this->model}" . ($this->super ? ' (Super mode)' : '') . "</>'?")) {
			$this->error('Finished !!!');

			return;
		}
	}

	private function getCurrentNamespace() :array
	{
		$modelsDirectory     = app_path() . '/Models';
		$modelSubDirectories = array_filter(glob("$modelsDirectory/*"), 'is_dir');
		$currentNamespace    = array_map(function ($directory) use ($modelsDirectory) {
			return str_replace("$modelsDirectory/", '', $directory);
		}, $modelSubDirectories);

		return $currentNamespace;
	}

	private function generatePaths()
	{
		foreach (self::PATHS as $kind => $path) {
			$this->paths[$kind] = str_replace(
				['%route_kind%', '%namespace%', '%name%', '%plural_name%'],
				[$this->routeKind, $this->namespace, $this->model, Str::plural($this->model)],
				$path
			);
		}
	}

	private function generateByLaravel()
	{
		// seed
		$this->call('make:seed', [
			'name' => $this->paths['file_path']['seed'],
		]);
		$this->call('make:seed', [
			'name' => $this->paths['file_path']['fake_seed'],
		]);

		// migration
		if(!file_exists($this->paths['full_file_path']['model']))
			$this->call('make:migration', [
				'name'     => $this->paths['file_path']['migration'],
				'--create' => lcfirst(Str::snake(Str::plural($this->model))),
			]);

		// factory
		$this->call('make:factory', [
			'name' => $this->paths['file_path']['factory'],
			'-m'   => $this->paths['file_path']['model'],
		]);

		// test
		$this->call('make:test', [
			'name' => $this->paths['file_path']['test'],
		]);
		$this->call('make:test', [
			'name'   => $this->paths['file_path']['test'],
			'--unit' => true,
		]);
	}

	private function makeTranslate() :void
	{
		foreach (config('laravel_maker.locales') as $locale) {

			if (!file_exists("resources/lang/{$locale}"))
				mkdir("resources/lang/{$locale}", 0777, true);
			$filePath = "resources/lang/{$locale}/responses.php";

			\App::setLocale($locale);

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;


			$routeKind = Str::snake($this->routeKind);
			$namespace = Str::snake($this->namespace);
			$name      = Str::snake($this->model);

			if (!isset($data[$namespace][$name]))
				$data[$routeKind][$namespace][$name] = [
					'store'   => trans('mont4::response.api.store', ['name' => $name]),
					'update'  => trans('mont4::response.api.update', ['name' => $name]),
					'destroy' => trans('mont4::response.api.destroy', ['name' => $name]),
				];

			$fileContent = $this->var_export($data);
			$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

			file_put_contents($filePath, $fileContent);
		}
	}

	private function makePermissionTranslate() :void
	{
		foreach (config('laravel_maker.locales') as $locale) {
			if (!file_exists("resources/lang/{$locale}"))
				mkdir("resources/lang/{$locale}", 0777, true);
			$filePath = "resources/lang/{$locale}/permission.php";

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;


			$routeKind = Str::snake($this->routeKind);
			$namespace = Str::snake($this->namespace);
			$model     = Str::snake($this->model);

			if (!isset($data['route_kind'][$routeKind]))
				$data['route_kind'][$routeKind] = $this->routeKind;

			if (!isset($data['namespace'][$namespace]))
				$data['namespace'][$namespace] = $this->namespace;

			if (!isset($data['controller'][$namespace][$model]))
				$data['controller'][$namespace][$model] = $this->model;

			if (!isset($data['permissions']))
				$data['permissions'] = [
					'super_index'   => 'Super index',
					'index'         => 'index',
					'store'         => 'store',
					'super_show'    => 'Super show',
					'show'          => 'Show',
					'super_update'  => 'Super update',
					'update'        => 'Update',
					'super_destroy' => 'Super Destroy',
					'destroy'       => 'Destroy',
				];

			$fileContent = $this->var_export($data);
			$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

			file_put_contents($filePath, $fileContent);
		}
	}

	private function makePermissionList() :void
	{
		$providers = array_keys(config('auth.providers'));

		$routeKind = Str::snake($this->routeKind);
		$namespace = Str::snake($this->namespace);
		$model     = Str::snake($this->model);

		foreach ($providers as $provider) {
			$filePath = "config/laravel_maker_{$provider}.php";

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;

			if ($this->super)
				$data[$routeKind][$namespace][$model] = [
					'index',
					'super_index',
					'store',
					'show',
					'super_show',
					'update',
					'super_update',
					'destroy',
					'super_destroy',
					'list',
				];
			else
				$data[$routeKind][$namespace][$model] = [
					'index',
					'store',
					'show',
					'update',
					'destroy',
					'list',
				];


			$fileContent = $this->var_export($data);
			$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

			file_put_contents($filePath, $fileContent);
		}
	}

	private function generateStub() :void
	{
		foreach (self::STUBS as $class) {
			if ($class == 'policy' &&
				file_exists($this->paths['full_file_path']['policy']) &&
				!file_exists($this->paths['full_file_path']['controller'])
			) {
				$this->generateFile($class, "{$class}_methods", true);
				$this->info("$class added successfully.");

				continue;
			} else if (file_exists($this->paths['full_file_path'][$class])) {
				$this->error("$class already exists!");

				continue;
			}

			$this->generateFile($class);

			$this->info("$class created successfully.");
		}
	}

	/**
	 * @param       $class
	 * @param null  $stubClass
	 * @param bool  $replace
	 */
	private function generateFile($class, $stubClass = NULL, $replace = false) :void
	{
		if (!$replace)
			$this->generateDirectory($class);

		$replaces = $this->generateReplaces($class);

		// get and generate stub
		$stubClass = $stubClass ?? $class;
		if ($this->super && in_array($class, ['controller', 'policy', 'request_update', 'policy_methods'])) {
			$stubClass = "super_{$stubClass}";
		}
		$stub        = file_get_contents(__dir__ . "/../stubs/class/{$stubClass}.stub");
		$fileContent = str_replace(array_keys($replaces), array_values($replaces), $stub);

		if ($replace) {
			// write stub in file
			$fileData = file_get_contents($this->paths['full_file_path'][$class]);
			$pos      = strrpos($fileData, '}');

			$fileContent = substr_replace($fileData, $fileContent, $pos);
			$fileContent .= "\n\n}";
		}

		// write stub in file
		file_put_contents($this->paths['full_file_path'][$class], $fileContent);
	}

	private function generateReplaces($class)
	{
		// prepare replaces
		$userModelNamespace      = config('laravel_maker.user_model');
		$userModelNamespaceItems = explode('\\', $userModelNamespace);
		$userModelName           = array_pop($userModelNamespaceItems);

		$replaces = [
			'DummyNamespace'              => $this->paths['namespace'][$class],
			'DummyModelNamespace'         => $this->paths['full_namespace']['model'],
			'DummyRequestStoreNamespace'  => $this->paths['full_namespace']['request_store'],
			'DummyRequestUpdateNamespace' => $this->paths['full_namespace']['request_update'],
			'DummyResourceIndexNamespace' => $this->paths['full_namespace']['resource_index'],
			'DummyResourceShowNamespace'  => $this->paths['full_namespace']['resource_show'],
			'DummyResourceListNamespace'  => $this->paths['full_namespace']['resource_list'],

			'DummyModelName'         => $this->model,
			'DummyFilterName'        => "{$this->model}Filter",
			'DummyRequestStoreName'  => "Store{$this->model}Request",
			'DummyRequestUpdateName' => "Update{$this->model}Request",

			'DummyUserModelNamespace' => $userModelNamespace,
			'DummyUserModelName'      => $userModelName,

			'DummyIndexResourceName' => "{$this->model}IndexResource",
			'DummyShowResourceName'  => "{$this->model}ShowResource",
			'DummyListResourceName'  => "{$this->model}ListResource",


			'dummy_route_kind' => Str::snake($this->routeKind),
			'DummyRouteKind'   => $this->routeKind,
			'dummy_namespace'  => Str::snake($this->namespace),
			'dummyNames'       => lcfirst(Str::plural($this->model)),
			'dummyName'        => lcfirst($this->model),
			'dummy_name'       => Str::snake($this->model),
		];

		return $replaces;
	}

	private function generateDirectory($class) :void
	{
		$path  = $this->paths['full_file_path'][$class];
		$paths = explode('/', $path);

		array_pop($paths);
		$pathOfDirectory = implode('/', $paths);

		if (!file_exists($pathOfDirectory))
			mkdir($pathOfDirectory, 0777, true);
	}

	private function var_export($var, $indent = "")
	{
		switch (gettype($var)) {
			case "string":
				return "'" . addcslashes($var, "\\\$\"\r\n\t\v\f") . "'";
			case "array":
				$indexed = array_keys($var) === range(0, count($var) - 1);
				$r       = [];
				foreach ($var as $key => $value) {
					$r[] = "$indent    "
						. ($indexed ? "" : $this->var_export($key) . " => ")
						. $this->var_export($value, "$indent    ");
				}
				return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
			case "boolean":
				return $var ? "TRUE" : "FALSE";
			default:
				return var_export($var, true);
		}
	}
}
