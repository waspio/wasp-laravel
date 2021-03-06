Wasp.io for Laravel
==============
This package automatically grabs all errors created by Laravel and notifies you in realtime using the wasp.io service.


Description
-----------

**You must have a [Wasp.io](https://wasp.io/) account in order to use this plugin.  Sign up for free, no credit card needed.**

Wasp.io automatically tracks errors generated by your applications, intelligently notifies your team, and provides realtime data feeds of errors and activity for all of your applications by sending the details of generated errors to the Wasp API.

A 14 day free trial is provided for all Wasp users.

Getting Started
------------

1) Sign up for a Wasp account at [https://wasp.io](https://wasp.io/) for an API Key.

2) Add your wasp project API key to your .env file:

```
WASP_API_KEY=[YOURAPIKEY]
```

3) Add the WaspLaravel package to your installation:

```
composer require wasp/wasp-laravel
```

4) Add the following to your app/Exceptions/Handler.php file:

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

5) Then publish the configuration file for easy access and to create the wasp.php configuration file in your app/config/ directory:

```bash 
php artisan vendor:publish --provider='Wasp\WaspLaravel\WaspServiceProvider'
```


Frequently Asked Questions
--------------------------

### Where do I get a Wasp API key?

Once you have a Wasp.io account, and have created a project, navigate to the project settings, and your API key will be shown there.

### Why should I use Wasp?

Errors slow down, and can take down your websites, and often the only notification you get is from a panicking client, or a visitor nice enough to let you know; Wasp.io automatically overrides the default error handling of your applications (or other) sites, and sends those errors (including fatal errors) to the WaspAPI for grouping, filtering, and notification in realtime.

### Where does the data go?

Error details are sent to the Wasp API (all things Waspside are SSL only for security) for filtering, notification, and management through your user account.  Since debugging is already a task, Wasp sends as much information as possible including full stacktrace information, browser information, the code where an error was generated, and user email, user name, and user ID of logged in users if applicable.



Changelog
---------

### 2.2.6
* Remove explicit requirement for \Exception in exception handler

### 2.2.5
* Initial commits for Laravel specific installs


Additional Information
---------
This package sends the details of errors generated by your applications to the Wasp API.  Error details include a full stacktrace (the functions and files through which the error was generated), the code surrounding the file and line where the error was generated, information on the browser, operating system, and other information relating to the visitor generating the error.