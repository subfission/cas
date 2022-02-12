<?php

namespace Subfission\Cas\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool authenticate()
 * @method static array getConfig()
 * @method static string user()
 * @method static string getCurrentUser()
 * @method static mixed getAttribute($key)
 * @method static bool hasAttribute($key)
 * @method static logout($url = '', $service = '')
 * @method static logoutWithUrl($url)
 * @method static mixed getAttributes()
 * @method static boolean isAuthenticated()
 * @method static boolean checkAuthentication()
 * @method static bool isMasquerading()
 * @method static setAttributes(array $attr)
 *
 * @see \Subfission\Cas\CasManager
 */
class Cas extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * Futher CAS Documentation: http://downloads.jasig.org/cas-clients/php/1.1.0RC7/docs/api/group__publicAuth.html#
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cas';
    }
}
