<?php namespace Subfission\Cas\Middleware;

use App;
use Config;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Session\SessionManager;

class CASAuth {

	protected $config;
	protected $auth;
	protected $session;

	public function __construct(AuthManager $auth, SessionManager $session)
	{
		$this->config = Config::get('cas');
		$this->auth = App::make('auth');
		$this->session = App::make('session');
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		if ($this->auth->guest())
		{
			if ($request->ajax())
			{
				return response('Unauthorized.', 401);
			}
			else
			{
				$cas = (App::make('cas'));
				$cas->authenticate();
			}
		}

		return $next($request);
	}
}
