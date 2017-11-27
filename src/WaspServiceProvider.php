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

        $this->app->singleton( WaspHandler::class, function() {

            $config = $app->config->get( 'wasp' );
            $debug = $app->config->get( 'app.debug' );
            if( $debug )
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
