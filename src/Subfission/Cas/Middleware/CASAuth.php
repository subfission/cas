<?php namespace Subfission\Cas\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class CASAuth {

	protected $config;
	protected $auth;
	protected $session;

	public function __construct(Guard $auth)
	{
        $this->auth = $auth;
		$this->config = config('cas');
		$this->session = app('session');
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
				$cas = app('cas');
				$cas->authenticate();
			}
		}

		return $next($request);
	}
}
