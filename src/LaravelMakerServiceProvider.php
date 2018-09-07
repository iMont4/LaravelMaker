<?php

namespace Mont4\LaravelMaker;

use Illuminate\Support\ServiceProvider;

class LaravelMakerServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Publishing is only necessary when using the CLI.
		if ($this->app->runningInConsole()) {

			// Publishing the configuration file.
			$this->publishes([
				__DIR__ . '/../config/laravel_maker.php' => config_path('laravel_maker.php'),
			], 'laravel_maker.config');

			// Registering package commands.
			$this->commands([
				MakeCommand::class,
				SyncPermission::class,
			]);

			if (isNotLumen()) {
				$this->publishes([
					__DIR__ . '/../config/permission.php' => config_path('permission.php'),
				], 'config');

				if (!class_exists('CreatePermissionTables')) {
					$timestamp = date('Y_m_d_His', time());

					$this->publishes([
						__DIR__ . '/../database/migrations/create_permission_tables.php.stub' => $this->app->databasePath() . "/migrations/{$timestamp}_create_permission_tables.php",
					], 'migrations');
				}
			}

		}
	}

	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/laravel_maker.php', 'laravelmaker');

		// Register the service the package provides.
		$this->app->singleton('laravelmaker', function ($app) {
			return new LaravelMaker;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['laravelmaker'];
	}
}