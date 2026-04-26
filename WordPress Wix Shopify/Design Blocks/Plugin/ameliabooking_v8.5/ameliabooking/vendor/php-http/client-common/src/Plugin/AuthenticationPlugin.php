<?php

namespace AmeliaHttp\Client\Common\Plugin;

use AmeliaHttp\Client\Common\Plugin;
use AmeliaHttp\Message\Authentication;
use AmeliaPsr\Http\Message\RequestInterface;

/**
 * Send an authenticated request.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class AuthenticationPlugin implements Plugin
{
    /**
     * @var Authentication An authentication system
     */
    private $authentication;

    /**
     * @param Authentication $authentication
     */
    public function __construct(Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $request = $this->authentication->authenticate($request);

        return $next($request);
    }
}
