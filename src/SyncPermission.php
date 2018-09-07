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
		$permissions = config('laravel_maker.permissions');
		dd($permissions);

		$permissionsCount = count($permissions, COUNT_RECURSIVE);
		$bar = $this->output->createProgressBar($permissionsCount);

		$count = 0;
		foreach ($permissions as $namespace => $controllers) {
			foreach ($controllers as $controller => $permissions) {
				foreach ($permissions as $permission) {

					$permissionClass = config('permission.models.permission');

					$permissionEntity = call_user_func([$permissionClass, 'query']);

					$permissionName      = sprintf("%s.%s.%s", $namespace, $controller, $permission);
					$availablePermission = $permissionEntity->where('name', '=', $permissionName)
						->first();

					if (!$availablePermission) {
						$permissionEntity->create([
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
		$bar->finish();


		$this->info("\n{$count} permissions synced.");
	}
}
