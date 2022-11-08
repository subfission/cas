<?php

namespace Subfission\Cas;

class PhpSessionProxy
{
    public function headersSent(): bool
    {
        return headers_sent();
    }

    public function sessionGetId()
    {
        return session_id();
    }

    public function sessionSetName(string $name = null)
    {
        return session_name($name);
    }

    public function sessionSetCookieParams(
        int $lifetime_or_options,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httponly = null
    ): bool {
        return session_set_cookie_params($lifetime_or_options, $path, $domain, $secure, $httponly);
    }
}
