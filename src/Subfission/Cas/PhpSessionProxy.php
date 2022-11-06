<?php

namespace Subfission\Cas;

class PhpSessionProxy
{
    public function headersSent(): bool
    {
        return headers_sent();
    }

    public function sessionId(?string $id = null)
    {
        return session_id($id);
    }

    public function sessionName(?string $name = null)
    {
        return session_name($name);
    }

    public function sessionSetCookieParams(
        int $lifetime_or_options,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httponly = null
    ): bool
    {
        return session_set_cookie_params($lifetime_or_options, $path, $domain, $secure, $httponly);
    }
}
