<?php namespace Subfission\Cas;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Session;
use phpCAS;

/**
 * CAS authenticator
 *
 * @package Cas
 */
class Sso {

    /**
     * Cas Config
     *
     * @var array
     */
    protected $config;
    /**
     * Current CAS user
     *
     * @var string
     */
    protected $remoteUser;
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    private $isAuthenticated;

    /**
     * @param $config
     * @param Auth $auth
     */
    public function __construct($config, AuthManager $auth)
    {
        $this->config = $config;
        $this->auth = $auth;
        $this->cas_init();
    }

    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is successful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @return bool
     */
    public function authenticate()
    {
        // attempt to authenticate with CAS server
        if ( phpCAS::forceAuthentication() )
        {
            // retrieve authenticated credentials
            $this->setRemoteUser();

            return true;
        } else return false;
    }

    /**
     * Checks to see is user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * Returns information about the currently logged in user.
     *
     * If nobody is currently logged in, this method should return null.
     *
     * @return array|null
     */
    public function getCurrentUser()
    {
        return $this->remoteUser;
    }

    /**
     * getCurrentUser Alias
     *
     * @return array|null
     */
    public function user()
    {
        return $this->getCurrentUser();
    }

    public function logout()
    {
        if ( phpCAS::isSessionAuthenticated() )
        {
            if ( $this->auth->check() )
            {
                $this->auth->logout();
            }
            Session::flush();
            phpCAS::logout();
            exit;
        }
    }

    /**
     * Make PHPCAS Initialization
     *
     * Initialize a PHPCAS token request
     *
     * @return none
     */
    private function cas_init()
    {
        session_name( (isset($this->config['session_name']) ? $this->config['session_name'] : 'CASAuth' ));
        // initialize CAS client
        $this->configureCasClient();
        $this->configureSslValidation();
        $this->detect_authentication();

        // set service URL for authorization with CAS server
        //\phpCAS::setFixedServiceURL();
        if ( ! empty($this->config[ 'cas_service' ]) )
        {
            phpCAS::allowProxyChain(new \CAS_ProxyChain_Any);
        }
        // set login and logout URLs of the CAS server
        phpCAS::setServerLoginURL($this->config[ 'cas_login_url' ]);
        phpCAS::setServerLogoutURL($this->config[ 'cas_logout_url' ]);
    }

    /**
     * Configure CAS Client
     *
     * @param $cfg
     */
    private function configureCasClient()
    {
        phpCAS::client(CAS_VERSION_2_0, $this->config[ 'cas_hostname' ], $this->config[ 'cas_port' ], $this->config[ 'cas_uri' ], false);
    }

    private function configureSslValidation()
    {
        // set SSL validation for the CAS server
        if ( $this->config[ 'cas_validation' ] == 'self' )
        {
            phpCAS::setCasServerCert($this->config[ 'cas_cert' ]);
        } else if ( $this->config[ 'cas_validation' ] == 'ca' )
        {
            phpCAS::setCasServerCACert($this->config[ 'cas_cert' ]);
        } else
        {
            phpCAS::setNoCasServerValidation();
        }
    }

    /**
     * Set Remote User
     */
    private function setRemoteUser()
    {
        $this->remoteUser = phpCAS::getUser();
        Session::put('cas_user', $this->remoteUser);
    }

    private function detect_authentication()
    {
        if ( ($this->isAuthenticated = phpCAS::isAuthenticated()) ) $this->setRemoteUser();
    }
}