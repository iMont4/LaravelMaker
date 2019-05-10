<?php

namespace Mont4\LaravelMaker\Commands;

use Illuminate\Console\Command;


class SyncPermission extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'permission:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

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
		$permissionsCount = 0;
		foreach (config('auth.guards') as $guardName => $guards) {
			$provider = $guards['provider'];
			if (!file_exists("config/laravel_maker_{$provider}.php"))
				continue;

			$routeKinds = include "config/laravel_maker_{$provider}.php";

			$permissionsCount = count($routeKinds, COUNT_RECURSIVE);
		}

		$bar = $this->output->createProgressBar($permissionsCount);
		foreach (config('auth.guards') as $guardName => $guards) {
			$provider = $guards['provider'];
			if (!file_exists("config/laravel_maker_{$provider}.php"))
				continue;

			$routeKinds = include "config/laravel_maker_{$provider}.php";

			$count = 0;
			foreach ($routeKinds as $routeKind => $namespaces) {
				foreach ($namespaces as $namespace => $controllers) {
					foreach ($controllers as $controller => $permissions) {
						foreach ($permissions as $permission) {

							$permissionClass = config('permission.models.permission');

							$permissionEntity = call_user_func([$permissionClass, 'query']);

							$permissionName      = sprintf("%s.%s.%s.%s", $routeKind, $namespace, $controller, $permission);
							$availablePermission = $permissionEntity->where('name', '=', $permissionName)
								->where('guard_name', '=', $guardName)
								->first();

							if (!$availablePermission) {
								$permissionEntity->create([
									'guard_name' => $guardName,
									'route_kind' => $routeKind,
									'namespace'  => $namespace,
									'controller' => $controller,
									'permission' => $permission,
									'name'       => $permissionName,
								]);

								$count++;
							}
							$bar->advance();
						}
						$bar->advance();
					}
					$bar->advance();
				}
				$bar->advance();
			}
		}
		$bar->finish();

		$this->info("\n{$count} permissions synced.");
	}
}
