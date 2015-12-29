<?php namespace Subfission\Cas\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class CASAuth {

	protected $auth;
	protected $cas;

	public function __construct(Guard $auth)
	{
        $this->auth = $auth;
		$this->cas = app('cas');
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
		if ($this->auth->guest() || ! $this->cas->isAuthenticated())
		{
			if ($request->ajax())
			{
				return response('Unauthorized.', 401);
			}
			// We setup CAS here to reduce the amount of objects we need to build at runtime.  This
			// way, we only create the CAS calls only if the user has not yet authenticated.
			$this->cas->authenticate();
			session()->put('cas_user', $this->cas->User());
		}

		return $next($request);
	}
}