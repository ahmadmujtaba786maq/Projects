<?php

namespace AmeliaHttp\Message\Authentication;

use AmeliaHttp\Message\Authentication;
use AmeliaPsr\Http\Message\RequestInterface;

/**
 * Authenticate a PSR-7 Request with a multiple authentication methods.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class Chain implements Authentication
{
    /**
     * @var Authentication[]
     */
    private $authenticationChain = [];

    /**
     * @param Authentication[] $authenticationChain
     */
    public function __construct(array $authenticationChain = [])
    {
        foreach ($authenticationChain as $authentication) {
            if (!$authentication instanceof Authentication) {
                throw new \InvalidArgumentException(
                    'Members of the authentication chain must be of type AmeliaHttp\Message\Authentication'
                );
            }
        }

        $this->authenticationChain = $authenticationChain;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestInterface $request)
    {
        foreach ($this->authenticationChain as $authentication) {
            $request = $authentication->authenticate($request);
        }

        return $request;
    }
}
