<?php

namespace Wasp\WaspLaravel;

use Illuminate\Support\ServiceProvider;

class WaspServiceProvider extends ServiceProvider
{
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
        ] );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton( 'wasp', function( $app ) {

            $config = $app->config->get( 'wasp' );
            $debug = $app->config->get( 'app.debug' );
            if( $debug )
            {
                $api_key = $config['api_key'];
                unset( $config['api_key'] );
                return new WaspHandler( $api_key, $config );
            }

        } );

        $this->app->alias( 'wasp', WaspHandler::class );
    }


    public function provides()
    {
        return [ 'wasp' ];
    }
}
