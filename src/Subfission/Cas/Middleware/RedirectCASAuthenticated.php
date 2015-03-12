<?php namespace Subfission\Cas\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use App;
use Config;

class RedirectCASAuthenticated {

	protected $auth;
	protected $cas;

	public function __construct(Guard $auth)
	{
		$this->auth = $auth;
		$this->cas = (App::make('cas'));
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
		if($this->cas->isAuthenticated())
		{
            $redirect_path = Config::get('redirect_path');
			return redirect( $redirect_path ? $redirect_path : 'home');
		}

		return $next($request);
	}

}
