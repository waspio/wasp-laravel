<?php
/**
 * WaspServiceProvider.php
 *
 * @version 1.0
 * @date 11/26/17 11:26 PM
 * @package wasp-laravel
 */

namespace Wasp\Reporting;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;

class WaspServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application events.
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../config/wasp.php' => config_path( 'wasp.php' ),
		] );

		//$this->mergeConfigFrom( __DIR__.'/../config/wasp.php', 'wasp' );
	}


	public function register()
	{
		$this->app->singleton( 'wasp', function (Container $app ) {

			$config = $app->config->get( 'wasp' );
			$debug = $app->config->get( 'app.debug' );
			if( $debug )
			{
				$api_key = $config['api_key'];
				unset( $config['api_key'] );
				$client = new Wasp( $api_key, $config );
				return $client;
			}

		} );

		$this->app->alias( 'wasp', Wasp::class );
	}


	public function provides()
	{
		return [ 'wasp' ];
	}

}