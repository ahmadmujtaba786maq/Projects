<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Recaptcha;

use AmeliaBooking\Domain\Services\Settings\SettingsService;

/**
 * Class AbstractRecaptchaService
 */
abstract class AbstractRecaptchaService
{
    /**
     * @var SettingsService $settingsService
     */
    protected $settingsService;

    /**
     * RecaptchaService constructor.
     *
     * @param SettingsService $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @param string $value
     *
     * @return boolean
     */
    abstract public function verify($value);

    /**
     * @param string $value
     * @param string $cabinetType
     *
     * @return boolean
     */
    abstract public function process($value, $cabinetType);
}
