<?php

namespace AmeliaBooking\Domain\Factory\Apple;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AppleCalendarEmployeeConnect;
use AmeliaBooking\Domain\ValueObjects\String\Name;

class AppleCalendarFactory
{
    /**
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $appleCalendarConnect = new AppleCalendarEmployeeConnect();

        if (isset($data['iCloudId'])) {
            $appleCalendarConnect->setICloudId(new Name($data['iCloudId']));
        }

        if (isset($data['appSpecificPassword'])) {
            $appleCalendarConnect->setAppSpecificPassword(new Name($data['appSpecificPassword']));
        }

        return $appleCalendarConnect;
    }
}
