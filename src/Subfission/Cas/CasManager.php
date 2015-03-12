<?php namespace Subfission\Cas;

use Config;
use Illuminate\Auth\AuthManager;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Manager;

class CasManager {

    var $config;
    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = array();
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;
    /**
     * @var \Illuminate\Session\SessionManager
     */
    private $session;

    /**
     * @param array $config
     * @param AuthManager $auth
     * @param SessionManager $session
     */

    public function __construct(AuthManager $auth, SessionManager $session)
    {
        $this->config = Config::get('cas');
        $this->auth = $auth;
        $this->session = $session;
    }

    /**
     * Get a Cas connection instance.
     *
     * @param string $name
     * @return app\Cas\Directory
     */
    public function connection($name = null)
    {
        if ( ! isset($this->connections[ $name ]) )
        {
            $this->connections[ $name ] = $this->createConnection($name);
        }

        return $this->connections[ $name ];
    }

    /**
     * Create the given connection by name.
     *
     * @param string $name
     * @return app\Cas\Sso
     */
    protected function createConnection($name)
    {
        $connection = new Sso($this->config, $this->auth, $this->session);

        return $connection;
    }


    /**
     * Get the default connection name.
     *
     * @return string
     */
    protected function getDefaultConnection()
    {
        return 'default';
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->connection(), $method), $parameters);
    }
}
