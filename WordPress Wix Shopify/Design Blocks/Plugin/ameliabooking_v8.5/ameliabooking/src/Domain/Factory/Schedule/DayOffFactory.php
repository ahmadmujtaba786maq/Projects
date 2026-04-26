<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\DateRepeat;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class DayOffFactory
 *
 * @package AmeliaBooking\Domain\Factory\Schedule
 */
class DayOffFactory
{
    /**
     * @param array $data
     *
     * @return DayOff
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $dayOff = new DayOff(
            new Name($data['name']),
            new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['startDate'])),
            new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['endDate'])),
            new DateRepeat($data['repeat'])
        );

        if (isset($data['id'])) {
            $dayOff->setId(new Id($data['id']));
        }

        return $dayOff;
    }
}
