<?php

namespace AmeliaBooking\Infrastructure\Services\Authentication;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;

class SocialAuthenticationService extends AbstractSocialAuthenticationService
{
    /**
     * @var SettingsService $settings
     */
    private $settings;

    /**
     * SocialAuthenticationService constructor.
     *
     * @param Container $container
     */
    public function __construct(
        Container $container
    ) {
        $this->container = $container;

        $this->settings = $this->container->get('domain.settings.service')->getCategorySettings('socialLogin');
    }

    /**
     * Exchange Google Authorization Code for Access Token.
     */
    public function getGoogleUserProfile($accessToken)
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo';

        $args = [
            'body' => [
                'id_token' => $accessToken,
            ],
        ];

        $response = wp_remote_get($url . '?' . http_build_query($args['body']));

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return [
            'email'     => $data['email'],
            'firstName' => $data['given_name'],
            'lastName'  => $data['family_name'],
        ];
    }

    /**
     * Exchange Facebook Authorization Code for Access Token.
     */
    public function getFacebookUserProfile($code, $redirectUrl)
    {
        $facebookAppId = $this->settings['facebookAppId'];

        $facebookAppSecret = $this->settings['facebookAppSecret'];

        $response = wp_remote_get(
            'https://graph.facebook.com/oauth/access_token',
            [
                'body' => [
                    'redirect_uri'  => $redirectUrl . '/',
                    'client_id'     => $facebookAppId,
                    'client_secret' => $facebookAppSecret,
                    'code'          => $code,
                ]
            ]
        );

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['error']) {
            return [
                'error' => $data['error']['message'],
            ];
        }

        /** @var string $accessToken */
        $accessToken = isset($data['access_token']) ? $data['access_token'] : null;

        $userResponse = wp_remote_get('https://graph.facebook.com/me?fields=first_name,last_name,email&access_token=' . urlencode($accessToken));

        $userBody = wp_remote_retrieve_body($userResponse);
        $userData = json_decode($userBody, true);

        return [
            'email'     => isset($userData['email']) ? $userData['email'] : null,
            'firstName' => isset($userData['first_name']) ? $userData['first_name'] : null,
            'lastName'  => isset($userData['last_name']) ? $userData['last_name'] : null,
            'token'     => $accessToken,
        ];
    }
}
