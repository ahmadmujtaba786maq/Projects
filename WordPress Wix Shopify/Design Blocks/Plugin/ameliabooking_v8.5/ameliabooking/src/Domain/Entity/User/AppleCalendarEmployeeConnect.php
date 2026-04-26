<?php

namespace AmeliaBooking\Domain\Entity\User;

use AmeliaBooking\Domain\ValueObjects\String\Name;

class AppleCalendarEmployeeConnect
{
    /** @var Name */
    private $iCloudId;

    /** @var Name */
    private $appSpecificPassword;

    /**
     * @return Name
     */
    public function getICloudId()
    {
        return $this->iCloudId;
    }

    /**
     * @param Name $iCloudId
     */
    public function setICloudId($iCloudId)
    {
        $this->iCloudId = $iCloudId;
    }

    /**
     * @return Name
     */
    public function getAppSpecificPassword()
    {
        return $this->appSpecificPassword;
    }

    /**
     * @param Name $appSpecificPassword
     */
    public function setAppSpecificPassword($appSpecificPassword)
    {
        $this->appSpecificPassword = $appSpecificPassword;
    }

    public function toArray()
    {
        return [
            'iCloudId' => $this->getICloudId() ? $this->getICloudId()->getValue() : null,
            'appSpecificPassword' => $this->getAppSpecificPassword() ? $this->getAppSpecificPassword()->getValue() : null,
        ];
    }
}
