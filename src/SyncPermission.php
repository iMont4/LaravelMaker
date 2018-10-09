<?php

namespace Mont4\LaravelMaker;

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
		$guardNames = config('laravel_maker.permissions');

		$permissionsCount = count($guardNames, COUNT_RECURSIVE);
		$bar              = $this->output->createProgressBar($permissionsCount);

		$count = 0;
		foreach ($guardNames as $guardName => $namespaces) {
			foreach ($namespaces as $namespace => $controllers) {
				foreach ($controllers as $controller => $permissions) {
					foreach ($permissions as $permission) {

						$permissionClass = config('permission.models.permission');

						$permissionEntity = call_user_func([$permissionClass, 'query']);

						$permissionName      = sprintf("%s.%s.%s", $namespace, $controller, $permission);
						$availablePermission = $permissionEntity->where('name', '=', $permissionName)
							->where('guard_name', '=', $guardName)
							->first();

						if (!$availablePermission) {
							$permissionEntity->create([
								'guard_name' => $guardName,
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
		}
		$bar->finish();


		$this->info("\n{$count} permissions synced.");
	}
}
