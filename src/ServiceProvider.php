<?php

namespace Mont4\LaravelMaker;

use Mont4\LaravelMaker\Commands\RemoveAll;
use Mont4\LaravelMaker\Commands\MakeAll;
use Mont4\LaravelMaker\Commands\MakeMethod;
use Mont4\LaravelMaker\Commands\SyncPermission;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'mont4');
		// $this->loadViewsFrom(__DIR__.'/../resources/views', 'mont4');
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
		// $this->loadRoutesFrom(__DIR__.'/routes.php');

		// Publishing is only necessary when using the CLI.
		if ($this->app->runningInConsole()) {
			$this->bootForConsole();
		}
	}

	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/laravel_maker.php', 'laravel_maker');
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

	/**
	 * Console-specific booting.
	 *
	 * @return void
	 */
	protected function bootForConsole()
	{
		// Publishing the configuration file.
		$this->publishes([
			__DIR__ . '/../config/laravel_maker.php' => config_path('laravel_maker.php'),
		], 'LaravelMaker');
		$this->publishes([
			__DIR__ . '/../config/permission.php' => config_path('permission.php'),
		], 'LaravelMaker');

		// Publishing the views.
		/*$this->publishes([
			__DIR__.'/../resources/views' => base_path('resources/views/vendor/mont4'),
		], 'laravelmaker.views');*/

		// Publishing assets.
		/*$this->publishes([
			__DIR__.'/../resources/assets' => public_path('vendor/mont4'),
		], 'laravelmaker.views');*/

		// Publishing the translation files.
		/*$this->publishes([
			__DIR__.'/../resources/lang' => resource_path('lang/vendor/mont4'),
		], 'laravelmaker.views');*/

		if (!class_exists('CreatePermissionTables')) {
			$timestamp = date('Y_m_d_His', time());
			$this->publishes([
				__DIR__ . '/../database/migrations/create_permission_tables.php.stub' => $this->app->databasePath() . "/migrations/{$timestamp}_create_permission_tables.php",
			], 'LaravelMaker');
		}

		// Registering package commands.
		$this->commands([
			MakeAll::class,
			RemoveAll::class,
			MakeMethod::class,
			SyncPermission::class,
		]);
	}
}
