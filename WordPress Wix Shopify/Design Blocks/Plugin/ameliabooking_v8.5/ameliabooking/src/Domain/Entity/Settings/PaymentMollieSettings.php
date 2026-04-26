<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Settings;

/**
 * Class PaymentMollieSettings
 *
 * @package AmeliaBooking\Domain\Entity\Settings
 */
class PaymentMollieSettings
{
    /** @var bool */
    private $enabled;

    /**
     * @return int
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'enabled' => $this->enabled,
        ];
    }
}
