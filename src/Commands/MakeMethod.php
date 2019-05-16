<?php

namespace Mont4\LaravelMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeMethod extends Command
{
	const PATHS = [
		'namespace'      => [
			'controller'     => 'App\Http\Controllers\%route_kind%\%namespace%',
			'request_index'  => 'App\Http\Requests\%route_kind%\%namespace%\%name%',
			'request_list'   => 'App\Http\Requests\%route_kind%\%namespace%\%name%',
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
			'request_index'  => 'App\Http\Requests\%route_kind%\%namespace%\%name%\%name%IndexRequest',
			'request_list'   => 'App\Http\Requests\%route_kind%\%namespace%\%name%\%name%ListRequest',
			'request_update' => 'App\Http\Requests\%route_kind%\%namespace%\%name%\%name%%method%Request',
			'request_store'  => 'App\Http\Requests\%route_kind%\%namespace%\%name%\%name%%method%Request',
			'model'          => 'App\Models\%namespace%\%name%',
			'filter'         => 'App\Filters\%namespace%\%name%Filter',
			'resource_index' => 'App\Http\Resources\%route_kind%\%namespace%\%name%\%name%IndexResource',
			'resource_show'  => 'App\Http\Resources\%route_kind%\%namespace%\%name%\%name%ShowResource',
			'resource_list'  => 'App\Http\Resources\%route_kind%\%namespace%\%name%\%name%ListResource',
			'policy'         => 'App\Policies\%namespace%\%name%Policy',
		],
		'file_path'      => [
			'controller'     => '%route_kind%/%namespace%/%name%Controller',
			'request_index'  => '%route_kind%/%namespace%/%name%/%name%IndexRequest',
			'request_list'   => '%route_kind%/%namespace%/%name%/%name%ListRequest',
			'request_update' => '%route_kind%/%namespace%/%name%/%name%%method%Request',
			'request_store'  => '%route_kind%/%namespace%/%name%/%name%%method%Request',
			'model'          => 'Models/%namespace%/%name%',
			'migration'      => 'create%plural_name%_table',
			'filter'         => 'Filters/%namespace%/%name%Filter',
			'factory'        => '%name%Factory',
			'resource_index' => '%route_kind%/%namespace%/%name%/%name%%method%Resource',
			'resource_show'  => '%route_kind%/%namespace%/%name%/%name%%method%Resource',
			'resource_list'  => '%route_kind%/%namespace%/%name%/%name%ListResource',
			'policy'         => '%namespace%/%name%Policy',
			'seed'           => '%namespace%_%name%Seeder',
			'fake_seed'      => 'Fake_%namespace%_%name%Seeder',
			'test'           => '%route_kind%/%namespace%/%name%Test',
		],
		'full_file_path' => [
			'controller'     => 'app/Http/Controllers/%route_kind%/%namespace%/%name%Controller.php',
			'request_index'  => 'app/Http/Requests/%route_kind%/%namespace%/%name%/%name%IndexRequest.php',
			'request_list'   => 'app/Http/Requests/%route_kind%/%namespace%/%name%/%name%ListRequest.php',
			'request_update' => 'app/Http/Requests/%route_kind%/%namespace%/%name%/%name%%method%Request.php',
			'request_store'  => 'app/Http/Requests/%route_kind%/%namespace%/%name%/%name%%method%Request.php',
			'model'          => 'app/Models/%namespace%/%name%.php',
			'filter'         => 'app/Filters/%namespace%/%name%Filter.php', // TODO. with route kind or without
			'factory'        => '%name%Factory.php',
			'resource_index' => 'app/Http/Resources/%route_kind%/%namespace%/%name%/%name%%method%Resource.php',
			'resource_show'  => 'app/Http/Resources/%route_kind%/%namespace%/%name%/%name%%method%Resource.php',
			'resource_list'  => 'app/Http/Resources/%route_kind%/%namespace%/%name%/%name%ListResource.php',
			'policy'         => 'app/Policies/%namespace%/%name%Policy.php',
			'seed'           => '%namespace%_%name%Seeder.php',
			'fake_seed'      => 'Fake_%namespace%_%name%Seeder.php',
			'test'           => '%route_kind%/%namespace%/%name%Test.php',
		],
	];

	const STUBS = [
		'dynamic',
		'policy',
		'controller',
	];

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'make:method';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	private $routeKind = '';
	private $namespace = '';
	private $model     = '';
	private $type      = '';
	private $method    = '';
	private $id        = false;
	private $const     = '';
	private $super     = true;

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
		$status = $this->getInformation(); // make method
		if (!$status) {
			return;
		}

