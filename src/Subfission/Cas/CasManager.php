<?php namespace Subfission\Cas;


use phpCAS;

class CasManager {

    /**
     * Array for storing configuration settings.
     */
    protected $config;
    
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
     */
    public function __construct(array $config)
    {
        $this->parseConfig($config);
        if ($this->config['cas_debug'] === true)
        {
            phpCAS::setDebug();
            phpCAS::log('Loaded configuration:'. PHP_EOL . serialize($config));
        } else
        {
            phpCAS::setDebug($this->config['cas_debug']);
        }

        phpCAS::setVerbose($this->config['cas_verbose_errors']);

        session_name($this->config['cas_session_name']);
        
        // Harden session cookie to prevent some attacks on the cookie (e.g. XSS)
        session_set_cookie_params($this->config['cas_session_lifetime'], 
                                  $this->config['cas_session_path'], 
                                  env('APP_DOMAIN'), 
                                  env('HTTPS_ONLY_COOKIES'), 
                                  true);

        $this->configureCas($this->config['cas_proxy'] ? 'proxy' : 'client');

        $this->configureCasValidation();

        // set login and logout URLs of the CAS server
        phpCAS::setServerLoginURL($this->config['cas_login_url']);

        // If specified, this will override the URL the user will be returning to.
        if ($this->config['cas_redirect_path'])
        {
            phpCAS::setFixedServiceURL($this->config['cas_redirect_path']);
        }

        phpCAS::setServerLogoutURL($this->config['cas_logout_url']);
        
        if ($this->config['cas_masquerade'])
        {
            $this->_masquerading = true;
            phpCAS::log('Masquerading as user: '. $this->config['cas_masquerade']);
        }
    }

    /**
     * Configure CAS Client|Proxy
     *
     * @param $method
     */
    protected function configureCas($method = 'client')
    {
        $server_type = $this->config['cas_enable_saml'] ? SAML_VERSION_1_1 : CAS_VERSION_2_0;
        phpCAS::$method($server_type, $this->config['cas_hostname'], (int) $this->config['cas_port'],
            $this->config['cas_uri'], $this->config['cas_control_session']);

        if ($this->config['cas_enable_saml'])
        {
            // Handle SAML logout requests that emanate from the CAS host exclusively.
            // Failure to restrict SAML logout requests to authorized hosts could
            // allow denial of service attacks where at the least the server is
            // tied up parsing bogus XML messages.
            phpCAS::handleLogoutRequests(true, explode(',', $this->config['cas_real_hosts']));
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
            'cas_hostname'        => '',
            'cas_session_name'    => 'CASAuth',
            'cas_session_lifetime'=> 7200,
            'cas_session_path'    => '/',
            'cas_control_session' => false,
            'cas_port'            => 443,
            'cas_uri'             => '/cas',
            'cas_validation'      => '',
            'cas_cert'            => '',
            'cas_proxy'           => false,
            'cas_validate_cn'     => true,
            'cas_login_url'       => '',
            'cas_logout_url'      => 'https://cas.myuniv.edu/cas/logout',
            'cas_logout_redirect' => '',
            'cas_redirect_path'   => '',
            'cas_enable_saml'     => true,
            'cas_debug'           => false,
            'cas_verbose_errors'  => false,
            'cas_masquerade'      => ''
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
        if ($this->config['cas_validation'] == 'ca' || $this->config['cas_validation'] == 'self')
        {
            phpCAS::setCasServerCACert($this->config['cas_cert'], $this->config['cas_validate_cn']);
        } else
        {
            // Not safe (does not validate your CAS server)
            phpCAS::setNoCasServerValidation();
        }
    }

    /**
     * Authenticates the user based on the current request.
     *
     * @return bool
     */
    public function authenticate()
    {
        if ($this->isMasquerading())
        {
            return true;
        }
        return phpCAS::forceAuthentication();
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
        if ($this->isMasquerading())
        {
            return $this->config['cas_masquerade'];
        }
        return phpCAS::getUser();
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
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($this->isMasquerading()) 
        {
            if ($this->hasAttribute($key))
            {
                return $this->_attributes[$key];
            }
        } else {
            return phpCAS::getAttribute($key);
        }
    }
    
    /**
     * Check for the existence of a key in attributes.
     *
     * @param $key
     * @return boolean
     */    
    public function hasAttribute($key)
    {
        if ($this->isMasquerading()) 
        {
            return array_key_exists($key, $this->_attributes);
        }
        return phpCAS::hasAttribute($key);
    }

    /**
     * Logout of the CAS session and redirect users.
     *
     * @param string $url
     */
    public function logout($url = '')
    {
        if (phpCAS::isSessionAuthenticated())
        {
            if(isset($_SESSION['phpCAS']))
            {
                $serialized = serialize($_SESSION['phpCAS']);
            }
            phpCAS::log('Logout requested, but no session data found for user:'. PHP_EOL . $serialized );
        }
        $params = [];
        if ($this->config['cas_logout_redirect'])
        {
            $params['service'] = $this->config['cas_logout_redirect'];
        } 
        if($url)
        {
            $params['url'] = $url;
        }
        empty($params) ? phpCAS::logout() : phpCAS::logout($params);
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
        return $this->isMasquerading() ? $this->_attributes : phpCAS::getAttributes();
    }

    /**
     * Checks to see is user is authenticated
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->isMasquerading() ? true : phpCAS::isAuthenticated();
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
     * @param array $attr: the attributes of the user.
     */
    public function setAttributes(array $attr)
    {
        $this->_attributes = $attr;
        phpCAS::log('Forced setting of user masquerading attributes: ' . serialize($attr));
    }
    
    /**
     * Pass through undefined methods to phpCAS
     *
     * @param $method
     * @param $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (method_exists('phpCAS', $method) && is_callable(['phpCAS', $method]))
        {
            return call_user_func_array(['phpCAS', $method], $params);
        }
        throw new \BadMethodCallException('Method not callable in phpCAS ' . $method . '::' . print_r($params, true));
    }
}
