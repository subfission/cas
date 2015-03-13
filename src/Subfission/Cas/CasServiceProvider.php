<?php namespace Subfission\Cas;

use Illuminate\Support\ServiceProvider;
use App;

class CasServiceProvider extends ServiceProvider {


	protected $session;
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
	}
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('cas', function()
		{
			return new CasManager(Config::get('cas'), App::make('auth'), App::make('session'));
		});
	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('cas');
	}

}
