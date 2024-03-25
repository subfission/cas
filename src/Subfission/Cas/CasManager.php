<?php

namespace Subfission\Cas;

use Psr\Log\LoggerInterface;

class CasManager
{
    /**
     * Array for storing configuration settings.
     */
    protected $config;

    /**
     * @var LoggerInterface|null
     */
    private $logger = null;

    /**
     * Proxy object for the phpCAS global
     * @var PhpCasProxy
     */
    protected $casProxy;

    /**
     * Proxy object for the PHP built-in functions we use
     * @var PhpSessionProxy
     */
    protected $sessionProxy;

    /**
     * @var LogoutStrategy
     */
    private $logoutStrategy;

    /**
     * Attributes used for overriding or masquerading.
     */
    protected $_attributes = [];

    /**
     * Boolean flag used for masquerading as a user.
     */
    protected $_masquerading = false;

    /**
     * @param array $config
     * @param PhpCasProxy|null $casProxy
     * @param PhpSessionProxy|null $sessionProxy
     */
    public function __construct(
        array           $config,
        PhpCasProxy     $casProxy = null,
        PhpSessionProxy $sessionProxy = null,
        LogoutStrategy  $logoutStrategy = null
    ) {
        $this->casProxy = $casProxy ?? new PhpCasProxy();
        $this->sessionProxy = $sessionProxy ?? new PhpSessionProxy();
        $this->logoutStrategy = $logoutStrategy ?? new LogoutStrategy();

        $this->parseConfig($config);

        $this->casProxy->setVerbose($this->config['cas_verbose_errors']);

        // Fix for PHP 7.2.  See http://php.net/manual/en/function.session-name.php
        if (!$this->sessionProxy->headersSent() && $this->sessionProxy->sessionGetId() === "") {
            $this->sessionProxy->sessionSetName($this->config['cas_session_name']);

            // Harden session cookie to prevent some attacks on the cookie (e.g. XSS)
            $this->sessionProxy->sessionSetCookieParams(
                $this->config['cas_session_lifetime'],
                $this->config['cas_session_path'],
                $this->config['cas_session_domain'],
                $this->config['cas_session_secure'],
                $this->config['cas_session_httponly']
            );
        }

        $this->configureCas($this->config['cas_proxy'] ? 'proxy' : 'client');

        $this->configureCasValidation();

        // set login and logout URLs of the CAS server
        $this->casProxy->setServerLoginURL($this->config['cas_login_url']);

        // If specified, this will override the URL the user will be returning to.
        if ($this->config['cas_redirect_path']) {
            $this->casProxy->setFixedServiceURL($this->config['cas_redirect_path']);
        }

        $this->casProxy->setServerLogoutURL($this->config['cas_logout_url']);

        if ($this->config['cas_masquerade']) {
            $this->_masquerading = true;
            $this->casProxy->log('Masquerading as user: '
                . $this->config['cas_masquerade']);
        }
    }

    /**
     * Sets a PSR-3 compatible logger, or null to disable logging
     *
     * @param LoggerInterface|null $logger
     * @return void
     */
    public function setLogger(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
        $this->casProxy->setLogger($this->logger);
    }

    /**
     * Configure CAS Client|Proxy
     *
     * @param $method
     */
    protected function configureCas($method = 'client')
    {
        if ($this->config['cas_enable_saml']) {
            $server_type = $this->casProxy->serverTypeSaml();
        } else {
            $server_type = $this->casProxy->serverTypeCas($this->config['cas_version']);
        }

        $this->casProxy->$method(
            $server_type,
            $this->config['cas_hostname'],
            (int) $this->config['cas_port'],
            $this->config['cas_uri'],
            $this->config['cas_client_service'],
            $this->config['cas_control_session']
        );

        if ($this->config['cas_enable_saml']) {
            // Handle SAML logout requests that emanate from the CAS host exclusively.
            // Failure to restrict SAML logout requests to authorized hosts could
            // allow denial of service attacks where at the least the server is
            // tied up parsing bogus XML messages.
            $this->casProxy->handleLogoutRequests(
                true,
                explode(',', $this->config['cas_real_hosts'])
            );
        }
    }

    /**
     * Maintain backwards compatibility with config file
     *
     * @param array $config
     */
    protected function parseConfig(array $config)
    {
        $defaults = [
            'cas_hostname'         => '',
            'cas_session_name'     => 'CASAuth',
            'cas_session_lifetime' => 7200,
            'cas_session_path'     => '/',
            'cas_control_session'  => false,
            'cas_session_httponly' => true,
            'cas_port'             => 443,
            'cas_uri'              => '/cas',
            'cas_validation'       => '',
            'cas_cert'             => '',
            'cas_proxy'            => false,
            'cas_validate_cn'      => true,
            'cas_login_url'        => '',
            'cas_logout_url'       => 'https://cas.myuniv.edu/cas/logout',
            'cas_logout_redirect'  => '',
            'cas_redirect_path'    => '',
            'cas_enable_saml'      => true,
            'cas_version'          => "2.0",
            'cas_verbose_errors'   => false,
            'cas_masquerade'       => '',
            'cas_session_domain'   => '',
            'cas_session_secure'   => false,
            'cas_client_service'   => '',
            'cas_real_hosts'       => '',
        ];

        $this->config = array_merge($defaults, $config);
    }