		$this->generatePaths(); // make method
		$this->makeTranslate(); // make method
		$this->makePermissionTranslate(); // make method
		$this->makePermissionList(); // make method
		$this->generateStub();
		$this->generateRoute();
	}

	private function getInformation()
	{
		$this->routeKind = Str::ucfirst(Str::camel($this->choice('Please choose the route kind?', config('laravel_maker.route_kinds'), config('laravel_maker.route_kind_default'))));

		$currentNamespaces = $this->getCurrentNamespace();
		if (!count($currentNamespaces)) {
			$this->error("'$this->routeKind' has not any namespace!");

			return false;
		}

		$this->namespace = Str::ucfirst(Str::camel(($this->choice('Please choose the namespace?', $currentNamespaces))));

		$currentModels = $this->getCurrentModels();
		if (!count($currentModels)) {
			$this->error("'$this->routeKind / $this->namespace' has not any namespace!");

			return false;
		}
		$this->model = Str::ucfirst(Str::camel($this->choice('Please choose model?', $currentModels)));

		$this->type = Str::ucfirst(Str::camel($this->choice('Please choose type?', ['Get', 'Post', 'Const'])));

		$this->method = Str::ucfirst(Str::camel($this->ask('Please enter method?')));

		if ($this->type != 'Const') {
			$this->id = $this->confirm("Has id input ?");
		}

		$this->super = $this->confirm("Is it super ?");

		$this->info("------------------------------------------------------------------------");
		$this->info("\t\t\tRoute kind: '<fg=red>$this->routeKind</>'");
		$this->info("\t\t\t Namespace: '<fg=red>$this->namespace</>'");
		$this->info("\t\t\t     Model: '<fg=red>$this->model</>'");
		$this->info("");
		$this->info("\t\t\t    Method: '<fg=red>$this->method</>'");
		$this->info("\t\t\t      Type: '<fg=red>$this->type</>'");
		$this->info("\t\t\t        ID: '<fg=red>" . ($this->id ? 'true' : 'false') . "</>'");
		$this->info("\t\t\tSuper mode: '<fg=red>" . ($this->super ? 'true' : 'false') . "</>'");
		$this->info("------------------------------------------------------------------------");


		// confirm namespace and name
		if (!$this->confirm("Do you wish to continue with '<fg=red>$this->routeKind \\ {$this->namespace} \\ {$this->model}" . ($this->super ? ' (Super mode)' : '') . "</>'?")) {
			$this->error('Finished !!!');

			return false;
		}

		return true;
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

	private function getCurrentModels() :array
	{
		$modelsDirectory     = app_path() . '/Models/' . $this->namespace;
		$modelSubDirectories = array_filter(glob("$modelsDirectory/*"), 'is_file');
		$currentNamespace    = array_map(function ($directory) use ($modelsDirectory) {
			return str_replace(["$modelsDirectory/", '.php'], '', $directory);
		}, $modelSubDirectories);

		return $currentNamespace;
	}

	private function generatePaths()
	{
		foreach (self::PATHS as $kind => $path) {
			$this->paths[$kind] = str_replace(
				['%route_kind%', '%namespace%', '%name%', '%method%', '%plural_name%'],
				[$this->routeKind, $this->namespace, $this->model, $this->method, Str::plural($this->model)],
				$path
			);
		}
	}

	private function getKind()
	{
		$kinds = [
			'Get'   => [
				// according id
				false => 'index',
				true  => 'show',
			],
			'Post'  => [
				// according id
				false => 'store',
				true  => 'update',
			],
			'Const' => [
				// according id
				false => 'const',
				true  => 'const',
			],
		];

		return $kinds[$this->type][$this->id];
	}

	private function getDynamicKind()
	{
		$kinds = [
			'Get'  => [
				// according id
				false => 'resource_index',
				true  => 'resource_show',
			],
			'Post' => [
				// according id
				false => 'request_store',
				true  => 'request_update',
			],
		];

		return $kinds[$this->type][$this->id] ?? NULL;
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
			$method    = Str::snake($this->method);

			if (!isset($data[$namespace][$name]))
				$data[$routeKind][$namespace][$name][$method] = '';

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
			$method    = Str::snake($this->method);

			if (!isset($data['route_kind'][$routeKind]))
				$data['route_kind'][$routeKind] = $this->routeKind;

			if (!isset($data['namespace'][$namespace]))
				$data['namespace'][$namespace] = $this->namespace;

			if (!isset($data['controller'][$namespace][$model]))
				$data['controller'][$namespace][$model] = $this->model;

			if (!isset($data['permissions'])) {
				$data['permissions'][$method] = "";

				if ($this->super) {
					$data['permissions']["super_$method"] = "";
				}
			}


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
		$method    = Str::snake($this->method);

		foreach ($providers as $provider) {
			$filePath = "config/laravel_maker_{$provider}.php";

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;

			if ($this->super)
				$data[$routeKind][$namespace][$model] = array_merge(
					$data[$routeKind][$namespace][$model],
					[
						$method,
						"super_$method",
					]
				);
			else
				$data[$routeKind][$namespace][$model] = array_merge(
					$data[$routeKind][$namespace][$model],
					[
						$method,
					]
				);


			$fileContent = $this->var_export($data);
			$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

			file_put_contents($filePath, $fileContent);
		}
	}

	private function generateStub() :void
	{
		foreach (self::STUBS as $class) {
			if ($class == 'dynamic') {
				if (!($class = $this->getDynamicKind()))
					continue;

				$this->generateFile($class);

				$this->info("$class created successfully.");
			} else {
				$kind = $this->getKind();

				$this->generateFile($class, "{$class}_{$kind}", true);

				$this->info("{$class}_{$kind} added successfully.");
			}
		}
	}

	private function generateRoute() :void
	{
		$replaces = $this->generateReplaces();

		$name        = "routes";
		if($this->id){
			$name = "routes_with_id";
		}

		$stub        = file_get_contents(__dir__ . "/../stubs/method/{$name}.stub");
		$fileContent = str_replace(array_keys($replaces), array_values($replaces), $stub);

		$this->line('------------------------------------------------------------------------');
		$this->info("Add to your routes.");
		$this->line($fileContent);
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
		if ($this->super && in_array($class, ['controller', 'policy', 'request_update'])) {
			$stubClass = "super_{$stubClass}";
		}
		$stub        = file_get_contents(__dir__ . "/../stubs/method/{$stubClass}.stub");
		$fileContent = str_replace(array_keys($replaces), array_values($replaces), $stub);

		if ($replace) {
			if (!file_exists($this->paths['full_file_path'][$class]))
				return;

			// write stub in file
			$fileData = file_get_contents($this->paths['full_file_path'][$class]);
			$pos      = strrpos($fileData, '}');

			$fileContent = substr_replace($fileData, $fileContent, $pos);
			$fileContent .= "\n\n}";
		}

		// write stub in file
		file_put_contents($this->paths['full_file_path'][$class], $fileContent);
	}

	private function generateReplaces($class = NULL)
	{
		// prepare replaces
		$userModelNamespace      = config('laravel_maker.user_model');
		$userModelNamespaceItems = explode('\\', $userModelNamespace);
		$userModelName           = array_pop($userModelNamespaceItems);

		$replaces = [
			'DummyNamespace'              => $class ? $this->paths['namespace'][$class] : $this->namespace,
			'DummyModelNamespace'         => $this->paths['full_namespace']['model'],
			'DummyRequestIndexNamespace'  => $this->paths['full_namespace']['request_index'],
			'DummyRequestListNamespace'   => $this->paths['full_namespace']['request_list'],
			'DummyRequestStoreNamespace'  => $this->paths['full_namespace']['request_store'],
			'DummyRequestUpdateNamespace' => $this->paths['full_namespace']['request_update'],
			'DummyResourceIndexNamespace' => $this->paths['full_namespace']['resource_index'],
			'DummyResourceShowNamespace'  => $this->paths['full_namespace']['resource_show'],
			'DummyResourceListNamespace'  => $this->paths['full_namespace']['resource_list'],

			'DummyModelName'         => $this->model,
			'DummyFilterName'        => "{$this->model}Filter",
			'DummyRequestIndexName'  => "{$this->model}IndexRequest",
			'DummyRequestListName'   => "{$this->model}ListRequest",
			'DummyRequestStoreName'  => "{$this->model}{$this->method}Request",
			'DummyRequestUpdateName' => "{$this->model}{$this->method}Request",

			'DummyUserModelNamespace' => $userModelNamespace,
			'DummyUserModelName'      => $userModelName,

			'DummyIndexResourceName' => "{$this->model}{$this->method}Resource",
			'DummyShowResourceName'  => "{$this->model}{$this->method}Resource",
			'DummyListResourceName'  => "{$this->model}ListResource",

			'DummyPermission'      => "{$this->routeKind}{$this->method}",
			'DummySuperPermission' => "{$this->routeKind}Super{$this->method}",


			'dummy_route_kind' => Str::snake($this->routeKind),
			'DummyRouteKind'   => $this->routeKind,
			'dummy_namespace'  => Str::snake($this->namespace),
			'dummyNames'       => lcfirst(Str::plural($this->model)),
			'dummyName'        => lcfirst($this->model),
			'dummy_name'       => Str::snake($this->model),
			'dummyMethod'      => lcfirst($this->method),
			'DummyMethod'      => $this->method,
			'dummy_method'     => Str::snake($this->method),
			'dummy_const_name' => Str::snake($this->method),
			'DummyConstName'   => Str::upper(Str::snake($this->method)),
			'dummy_type'       => $this->type == 'Post' ? 'post' : 'get',
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
