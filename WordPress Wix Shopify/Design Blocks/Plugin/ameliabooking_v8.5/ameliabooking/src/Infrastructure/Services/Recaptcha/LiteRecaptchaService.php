<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Recaptcha;

/**
 * Class RecaptchaService
 */
class LiteRecaptchaService extends AbstractRecaptchaService
{
    /**
     * @param string $value
     *
     * @return boolean
     */
    public function verify($value)
    {
        return true;
    }

    /**
     * @param string $value
     * @param string $cabinetType
     *
     * @return boolean
     */
    public function process($value, $cabinetType)
    {
        return true;
    }
}
