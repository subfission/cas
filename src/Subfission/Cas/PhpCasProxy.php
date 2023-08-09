<?php

namespace Subfission\Cas;

use phpCAS;
use Psr\Log\LoggerInterface;

class PhpCasProxy
{
    public function serverTypeSaml(): string
    {
        // This constant is defined by the phpCAS class and is not a public class constant
        return SAML_VERSION_1_1;
    }

    public function serverTypeCas(string $version): string
    {
        // This allows the user to use 1.0, 2.0, etc as a string in the config
        $cas_version_str = 'CAS_VERSION_' . str_replace('.', '_', $version);

        // We pull the phpCAS constant values as this is their definition
        // PHP will generate a E_WARNING if the version string is invalid which is helpful for troubleshooting
        $server_type = constant($cas_version_str);

        if (is_null($server_type)) {
            // This will never be null, but can be invalid values for which we need to detect and substitute.
            $this->log('Invalid CAS version set; Reverting to defaults');
            $server_type = CAS_VERSION_2_0;
        }

        return $server_type;
    }

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

    public function client(
        string $server_version,
        string $server_hostname,
        int $server_port,
        string $server_uri,
        string $service_base_url,
        bool $changeSessionID = true,
        \SessionHandlerInterface $sessionHandler = null
    ): void {
        phpCAS::client($server_version, $server_hostname, $server_port, $server_uri, $service_base_url, $changeSessionID, $sessionHandler);
    }

    public function proxy(
        string $server_version,
        string $server_hostname,
        int $server_port,
        string $server_uri,
        string $service_base_url,
        bool $changeSessionID = true,
        \SessionHandlerInterface $sessionHandler = null
    ): void {
        phpCAS::proxy($server_version, $server_hostname, $server_port, $server_uri, $service_base_url, $changeSessionID, $sessionHandler);
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

    public function getAttributes(): array
    {
        return phpCAS::getAttributes();
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