    /**
     * Configure SSL Validation
     *
     * Having some kind of server cert validation in production
     * is highly recommended.
     */
    protected function configureCasValidation()
    {
        if (
            $this->config['cas_validation'] == 'ca'
            || $this->config['cas_validation'] == 'self'
        ) {
            $this->casProxy->setCasServerCACert(
                $this->config['cas_cert'],
                $this->config['cas_validate_cn']
            );
        } else {
            // Not safe (does not validate your CAS server)
            $this->casProxy->setNoCasServerValidation();
        }
    }

    /**
     * Authenticates the user based on the current request.
     *
     * @return bool
     */
    public function authenticate()
    {
        if ($this->isMasquerading()) {
            return true;
        }

        return $this->casProxy->forceAuthentication();
    }

    /**
     * Returns the current config.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Retrieve authenticated credentials.
     * Returns either the masqueraded account or the phpCAS user.
     *
     * @return string
     */
    public function user()
    {
        if ($this->isMasquerading()) {
            return $this->config['cas_masquerade'];
        }

        return $this->casProxy->getUser();
    }

    public function getCurrentUser()
    {
        return $this->user();
    }

    /**
     * Retrieve a specific attribute by key name.  The
     * attribute returned can be either a string or
     * an array based on matches.
     *
     * @param $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (!$this->isMasquerading()) {
            return $this->casProxy->getAttribute($key);
        }
        if ($this->hasAttribute($key)) {
            return $this->_attributes[$key];
        }

        return;
    }

    /**
     * Check for the existence of a key in attributes.
     *
     * @param $key
     *
     * @return boolean
     */
    public function hasAttribute($key)
    {
        if ($this->isMasquerading()) {
            return array_key_exists($key, $this->_attributes);
        }

        return $this->casProxy->hasAttribute($key);
    }

    /**
     * Logout of the CAS session and redirect users.
     *
     * @param string $url
     * @param string $service
     */
    public function logout($url = '', $service = '')
    {
        if ($this->casProxy->isSessionAuthenticated()) {
            if (isset($_SESSION['phpCAS'])) {
                $serialized = serialize($_SESSION['phpCAS']);
                $this->casProxy->log('Logout requested, but no session data found for user:'
                    . PHP_EOL . $serialized);
            }
        }
        $params = [];
        if ($service) {
            $params['service'] = $service;
        } elseif ($this->config['cas_logout_redirect']) {
            $params['service'] = $this->config['cas_logout_redirect'];
        }
        if ($url) {
            $params['url'] = $url;
        }

        $this->casProxy->logout($params);

        $this->logoutStrategy->completeLogout();
    }


    /**
     * Logout the user using the provided URL.
     *
     * @param $url
     */
    public function logoutWithUrl($url)
    {
        $this->logout($url);
    }

    /**
     * Get the attributes for for the currently connected user. This method
     * can only be called after authenticate() or an error wil be thrown.
     *
     * @return mixed
     */
    public function getAttributes()
    {
        // We don't error check because phpCAS has its own error handling.
        return $this->isMasquerading() ? $this->_attributes
            : $this->casProxy->getAttributes();
    }

    /**
     * Checks to see is user is authenticated locally
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->isMasquerading() ? true : $this->casProxy->isAuthenticated();
    }

    /**
     * Checks to see is user is globally in CAS
     *
     * @return boolean
     */
    public function checkAuthentication()
    {
        return $this->isMasquerading() ? true : $this->casProxy->checkAuthentication();
    }

    /**
     * Checks to see if masquerading is enabled
     *
     * @return bool
     */
    public function isMasquerading()
    {
        return $this->_masquerading;
    }

    /**
     * Set the attributes for a user when masquerading. This
     * method has no effect when not masquerading.
     *
     * @param array $attr : the attributes of the user.
     */
    public function setAttributes(array $attr)
    {
        $this->_attributes = $attr;
        $this->casProxy->log('Forced setting of user masquerading attributes: '
            . serialize($attr));
    }

    /**
     * Pass through undefined methods to PhpCasProxy
     *
     * @param $method
     * @param $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        return call_user_func_array([$this->casProxy, $method], $params);
    }
}
