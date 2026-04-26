<?php

namespace AmeliaBooking\Infrastructure\Services\Authentication;

use AmeliaBooking\Infrastructure\Common\Container;

class StarterSocialAuthenticationService extends AbstractSocialAuthenticationService
{
    /**
     * SocialAuthenticationService constructor.
     *
     * @param Container $container
     */
    public function __construct(
        Container $container
    ) {
        $this->container = $container;
    }

    /**
     * Exchange Google Authorization Code for Access Token.
     */
    public function getGoogleUserProfile($accessToken)
    {
        return [];
    }

    /**
     * Exchange Facebook Authorization Code for Access Token.
     */
    public function getFacebookUserProfile($code, $redirectUrl)
    {
        return [];
    }
}
