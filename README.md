# CAS
Simple CAS Authentication for Laravel 5

This version is a highly modified version of "xavrsl/cas" with specific priority on simplicity and functionality with 
Laravel 5.  If you are familiar with "xavrsl/cas", then you will find this version very easy to utilize. This package
was built for my neccessity but can be easily used for anyone requiring CAS in Laravel 5.

## Updates
### Added
- Custom SSO Cookie Names
- Middleware for Authentication
- Middleware for redirecting Authenticated Users

### Removed
- CAS SSO Proxy Services
- Multiple CAS connections

## Installation

Require this package in your composer.json and run composer update (or run `composer require subfission/cas:dev-master` 
directly):

    "subfission/cas": "dev-master"

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

    'Subfission\Cas\CasServiceProvider',

Add the middelware to your Kernel.php file :

	'cas.auth'  => 'Subfission\Cas\Middleware\CASAuth',
	'cas.guest' => 'Subfission\Cas\Middleware\RedirectCASAuthenticated',

As well as the Facade to your app.php config file :

	'Cas' => 'Subfission\Cas\Facades\Cas',

You need to publish the configuration file :

    $ php artisan config:publish subfission/cas

Configuration
==
Edit the cas.php configuration file after you publish it, with your server's settings.  

Configuration should be pretty straightforward for anyone who's ever used the PHPCas client.

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

Then get the current user id this way :

	Cas::getCurrentUser();
