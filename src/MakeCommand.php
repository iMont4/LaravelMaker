<?php

namespace Mont4\LaravelMaker;

use function GuzzleHttp\Psr7\str;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'make:all {name} {--s|super : Super admin mode}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'make all files (Controller, Models, Migrations, Seed, Policy, Requests, Translates, Permissions, etc)';

	private $fullNamespaces = [];
	private $namespaces     = [];
	private $fullFilepaths  = [];
	private $filepaths      = [];

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
		$name = $this->argument('name');
		list($name, $namespace) = $this->extract($name);

		$needSuper = false;
		if ($this->option('super'))
			$needSuper = true;

		$this->generateFilePath($namespace, $name);
		$this->generateNamespace($namespace, $name);

		$this->makeModel();
		$this->makeFactory();
		$this->makeResource();

		$this->makeSeed();

		$this->makeRequest();

		$makePolicy = new MakePolicy($namespace, $name, $this->fullNamespaces, $this->namespaces, $this->fullFilepaths, $needSuper);
		$makePolicy->generate();

		$makePolicy = new MakeController($namespace, $name, $this->fullNamespaces, $this->namespaces, $this->fullFilepaths, $needSuper);
		$makePolicy->generate();

		$this->makeTranslate($namespace, $name);
		$this->makePermissionTranslate($namespace, $name);

		$this->makePermissionList($namespace, $name, $needSuper);
	}


	private function makeSeed(): void
	{
		$this->call('make:seed', [
			'name' => $this->filepaths['seed'],
		]);

		$this->call('make:seed', [
			'name' => $this->filepaths['fake_seed'],
		]);
	}


	private function makeModel(): void
	{
		$this->call('make:model', [
			'name' => $this->filepaths['model'],
			'-m'   => true,
		]);
	}

	private function makeFactory(): void
	{
		$this->call('make:factory', [
			'name' => $this->filepaths['factory'],
			'-m'   => $this->filepaths['model'],
		]);
	}

	private function makeResource(): void
	{
		$this->call('make:resource', [
			'name' => $this->filepaths['resource'],
		]);
		$this->call('make:resource', [
			'name' => $this->filepaths['collection'],
			'-c'   => true,
		]);
	}

	/**
	 * @param $name
	 * @return array
	 */
	private function extract($name): array
	{
		$explodes  = explode('/', $name);
		$name      = array_pop($explodes);
		$namespace = implode('/', $explodes);
		return [$name, $namespace];
	}


	private function makeRequest(): void
	{
		$this->call('make:request', [
			'name' => $this->filepaths['store_request'],
		]);

		$this->call('make:request', [
			'name' => $this->filepaths['update_request'],
		]);
	}

	/**
	 * @param $namespace
	 * @param $name
	 */
	private function makeTranslate($namespace, $name): void
	{
		foreach (config('laravelmaker.locales') as $locale) {
			if (!file_exists("resources/lang/{$locale}"))
				mkdir("resources/lang/{$locale}", 0777, true);
			$filePath = "resources/lang/{$locale}/responses.php";

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;


			$namespace = Str::snake(str_replace(['/', '\\'], '_', $namespace)) ?: 'default';
			$name      = Str::snake($name);

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

	/**
	 * @param $namespace
	 * @param $name
	 */
	private function makePermissionTranslate($namespace, $name): void
	{
		foreach (config('laravelmaker.locales') as $locale) {
			if (!file_exists("resources/lang/{$locale}"))
				mkdir("resources/lang/{$locale}", 0777, true);
			$filePath = "resources/lang/{$locale}/permission.php";

			$data = [];
			if (file_exists($filePath))
				$data = include $filePath;


			$namespace = Str::snake(str_replace(['/', '\\'], '_', $namespace)) ?: 'default';
			$name      = Str::snake($name);

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

	/**
	 * @param $namespace
	 * @param $name
	 * @param $super
	 */
	private function makePermissionList($namespace, $name, $super): void
	{
		$data = config('laravelmaker');

		$namespace = Str::snake(str_replace(['/', '\\'], '_', $namespace));
		$name      = Str::snake($name);

		if ($super)
			$data['permissions'][$namespace][$name] = [
				'superIndex',
				'index',
				'store',
				'superShow',
				'show',
				'superUpdate',
				'update',
				'superDestroy',
				'destroy',
			];
		else
			$data['permissions'][$namespace][$name] = [
				'index',
				'store',
				'show',
				'update',
				'destroy',
			];

		$fileContent = $this->var_export($data);
		$fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

		$filePath = base_path('config/laravel_maker.php');
		file_put_contents($filePath, $fileContent);

	}


	/**
	 * @param $namespace
	 * @param $name
	 */
	private function generateFilePath($namespace, $name): void
	{
		if($namespace){
			$this->filepaths['controller']     = sprintf('%s/%sController', $namespace, $name);
			$this->filepaths['update_request'] = sprintf('%s/%s/Update%sRequest', $namespace, $name, $name);
			$this->filepaths['store_request']  = sprintf('%s/%s/Store%sRequest', $namespace, $name, $name);
			$this->filepaths['model']          = sprintf('Models/%s/%s', $namespace, $name);
			$this->filepaths['factory']        = sprintf('%sFactory', $name);
			$this->filepaths['resource']       = sprintf('%s/%s/%sResource', $namespace, $name, $name);
			$this->filepaths['collection']     = sprintf('%s/%s/%sCollection', $namespace, $name, $name);
			$this->filepaths['policy']         = sprintf('%s/%sPolicy', $namespace, $name);
			$this->filepaths['seed']           = sprintf('%s_%sTableSeeder', str_replace('/', '_', $namespace), str_plural($name));
			$this->filepaths['fake_seed']      = sprintf('Fake_%s_%sTableSeeder', str_replace('/', '_', $namespace), str_plural($name));

			$this->fullFilepaths['controller']     = sprintf('Http/Controllers/%s/%sController', $namespace, $name);
			$this->fullFilepaths['update_request'] = sprintf('Http/Requests/%s/%s/Update%sRequest', $namespace, $name, $name);
			$this->fullFilepaths['store_request']  = sprintf('Http/Requests/%s/%s/Store%sRequest', $namespace, $name, $name);
			$this->fullFilepaths['model']          = sprintf('Models/%s/%s', $namespace, $name);
			$this->fullFilepaths['resource']       = sprintf('Http/Resources/%s/%s/%sResource', $namespace, $name, $name);
			$this->fullFilepaths['collection']     = sprintf('Http/Resources/%s/%s/%sCollection', $namespace, $name, $name);
			$this->fullFilepaths['policy']         = sprintf('Policies/%s/%sPolicy', $namespace, $name);
			$this->fullFilepaths['seed']           = sprintf('%s_%sSeeder', str_replace('/', '_', $namespace), $name);
			$this->fullFilepaths['fake_seed']      = sprintf('Fake_%s_%sSeeder', str_replace('/', '_', $namespace), $name);
		}else{
			$this->filepaths['controller']     = sprintf('%sController', $name);
			$this->filepaths['update_request'] = sprintf('%s/Update%sRequest', $name, $name);
			$this->filepaths['store_request']  = sprintf('%s/Store%sRequest', $name, $name);
			$this->filepaths['model']          = sprintf('Models/%s', $name);
			$this->filepaths['factory']        = sprintf('%sFactory', $name);
			$this->filepaths['resource']       = sprintf('%s/%sResource', $name, $name);
			$this->filepaths['collection']     = sprintf('%s/%sCollection', $name, $name);
			$this->filepaths['policy']         = sprintf('%sPolicy', $name);
			$this->filepaths['seed']           = sprintf('%sTableSeeder', str_plural($name));
			$this->filepaths['fake_seed']      = sprintf('Fake_%sTableSeeder', str_plural($name));

			$this->fullFilepaths['controller']     = sprintf('Http/Controllers/%sController', $name);
			$this->fullFilepaths['update_request'] = sprintf('Http/Requests/%s/Update%sRequest', $name, $name);
			$this->fullFilepaths['store_request']  = sprintf('Http/Requests/%s/Store%sRequest', $name, $name);
			$this->fullFilepaths['model']          = sprintf('Models/%s', $name);
			$this->fullFilepaths['resource']       = sprintf('Http/Resources/%s/%sResource', $name, $name);
			$this->fullFilepaths['collection']     = sprintf('Http/Resources/%s/%sCollection', $name, $name);
			$this->fullFilepaths['policy']         = sprintf('Policies/%sPolicy', $name);
			$this->fullFilepaths['seed']           = sprintf('%sSeeder', $name);
			$this->fullFilepaths['fake_seed']      = sprintf('Fake_%sSeeder', $name);
		}
	}

	/**
	 * @param $namespace
	 * @param $name
	 */
	private function generateNamespace($namespace, $name): void
	{
		if($namespace){
			$namespace                              = str_replace('/', '\\', $namespace);
			$this->fullNamespaces['controller']     = sprintf('App\Http\Controllers\%s\%sController', $namespace, $name);
			$this->fullNamespaces['update_request'] = sprintf('App\Http\Requests\%s\%s\Update%sRequest', $namespace, $name, $name);
			$this->fullNamespaces['store_request']  = sprintf('App\Http\Requests\%s\%s\Store%sRequest', $namespace, $name, $name);
			$this->fullNamespaces['model']          = sprintf('App\Models\%s\%s', $namespace, $name);
			$this->fullNamespaces['resource']       = sprintf('App\Http\Resources\%s\%s\%sResource', $namespace, $name, $name);
			$this->fullNamespaces['collection']     = sprintf('App\Http\Resources\%s\%s\%sCollection', $namespace, $name, $name);
			$this->fullNamespaces['policy']         = sprintf('App\Policies\%s\%sPolicy', $namespace, $name);
			$this->fullNamespaces['user_model']     = config('auth.providers.users.model');

			$this->namespaces['controller']     = sprintf('App\Http\Controllers\%s', $namespace);
			$this->namespaces['update_request'] = sprintf('App\Http\Requests\%s\%s', $namespace, $name);
			$this->namespaces['store_request']  = sprintf('App\Http\Requests\%s\%s', $namespace, $name);
			$this->namespaces['model']          = sprintf('App\Models\%s', $namespace);
			$this->namespaces['policy']         = sprintf('App\Policies\%s', $namespace);
		}else{
			$this->fullNamespaces['controller']     = sprintf('App\Http\Controllers\%sController', $name);
			$this->fullNamespaces['update_request'] = sprintf('App\Http\Requests\%s\Update%sRequest', $name, $name);
			$this->fullNamespaces['store_request']  = sprintf('App\Http\Requests\%s\Store%sRequest', $name, $name);
			$this->fullNamespaces['model']          = sprintf('App\Models\%s', $name);
			$this->fullNamespaces['resource']       = sprintf('App\Http\Resources\%s\%sResource', $name, $name);
			$this->fullNamespaces['collection']     = sprintf('App\Http\Resources\%s\%sCollection', $name, $name);
			$this->fullNamespaces['policy']         = sprintf('App\Policies\%sPolicy', $name);
			$this->fullNamespaces['user_model']     = config('auth.providers.users.model');

			$this->namespaces['controller']     = sprintf('App\Http\Controllers');
			$this->namespaces['update_request'] = sprintf('App\Http\Requests\%s', $name);
			$this->namespaces['store_request']  = sprintf('App\Http\Requests\%s', $name);
			$this->namespaces['model']          = sprintf('App\Models');
			$this->namespaces['policy']         = sprintf('App\Policies');
		}
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
