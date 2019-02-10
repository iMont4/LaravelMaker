<?php

namespace Mont4\LaravelMaker;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAll extends Command
{
	const STUBS = [
		'model',
		'request_store',
		'request_update',
		'resource_index',
		'resource_show',
		'resource_list',
		'policy',
		'controller',
	];

	const SUPER_STUBS = [
		'model',
		'request_store',
		'super_request_update',
		'resource_index',
		'resource_show',
		'resource_list',
		'super_policy',
		'super_controller',
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

	private $namespace;
	private $model;
	private $super;

	private $filePaths;
	private $fullFilePaths;

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
		$currentNamespace = $this->getCurrentNamespace();

		// get namespace
		$this->namespace = ucfirst($this->anticipate('Please enter or choose the namespace?', $currentNamespace));

		// get name
		$this->model = ucfirst($this->ask('Please enter name?'));

		// get is super
		$super = '';
		if ($this->super = $this->confirm("Is it super ?")) {
			$super = ' (Super)';
		}

		// generate full file path
		$this->generateFullFilePaths();

		// create table of full file path
		$fullFilePaths = $this->fullFilePaths;
		$data          = [];
		foreach ($fullFilePaths as $class => $fullFilePath) {
			$data[] = [
				$class,
				$fullFilePath,
			];
		}
		$this->table(['Class', 'Path'], $data);

		// set full file path
		$this->fullFilePaths = array_map('app_path', $this->fullFilePaths);

		// confirm namespace and name
		if (!$this->confirm("Do you wish to continue with '<fg=red>{$this->namespace} \\ {$this->model}{$super}</>'?")) {
			$this->error('Finished !!!');
		}

		$this->generateByLaravel();
		$this->generateStub();
		$this->makeTranslate();
		$this->makePermissionTranslate();
		$this->makePermissionList();
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

	private function generateStub() :void
	{
		$classes = self::STUBS;
		if ($this->super) {
			$classes = self::SUPER_STUBS;
		}

		foreach ($classes as $class) {
			$this->generateFile($class);
		}
	}

	private function generateByLaravel()
	{
		// seed
		$this->call('make:seed', [
			'name' => $this->filePaths['seed'],
		]);
		$this->call('make:seed', [
			'name' => $this->filePaths['fake_seed'],
		]);

		// migration
		$this->call('make:model', [
			'name' => $this->filePaths['model'],
			'-m'   => true,
		]);

		// factory
		$this->call('make:factory', [
			'name' => $this->filePaths['factory'],
			'-m'   => $this->filePaths['model'],
		]);

		// test
		$this->call('make:test', [
			'name' => $this->filePaths['test'],
		]);
		$this->call('make:test', [
			'name'   => $this->filePaths['test'],
			'--unit' => true,
		]);
	}

	/**
	 * @param       $class
	 */
	private function generateFile($class) :void
	{
		$this->generateDirectory($class);

		// prepare replaces
		$replaces = [
			'DummyNamespace' => $this->namespace,
			'dummyNamespace' => lcfirst($this->namespace),
			'DummyName'      => $this->model,
			'dummyNames'     => lcfirst(Str::plural($this->model)),
			'dummyName'      => lcfirst($this->model),
		];

		// get and generate stub
		$stub        = file_get_contents(__dir__ . "/stubs/{$class}.stub");
		$fileContent = str_replace(array_keys($replaces), array_values($replaces), $stub);

		// write stub in file
		file_put_contents($this->fullFilePaths[$class], $fileContent);
	}

	private function generateFullFilePaths() :void
	{
		$this->filePaths['controller']     = sprintf('%s/%sController', $this->namespace, $this->model);
		$this->filePaths['update_request'] = sprintf('%s/%s/Update%sRequest', $this->namespace, $this->model, $this->model);
		$this->filePaths['store_request']  = sprintf('%s/%s/Store%sRequest', $this->namespace, $this->model, $this->model);
		$this->filePaths['model']          = sprintf('Models/%s/%s', $this->namespace, $this->model);
		$this->filePaths['factory']        = sprintf('%sFactory', $this->model);
		$this->filePaths['resource']       = sprintf('%s/%s/%sResource', $this->namespace, $this->model, $this->model);
		$this->filePaths['collection']     = sprintf('%s/%s/%sCollection', $this->namespace, $this->model, $this->model);
		$this->filePaths['policy']         = sprintf('%s/%sPolicy', $this->namespace, $this->model);
		$this->filePaths['seed']           = sprintf('%s_%sTableSeeder', str_replace('/', '_', $this->namespace), str_plural($this->model));
		$this->filePaths['fake_seed']      = sprintf('Fake_%s_%sTableSeeder', str_replace('/', '_', $this->namespace), str_plural($this->model));
		$this->filePaths['test']           = sprintf('%s/%sTest', $this->namespace, $this->model);


		$this->fullFilePaths['controller']           = sprintf('Http/Controllers/%s/%sController.php', $this->namespace, $this->model);
		$this->fullFilePaths['super_controller']     = sprintf('Http/Controllers/%s/%sController.php', $this->namespace, $this->model);
		$this->fullFilePaths['request_store']        = sprintf('Http/Requests/%s/%s/Store%sRequest.php', $this->namespace, $this->model, $this->model);
		$this->fullFilePaths['request_update']       = sprintf('Http/Requests/%s/%s/Update%sRequest.php', $this->namespace, $this->model, $this->model);
		$this->fullFilePaths['super_request_update'] = sprintf('Http/Requests/%s/%s/Update%sRequest.php', $this->namespace, $this->model, $this->model);
		$this->fullFilePaths['model']                = sprintf('Models/%s/%s.php', $this->namespace, $this->model);
		$this->fullFilePaths['resource_index']       = sprintf('Http/Resources/%s/%s/%sIndexResource.php', $this->namespace, $this->model, $this->model);
		$this->fullFilePaths['resource_show']        = sprintf('Http/Resources/%s/%s/%sShowResource.php', $this->namespace, $this->model, $this->model);
		$this->fullFilePaths['resource_list']        = sprintf('Http/Resources/%s/%s/%sListResource.php', $this->namespace, $this->model, $this->model);
		$this->fullFilePaths['policy']               = sprintf('Policies/%s/%sPolicy.php', $this->namespace, $this->model);
		$this->fullFilePaths['super_policy']         = sprintf('Policies/%s/%sPolicy.php', $this->namespace, $this->model);
		$this->fullFilePaths['seed']                 = sprintf('%s_%sSeeder.php', str_replace('/', '_', $this->namespace), $this->model);
		$this->fullFilePaths['seed_fake']            = sprintf('Fake_%s_%sSeeder.php', str_replace('/', '_', $this->namespace), $this->model);
	}

	/**
	 * @param $class
	 *
	 * @return mixed
	 */
	private function generateDirectory($class) :void
	{
		$path  = $this->fullFilePaths[$class];
		$paths = explode('/', $path);
		array_pop($paths);
		$pathOfDirectory = implode('/', $paths);
		if (!file_exists($pathOfDirectory))
			mkdir($pathOfDirectory, 0777, true);
	}

	private function makeTranslate() :void
	{
		foreach (config('laravel_maker.locales') as $locale) {
			if (!file_exists("resources/lang/{$locale}"))
				mkdir("resources/lang/{$locale}", 0777, true);
			$filePath = "resources/lang/{$locale}/responses.php";

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;


			$namespace = Str::snake(str_replace(['/', '\\'], '_', $this->namespace)) ?: 'default';
			$name      = Str::snake($this->model);

			if (!isset($data[$namespace][$name]))
				$data[$namespace][$name] = [
					'store'   => '',
					'update'  => '',
					'destroy' => '',
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


			$namespace = Str::snake(str_replace(['/', '\\'], '_', $this->namespace)) ?: 'default';
			$name      = Str::snake($this->model);

			if (!isset($data['namespace'][$namespace]))
				$data['namespace'][$namespace] = '';

			if (!isset($data['controller'][$namespace][$name]))
				$data['controller'][$namespace][$name] = '';

			if (!isset($data['permissions']))
				$data['permissions'] = [
					'superIndex'   => '',
					'index'        => '',
					'store'        => '',
					'superShow'    => '',
					'show'         => '',
					'superUpdate'  => '',
					'update'       => '',
					'superDestroy' => '',
					'destroy'      => '',
				];

			$fileContent = $this->var_export($data);
			$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

			file_put_contents($filePath, $fileContent);
		}
	}

	private function makePermissionList() :void
	{
		$data = config('laravel_maker');

		$guardNames = array_keys(config('auth.guards'));
		$namespace  = Str::snake(str_replace(['/', '\\'], '_', $this->namespace));
		$name       = Str::snake($this->model);

		foreach ($guardNames as $guardName) {
			if ($this->super)
				$data['permissions'][$guardName][$namespace][$name] = [
					'index',
					'superIndex',
					'store',
					'show',
					'superShow',
					'update',
					'superUpdate',
					'destroy',
					'superDestroy',
					'list',
				];
			else
				$data['permissions'][$guardName][$namespace][$name] = [
					'index',
					'store',
					'show',
					'update',
					'destroy',
					'list',
				];
		}


		$fileContent = $this->var_export($data);
		$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

		$filePath = base_path('config/laravel_maker.php');
		file_put_contents($filePath, $fileContent);

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
