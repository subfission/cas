<?php namespace Subfission\Cas;

use Illuminate\Auth\AuthManager;

class CasManager {

    var $config;
    /**
     * The active connection instance.
     *
     * @var string
     */
    protected $connections;
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * @param array $config
     */
    public function __construct()
    {
        $this->config = config('cas');
        $this->auth = app('auth');
    }

    /**
     * Get a Cas connection instance.
     *
     * @param string $name
     * @return app\Cas\Directory
     */
    public function connection()
    {
        if ( ! isset($this->connections) )
        {
            $this->connections = $this->createConnection();
        }

        return $this->connections;
    }

    /**
     * Create the given connection by name.
     *
     * @return app\Cas\Sso
     */
    protected function createConnection()
    {
        return new Sso($this->config, $this->auth);
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
