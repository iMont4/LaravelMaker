<?php
/**
 * Created by PhpStorm.
 * User: imohammad
 * Date: 8/30/18
 * Time: 7:36 PM
 */

namespace Mont4\LaravelMaker;


use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpNamespace;

class MakeController
{
	private $class;

	private $namespace;
	private $name;

	private $namespaceMessage;
	private $nameMessage;

	private $classNameValue;
	private $permissionNameSpace;

	private $controllerNamespace;

	private $needSuper;
	private $fullNamespaces;
	private $namespaces;
	private $fullFilepaths;

	public function __construct($namespace, $name, $fullNamespaces, $namespaces, $fullFilepaths, $needSuper)
	{
		$this->namespace = $namespace;
		$this->name      = $name;


		$this->namespaceMessage = Str::snake(str_replace(['/', '\\'], '_', $namespace)) ?: 'default';
		$this->nameMessage      = Str::snake($name);

		$this->classNameValue = sprintf('%sController', $this->name);

		$this->permissionNameSpace = Str::snake(str_replace(['/', '\\'], '_', $namespace)) ?: 'default';

		$this->controllerNamespace = 'App\Http\Controllers\Controller';

		$this->needSuper      = $needSuper;
		$this->fullNamespaces = $fullNamespaces;
		$this->namespaces     = $namespaces;
		$this->fullFilepaths  = $fullFilepaths;
	}


	public function generate()
	{
		$namespace = new PhpNamespace($this->namespaces['controller']);
		$namespace->addUse($this->fullNamespaces['model'])
			->addUse($this->fullNamespaces['controller'])
			->addUse($this->fullNamespaces['store_request'])
			->addUse($this->fullNamespaces['update_request'])
			->addUse($this->fullNamespaces['resource'])
			->addUse($this->fullNamespaces['collection']);

		$this->class = $namespace->addClass($this->classNameValue)
			->setExtends($this->controllerNamespace);

		$this->addIndexMethod();
		$this->addStoreMethod();
		$this->addShowMethod();
		$this->addUpdateMethod();
		$this->addDestroyMethod();

		$path  = $this->fullFilepaths['controller'];
		$paths = explode('/', $path);
		array_pop($paths);
		$pathOfDirectory = implode('/', $paths);
		$pathOfDirectory = app_path($pathOfDirectory);

		$path = app_path($this->fullFilepaths['controller'] . '.php');
		if (!file_exists($pathOfDirectory))
			mkdir($pathOfDirectory, 0777, true);

		$contents = "<?php\n\n" . $namespace;
		file_put_contents($path, $contents);
	}

	private function addIndexMethod()
	{
		$variableName = str_plural(lcfirst($this->name));

		$method = $this->class->addMethod('index')
			->setVisibility('public');

		$collection = explode('\\', $this->fullNamespaces['collection']);
		$collection = array_pop($collection);

		$method->addComment('');
		$method->addComment("@return {$collection}");

		if ($this->needSuper) {
			$methodBody = "
if (auth()->user()->can('superIndex', {$this->name}::class)) {
	/** @var {$this->name}[] \${$variableName} */
	\${$variableName} = {$this->name}::get();
} elseif (auth()->user()->can('index', {$this->name}::class)) {
	/** @var {$this->name}[] \${$variableName} */
	\${$variableName} = {$this->name}::get();
} else {
	abort(500);
}

\$collection = new {$collection}(\${$variableName});
return \$collection;
";
		} else {
			$methodBody = "
if (auth()->user()->can('index', {$this->name}::class)) {
	/** @var {$this->name}[] \${$variableName} */
	\${$variableName} = {$this->name}::get();
} else {
	abort(500);
}

\$collection = new {$collection}(\${$variableName});
return \$collection;
";
		}

		$method->setBody($methodBody);
	}

	private function addStoreMethod()
	{
		$variableName = lcfirst($this->name);

		$method = $this->class->addMethod('store')
			->setVisibility('public');

		$storeRequestComment = explode('\\', $this->fullNamespaces['store_request']);
		$storeRequestComment = array_pop($storeRequestComment);
		$method->addComment("@param  {$storeRequestComment} \$request")
			->addParameter('request')
			->setTypeHint($this->fullNamespaces['store_request']);


		$method->addComment('');
		$method->addComment('@return mixed');
		$methodBody = "
if (auth()->user()->can('store', {$this->name}::class)) {
	
} else {
	abort(500);
}

\${$variableName} = new {$this->name}();
\${$variableName}->fill(\$request);
\${$variableName}->save();

return [
	'status'  => true,
	'message' => trans('messages.{$this->namespaceMessage}.{$this->nameMessage}.store'),
	'id'      => \${$variableName}->id,
];
";

		$method->setBody($methodBody);

	}

