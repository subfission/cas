<?php

namespace Subfission\Cas;

use phpCAS;
use Psr\Log\LoggerInterface;

class PhpCasProxy
{
    public function setVerbose(bool $verbose): void
    {
        phpCAS::setVerbose($verbose);
    }

    public function setServerLoginURL(string $url = ''): void
    {
        phpCAS::setServerLoginURL($url);
    }

    public function setFixedServiceURL(string $url): void
    {
        phpCAS::setFixedServiceURL($url);
    }

    public function setServerLogoutURL(string $url = ''): void
    {
        phpCAS::setServerLogoutURL($url);
    }

    public function log(string $str): void
    {
        phpCAS::log($str);
    }

    // TODO remove use of this
    public function setDebug():void
    {
        // deprecated
    }

    public function setLogger(LoggerInterface $logger = null): void
    {
        phpCAS::setLogger($logger);
    }

    public function client(string $server_version, string $server_hostname,
                          int $server_port, string $server_uri,
                          bool $changeSessionID = true, \SessionHandlerInterface $sessionHandler = null
    ): void
    {
        phpCAS::client($server_version, $server_hostname, $server_port, $server_uri, $changeSessionID, $sessionHandler);
    }

    public function proxy(string $server_version, string $server_hostname,
                          int $server_port, string $server_uri,
                          bool $changeSessionID = true, \SessionHandlerInterface $sessionHandler = null
    ): void
    {
        phpCAS::proxy($server_version, $server_hostname, $server_port, $server_uri, $changeSessionID, $sessionHandler);
    }

    public function handleLogoutRequests(bool $check_client = true, array $allowed_clients = []): void
    {
        phpCAS::handleLogoutRequests($check_client, $allowed_clients);
    }

    public function setCasServerCACert(string $cert, bool $validate_cn = true): void
    {
        phpCAS::setCasServerCACert($cert, $validate_cn);
    }

    public function setNoCasServerValidation(): void
    {
        phpCAS::setNoCasServerValidation();
    }

    public function forceAuthentication(): bool
    {
        return phpCAS::forceAuthentication();
    }

    public function getUser(): string
    {
        return phpCAS::getUser();
    }

    public function getAttribute(string $key)
    {
        return phpCAS::getAttribute($key);
    }

    public function hasAttribute(string $key): bool
    {
        return phpCAS::hasAttribute($key);
    }

    public function isSessionAuthenticated(): bool
    {
        return phpCAS::isSessionAuthenticated();
    }

    public function logout($params = ''): void
    {
        phpCAS::logout($params);
    }

    public function isAuthenticated(): bool
    {
        return phpCAS::isAuthenticated();
    }

    public function checkAuthentication(): bool
    {
        return phpCAS::checkAuthentication();
    }

    /**
     * Pass through undefined methods to phpCAS
     *
     * @param $method
     * @param $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (
            method_exists('phpCAS', $method)
            && is_callable([
                'phpCAS',
                $method
            ])
        ) {
            return call_user_func_array(['phpCAS', $method], $params);
        }
        throw new \BadMethodCallException('Method not callable in phpCAS '
            . $method . '::' . print_r(
                $params,
                true
            ));
    }
}
