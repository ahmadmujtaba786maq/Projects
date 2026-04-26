<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\Collection\Collection;

/**
 * Class WeekDayFactory
 *
 * @package AmeliaBooking\Domain\Factory\Schedule
 */
class WeekDayFactory
{
    /**
     * @param array $data
     *
     * @return WeekDay
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $weekDay = new WeekDay(
            new IntegerValue($data['dayIndex']),
            new DateTimeValue(\DateTime::createFromFormat('H:i:s', $data['startTime'])),
            new DateTimeValue(\DateTime::createFromFormat('H:i:s', $data['endTime'])),
            new Collection(isset($data['timeOutList']) ? $data['timeOutList'] : []),
            new Collection(isset($data['periodList']) ? $data['periodList'] : [])
        );

        if (isset($data['id'])) {
            $weekDay->setId(new Id($data['id']));
        }

        return $weekDay;
    }
}
