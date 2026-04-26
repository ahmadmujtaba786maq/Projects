<?php

namespace AmeliaBooking\Infrastructure\Services\Authentication;

use AmeliaBooking\Infrastructure\Common\Container;

abstract class AbstractSocialAuthenticationService
{
    /** @var Container $container */
    protected $container;
    /**
     * Exchange Google Authorization Code for Access Token.
     */
    abstract public function getGoogleUserProfile($accessToken);

    /**
     * Exchange Facebook Authorization Code for Access Token.
     */
    abstract public function getFacebookUserProfile($code, $redirectUrl);
}
