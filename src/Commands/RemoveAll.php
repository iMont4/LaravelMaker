<?php

namespace Mont4\LaravelMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RemoveAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    const PATHS = [
        'request_resource_path' => [
            'request'  => 'app/Http/Requests/%route_kind%/%namespace%/%name%',
            'resource' => 'app/Http/Resources/%route_kind%/%namespace%/%name%',
        ],
        'directory_path'        => [
            'controller' => 'app/Http/Controllers/%route_kind%/%namespace%',
            'request'    => 'app/Http/Requests/%route_kind%/%namespace%',
            'resource'   => 'app/Http/Resources/%route_kind%/%namespace%',

            'model'  => 'app/Models/%namespace%',
            'filter' => 'app/Filters/%namespace%',
            'policy' => 'app/Policies/%namespace%',
        ],
        'full_file_path'        => [
            'controller' => 'app/Http/Controllers/%route_kind%/%namespace%/%name%Controller.php',
            'model'      => 'app/Models/%namespace%/%name%.php',
            'filter'     => 'app/Filters/%namespace%/%name%Filter.php',
            'policy'     => 'app/Policies/%namespace%/%name%Policy.php',

            'factory'   => 'database/factories/%name%Factory.php',
            'seed'      => 'database/seeds/%namespace%_%name%Seeder.php',
            'fake_seed' => 'database/seeds/Fake_%namespace%_%name%Seeder.php',
        ],
    ];

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
        $status = $this->getInformation();
        if (!$status) {
            return;
        }
        $this->generatePaths();

        $this->removeTranslate();
        $this->removePermissionList();

        foreach ($this->paths['request_resource_path'] as $path) {
            $path = base_path($path);

            if (file_exists($path)) {
                $this->rrmdir($path);
            }
        }

        foreach ($this->paths['full_file_path'] as $path) {
            $path = base_path($path);

            if (file_exists($path)) {
                unlink($path);
            }
        }

        foreach ($this->paths['directory_path'] as $path) {
            $path = base_path($path);

            if (file_exists($path) && $this->dir_is_empty($path)) {
                rmdir($path);
            }
        }

    }

    private function removeTranslate() :void
    {
        foreach (config('laravel_maker.locales') as $locale) {

            if (!file_exists("resources/lang/{$locale}"))
                mkdir("resources/lang/{$locale}", 0777, true);
            $filePath = "resources/lang/{$locale}/responses.php";

            $data = [];
            if (file_exists($filePath))
                $data = include $filePath;

            $routeKind = Str::snake($this->routeKind);
            $namespace = Str::snake($this->namespace);
            $name      = Str::snake($this->model);

            if (isset($data[$routeKind][$namespace][$name])) {

                unset($data[$routeKind][$namespace][$name]);

                if (!count($data[$routeKind][$namespace])) {
                    unset($data[$routeKind][$namespace]);
                }
            }


            $fileContent = $this->var_export($data);
            $fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

            file_put_contents($filePath, $fileContent);
        }
    }

    private function removePermissionList() :void
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

            if (isset($data[$routeKind][$namespace][$model])) {
                unset($data[$routeKind][$namespace][$model]);

                if (!count($data[$routeKind][$namespace]))
                    unset($data[$routeKind][$namespace]);
            }


            $fileContent = $this->var_export($data);
            $fileContent = sprintf("<?php\n\nreturn %s;", $fileContent);

            file_put_contents($filePath, $fileContent);
        }
    }

    private function getInformation()
    {
        $this->routeKind = Str::ucfirst(Str::camel($this->choice('Please enter or choose the route kind?', config('laravel_maker.route_kinds'), config('laravel_maker.route_kind_default'))));

        $this->namespace = Str::ucfirst(Str::camel(($this->choice('Please enter or choose the namespace?', $this->getCurrentNamespace()))));

        $currentModels = $this->getCurrentModels();
        if (!count($currentModels)) {
            $this->error("'$this->routeKind / $this->namespace' has not any models!");

            return false;
        }
        $this->model = Str::ucfirst(Str::camel($this->choice('Please enter name?', $currentModels)));

        $this->info("------------------------------------------------------------------------");
        $this->info("\t\t\tRoute kind: '<fg=red>$this->routeKind</>'");
        $this->info("\t\t\t Namespace: '<fg=red>$this->namespace</>'");
        $this->info("\t\t\t      Name: '<fg=red>$this->model</>'");
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
                ['%route_kind%', '%namespace%', '%name%', '%plural_name%'],
                [$this->routeKind, $this->namespace, $this->model, Str::plural($this->model)],
                $path
            );
        }
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }

    private function dir_is_empty($dir)
    {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
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
