<?php namespace Subfission\Cas\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class CASAuth
{

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
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if( $this->cas->isAuthenticated() )
        {
            // Store the user credentials in a Laravel managed session
            session()->put('cas_user', $this->cas->user());
            // manage the internal login process if the cas user is found in internal db
            $config = $this->cas->getConfig();
            $user = User::where($config['user_cas_attribute'], $this->cas->user())->first();
            if (($config['use_laravel_sessions']) && (isset($user))) {
                Auth::login($user);
            }
        } else {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }
            $this->cas->authenticate();
        }

        return $next($request);
    }
}
