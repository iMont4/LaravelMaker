<?php

namespace Mont4\LaravelMaker;

use Illuminate\Support\ServiceProvider;
use Mont4\LaravelMaker\Commands\MakeAll;
use Mont4\LaravelMaker\Commands\MakeMethod;
use Mont4\LaravelMaker\Commands\SyncPermission;

class LaravelMakerServiceProvider extends ServiceProvider
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
		], 'laravel+maker.config');
		$this->publishes([
			__DIR__ . '/../config/permission.php' => config_path('permission.php'),
		], 'config');

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

		// Registering package commands.
		$this->commands([
			MakeAll::class,
			MakeMethod::class,
			SyncPermission::class,
		]);
	}
}
