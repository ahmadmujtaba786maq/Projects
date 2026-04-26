<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Recaptcha;

use AmeliaBooking\Application\Commands\CommandResult;

/**
 * Class RecaptchaService
 */
class RecaptchaService extends AbstractRecaptchaService
{
    /**
     * @param string $value
     *
     * @return boolean
     */
    public function verify($value)
    {
        $googleRecaptchaSettings = $this->settingsService->getSetting(
            'general',
            'googleRecaptcha'
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            [
                'secret'   => $googleRecaptchaSettings['secret'],
                'response' => $value
            ]
        );

        $response = json_decode(curl_exec($ch));

        curl_close($ch);

        return $response->success;
    }

    /**
     * @param string $value
     * @param string $cabinetType
     *
     * @return boolean
     */
    public function process($value, $cabinetType)
    {
        $googleRecaptchaSettings = $this->settingsService->getSetting(
            'general',
            'googleRecaptcha'
        );

        $userRecaptchaSettings = $this->settingsService->getSetting(
            'roles',
            $cabinetType . 'Cabinet'
        );

        if (
            $googleRecaptchaSettings['enabled'] &&
            $googleRecaptchaSettings['siteKey'] &&
            $googleRecaptchaSettings['secret'] &&
            !empty($userRecaptchaSettings['googleRecaptcha']) &&
            (!$value || !$this->verify($value))
        ) {
            return false;
        }

        return true;
    }
}
