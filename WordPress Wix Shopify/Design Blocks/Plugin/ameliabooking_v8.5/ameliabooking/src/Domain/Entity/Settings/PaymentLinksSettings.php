<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Settings;

/**
 * Class PaymentLinksSettings
 *
 * @package AmeliaBooking\Domain\Entity\Settings
 */
class PaymentLinksSettings
{
    /** @var bool */
    private $enabled;

    /** @var bool */
    private $changeBookingStatus;

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isChangeBookingStatus()
    {
        return $this->changeBookingStatus;
    }

    /**
     * @param bool $changeBookingStatus
     */
    public function setChangeBookingStatus($changeBookingStatus)
    {
        $this->changeBookingStatus = $changeBookingStatus;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'enabled' => $this->enabled,
            'changeBookingStatus' => $this->changeBookingStatus
        ];
    }
}