	private function addShowMethod()
	{
		$variableName = lcfirst($this->name);

		$method = $this->class->addMethod('show')
			->setVisibility('public');

		$method->addComment("@param  int \$id")
			->addParameter('id');

		$resource = explode('\\', $this->fullNamespaces['resource']);
		$resource = array_pop($resource);

		$method->addComment('');
		$method->addComment("@return {$resource}");

		if ($this->needSuper) {
			$methodBody = "
/** @var {$this->name} \${$variableName} */
\${$variableName} = {$this->name}::findOrFail(\$id);

if (auth()->user()->can('superShow', \${$variableName})) {

} elseif (auth()->user()->can('show', \${$variableName})) {

} else {
	abort(500);
}

\${$resource} = new $resource(\${$variableName});
return \${$resource};
";
		} else {
			$methodBody = "
/** @var {$this->name} \${$variableName} */
\${$variableName} = {$this->name}::findOrFail(\$id);

if (auth()->user()->can('show', \${$variableName})) {

} else {
	abort(500);
}

\${$resource} = new $resource(\${$variableName});
return \${$resource};
";
		}


		$method->setBody($methodBody);

	}

	private function addUpdateMethod()
	{
		$variableName = lcfirst($this->name);

		$method = $this->class->addMethod('update')
			->setVisibility('public');

		$updateRequestComment = explode('\\', $this->fullNamespaces['update_request']);
		$updateRequestComment = array_pop($updateRequestComment);
		$method->addComment("@param  {$updateRequestComment} \$request")
			->addParameter('request')
			->setTypeHint($this->fullNamespaces['update_request']);

		$method->addComment("@param  int \$id")
			->addParameter('id');


		$method->addComment('');
		$method->addComment('@return mixed');

		if ($this->needSuper) {
			$methodBody = "
/** @var {$this->name} \${$variableName} */
\${$variableName} = {$this->name}::findOrFail(\$id);

if (auth()->user()->can('superUpdate', \${$variableName})) {
	\${$variableName}->fill(\$request);
	\${$variableName}->save();
} elseif (auth()->user()->can('update', \${$variableName})) {
	\${$variableName}->fill(\$request);
	\${$variableName}->save();
} else {
	abort(500);
}

return [
	'status'  => true,
	'message' => trans('messages.{$this->namespaceMessage}.{$this->nameMessage}.update'),
	'id'      => \${$variableName}->id,
];
";
		} else {
			$methodBody = "
/** @var {$this->name} \${$variableName} */
\${$variableName} = {$this->name}::findOrFail(\$id);

if (auth()->user()->can('update', \${$variableName})) {
	\${$variableName}->fill(\$request);
	\${$variableName}->save();
} else {
	abort(500);
}

return [
	'status'  => true,
	'message' => trans('messages.{$this->namespaceMessage}.{$this->nameMessage}.update'),
	'id'      => \${$variableName}->id,
];
";
		}


		$method->setBody($methodBody);

	}

	private function addDestroyMethod()
	{
		$variableName = lcfirst($this->name);

		$method = $this->class->addMethod('destroy')
			->setVisibility('public');

		$method->addComment("@param  int \$id")
			->addParameter('id');


		$method->addComment('');
		$method->addComment('@return mixed');

		if ($this->needSuper) {
			$methodBody = "
/** @var {$this->name} \${$variableName} */
\${$variableName} = {$this->name}::findOrFail(\$id);

if (auth()->user()->can('superDestroy', \${$variableName})) {

} elseif (auth()->user()->can('destroy', \${$variableName})) {

} else {
	abort(500);
}

\${$variableName}->delete();

return [
	'status'  => true,
	'message' => trans('messages.{$this->namespaceMessage}.{$this->nameMessage}.destroy'),
	'id'      => \${$variableName}->id,
];
";
		} else {
			$methodBody = "
/** @var {$this->name} \${$variableName} */
\${$variableName} = {$this->name}::findOrFail(\$id);

if (auth()->user()->can('destroy', \${$variableName})) {

} else {
	abort(500);
}

\${$variableName}->delete();

return [
	'status'  => true,
	'message' => trans('messages.{$this->namespaceMessage}.{$this->nameMessage}.destroy'),
	'id'      => \${$variableName}->id,
];
";
		}


		$method->setBody($methodBody);

	}
}