<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Settings;

/**
 * Class MicrosoftTeamsSettings
 *
 * @package AmeliaBooking\Domain\Entity\Settings
 */
class MicrosoftTeamsSettings
{
    /** @var bool */
    private $enabled;

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'enabled'   => $this->getEnabled(),
        ];
    }
}
