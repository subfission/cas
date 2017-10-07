# CAS
Simple CAS Authentication for Laravel 5.x

This version is a highly modified version of "xavrsl/cas" with specific priority on simplicity and functionality with 
Laravel 5+.  While this will likely still work with older versions, they are untested. This package was built for my 
necessity but can be easily used for anyone requiring CAS in Laravel 5.  This package is different in mindset as the 
goal in this project is to be as minimal as possible while offering as much flexibility as needed.

## Updates
* Supports additional CAS versions, including version 1,2,3
* Supports direct phpCAS calls for heavily customized CAS configurations
* Supports logon with custom URL redirects
* Supports logoff with redirect callbacks
* Updated to work with Laravel 5.2 (backwards compatible)
* Uses the latest phpCAS
* Supports verbose logging
* Session handling has been removed from CAS Manager and is moved strictly into the middleware
* You can now leverage the CAS sessions instead of relying on Laravel sessions
* More security fixes
* Cleaner codebase
* Backwards compatible (for the most part)
* More configuration options in the config file available
* Masquerading as a user now supported
* Tested and working with PHP 7.x


Check out the [wiki](https://github.com/subfission/cas/wiki) for further details.
