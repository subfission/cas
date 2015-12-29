# CAS
Simple CAS Authentication for Laravel 5+

This version is a highly modified version of "xavrsl/cas" with specific priority on simplicity and functionality with 
Laravel 5+.  While this will likely still work with older versions, they are untested. This package was built for my 
necessity but can be easily used for anyone requiring CAS in Laravel 5.  This package is different in mindset as the 
goal in this project is to be as minimal as possible while offering as much flexibility as needed.

## Updates

* Session handling has been removed from CAS Manager and is moved strictly into the middleware
* You can now leverage the CAS sessions instead of relying on Laravel sessions
* More security fixes
* Cleaner codebase
* Backwards compatible (for the most part)
* More configuration options in the config file available
* Masquerading as a user now supported
* Tested with PHP7


## Installation

Require this package using composer in your Laravel 5 directory :

    $ composer require "subfission/cas": "dev-master"

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

    'Subfission\Cas\CasServiceProvider',

Add the middelware to your Kernel.php file :

	'cas.auth'  => 'Subfission\Cas\Middleware\CASAuth',
	'cas.guest' => 'Subfission\Cas\Middleware\RedirectCASAuthenticated',

As well as the Facade to your app.php config file :

	'Cas' => 'Subfission\Cas\Facades\Cas',

You need to publish the configuration file :

    $ php artisan vendor:publish

Configuration
==
Edit the cas.php configuration file after you publish it, with your server's settings.  

Read the comments in the config file for further help.

Usage
==

Authenticate against the CAS server

	Cas::authenticate();

Exemple of CAS authentication in a route middleware :

```php
Route::group(['middleware' => ['cas.auth']], function ()
{
  Route::get('home', 'HomeController@index');
});


Route::get('/auth/logout', function()
{
  Cas::logout();
});
```

Then get the current user id in one of these ways :

	Cas::getCurrentUser();
	Cas::user()

Once a user has CAS authenticated, you can also retrieve the user from the session to be used for authorization or
secondary authentication like this :
```php
    $user = session('cas_user')
```