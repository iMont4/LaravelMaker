<?php
/**
 * Created by PhpStorm.
 * User: imohammad
 * Date: 8/30/18
 * Time: 7:36 PM
 */

namespace Mont4\LaravelMaker;


use Nette\PhpGenerator\PhpNamespace;

class MakePolicy
{
	private $class;

	private $namespace;
	private $name;
	private $classNameValue;
	private $namespaceValue;
	private $permissionNameSpace;

	private $userModelNamespace;
	private $HandlesAuthorizationNamespace;

	private $methods = [
		// index
		[
			'method' => 'superIndex',

			'super'         => true,
			'second_param'  => false,
			'specific_user' => false,
		],
		[
			'method' => 'index',

			'super'         => false,
			'second_param'  => false,
			'specific_user' => false,
		],

		// Store
		[
			'method' => 'store',

			'super'         => false,
			'second_param'  => false,
			'specific_user' => false,
		],

		// Show
		[
			'method' => 'superShow',

			'super'         => true,
			'second_param'  => true,
			'specific_user' => false,
		],
		[
			'method' => 'show',

			'super'         => false,
			'second_param'  => true,
			'specific_user' => true,
		],

		// update
		[
			'method' => 'superUpdate',

			'super'         => true,
			'second_param'  => true,
			'specific_user' => false,
		],
		[
			'method' => 'update',

			'super'         => false,
			'second_param'  => true,
			'specific_user' => true,
		],

		// destroy
		[
			'method' => 'superDestroy',

			'super'         => true,
			'second_param'  => true,
			'specific_user' => false,
		],
		[
			'method' => 'destroy',

			'super'         => false,
			'second_param'  => true,
			'specific_user' => true,
		],
	];
	private $needSuper;
	private $fullFilepaths;

	public function __construct($namespace, $name, $fullNamespaces, $namespaces, $fullFilepaths, $needSuper)
	{
		$this->namespace = $namespace;
		$this->name      = $name;
		$this->needSuper = $needSuper;

		$this->fullNamespaces = $fullNamespaces;
		$this->namespaces     = $namespaces;
		$this->fullFilepaths  = $fullFilepaths;

		$this->classNameValue = sprintf('%sPolicy', $this->name);
		$this->namespaceValue = sprintf('App\Policies\%s', str_replace('/', '\\', $this->namespace));

		$this->permissionNameSpace = str_replace(['\\', '/'], '', $this->namespace);

		$this->userModelNamespace            = config('auth.providers.users.model');
		$this->HandlesAuthorizationNamespace = 'Illuminate\Auth\Access\HandlesAuthorization';
	}


	public function generate()
	{
		$namespace = new PhpNamespace($this->namespaces['policy']);
		$namespace->addUse($this->fullNamespaces['user_model'])
			->addUse($this->fullNamespaces['model'])
			->addUse($this->HandlesAuthorizationNamespace);

		$this->class = $namespace->addClass($this->classNameValue)
			->addTrait($this->HandlesAuthorizationNamespace);

		foreach ($this->methods as $method) {
			if (
				!$method['super'] ||
				(
					$method['super'] &&
					$this->needSuper
				)
			) {
				$this->addMethod($method['method'], $method['second_param'], $method['specific_user']);
			}
		}

		$this->makeFile($namespace);

	}

	private function addMethod($methodName, $secondParam = false, $specificUserPermission = false)
	{
		$secoundParamName = lcfirst($this->name);

		$method = $this->class->addMethod($methodName)
			->setVisibility('public');

		$method->addComment("@param  \\{$this->fullNamespaces['user_model']} \$user")
			->addParameter('user')
			->setTypeHint($this->fullNamespaces['user_model']);


		if ($secondParam)
			$method->addComment("@param  \\{$this->fullNamespaces['model']} \${$secoundParamName}")
				->addParameter($secoundParamName)
				->setTypeHint($this->fullNamespaces['model']);


		$method->addComment('');
		$method->addComment('@return mixed');

		if ($specificUserPermission) {
			$methodBody = "
if (\$user->can('{$this->permissionNameSpace}.{$this->name}.{$methodName}')) {
	if (\${$secoundParamName}->user_id == \$user->id) {
		return true;
	}
}

return false;
";
		} else {
			$methodBody = "
if (\$user->can('{$this->permissionNameSpace}.{$this->name}.{$methodName}'))
{
	return true;
}

return false;
";
		}

		$method->setBody($methodBody);
	}

	/**
	 * @param $namespace
	 */
	private function makeFile($namespace): void
	{
		$path  = $this->fullFilepaths['policy'];
		$paths = explode('/', $path);
		array_pop($paths);
		$pathOfDirectory = implode('/', $paths);
		$pathOfDirectory = app_path($pathOfDirectory);

		if (!file_exists($pathOfDirectory))
			mkdir($pathOfDirectory, 0777, true);

		$path = app_path($this->fullFilepaths['policy'] . '.php');
		if (!file_exists($path)) {
			$contents = "<?php\n\n" . $namespace;
			file_put_contents($path, $contents);
		}
	}


	/**
	 * @param $namespace
	 * @param $name
	 */
	private function makeTranslate($namespace, $name): void
	{


	}
}