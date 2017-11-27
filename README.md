# Wasp for Laravel

## This package is under current development and testing 2017-11-27

Getting started:

```
composer require wasp/wasp-laravel
```

Then just add the following 3 lines to your app/Exceptions/Handler.php file:

```php
public function report(Exception $exception)
{

    if( app()->bound( 'wasp' ) && $this->shouldReport( $exception ) )
    {
        app( 'wasp' )->exception_handler( $exception );
    }
    parent::report($exception);
}

```

Then publish the configuration file for easy access and to create the wasp.php configuration file in your app/config/ directory:

```bash
php artisan vendor:publish --provider='Wasp\\WaspLaravel\\WaspServiceProvider'

```