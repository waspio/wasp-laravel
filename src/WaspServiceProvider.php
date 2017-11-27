<?php

namespace Wasp\WaspLaravel;

use Illuminate\Support\ServiceProvider;

class WaspServiceProvider extends ServiceProvider
{
	
	protected $defer = false;
	
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->publishes([
            __DIR__ .'/../config/wasp.php' => config_path( 'wasp.php' )
        ], 'config' );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( __DIR__ .'/../config/wasp.php', 'wasp' );
        $this->app->singleton( WaspHandler::class, function( $app ) {

            $config = $app->config->get( 'wasp' );
            $debug = $app->config->get( 'app.debug' );
            if( $debug && isset( $config['api_key'] ) )
            {
                $api_key = $config['api_key'];
                unset( $config['api_key'] );
                return new WaspHandler( $api_key, $config );
            }

        } );

        $this->app->alias( WaspHandler::class, 'wasp' );
    }


    public function provides()
    {
        return [ 'wasp', WaspHandler::class ];
    }
	
	
	protected function publishConfig()
	{
        $this->publishes([
            __DIR__ .'/../config/wasp.php' => config_path( 'wasp.php' )
        ], 'config' );
	}
}