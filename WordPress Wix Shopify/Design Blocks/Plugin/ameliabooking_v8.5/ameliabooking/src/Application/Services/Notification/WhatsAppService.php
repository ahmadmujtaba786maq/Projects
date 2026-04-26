<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class WhatsAppService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
class WhatsAppService
{
    /** @var Container */
    private $container;

    public const URL = 'https://graph.facebook.com/v20.0/';

    /**
     * WhatsAppService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $route
     * @param $method
     * @param $data
     * @param bool $authorize
     *
     * @return mixed
     */
    public function sendRequest($route, $method, $data = null, $authorize = true)
    {
        $ch = curl_init($route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        $accessToken     = $settingsService->getSetting('notifications', 'whatsAppAccessToken');

        if ($authorize) {
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ]
            );
        }


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        $response = json_decode($response, true);

        curl_close($ch);

        return $response;
    }

    /**
     * @param $to
     * @param $template
     * @param $components
     * @param string $languageCode
     *
     * @return mixed
     */
    public function send($to, $template, $components, $languageCode)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        $whatsAppPhoneId = $settingsService->getSetting('notifications', 'whatsAppPhoneID');

        $route = self::URL . $whatsAppPhoneId . '/messages';

        $to   = str_replace('+', '', $to);
        $data = [
            "messaging_product" => "whatsapp",
            'to'            => $to,
            "type"          =>  "template",
            "template"      => [
                "name"  => $template,
                "language" => [
                    "code"  => $languageCode
                ],
                "components" => $components
            ]
        ];

        return $this->sendRequest($route, 'POST', $data);
    }


    /**
     * @param string $name
     * @return mixed
     */
    public function getTemplates($name = null, $url = null)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        $businessId      = $settingsService->getSetting('notifications', 'whatsAppBusinessID');
        $accessToken     = $settingsService->getSetting('notifications', 'whatsAppAccessToken');

        $route = $url ?: (self::URL . $businessId . '/message_templates?access_token=' . $accessToken . ($name ? ('&name=' . $name) : ''));

        return $this->sendRequest($route, 'GET', null, false);
    }


    /**
     * @param $to
     *
     * @return mixed
     */
    public function sendMessage($to, $text)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        $whatsAppPhoneId = $settingsService->getSetting('notifications', 'whatsAppPhoneID');

        $route = self::URL . $whatsAppPhoneId . '/messages';

        $message = $settingsService->getSetting('notifications', 'whatsAppReplyMsg');

        if (!empty($message)) {
            $to   = str_replace('+', '', $to);
            $data = [
                "messaging_product" => "whatsapp",
                'to'            => $to,
                "type"          =>  "TEXT",
                "text"      => [
                    'body' => $text
                ]
            ];

            return $this->sendRequest($route, 'POST', $data);
        }

        return null;
    }
}
