<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\Collection\Collection;

/**
 * Class SpecialDayFactory
 *
 * @package AmeliaBooking\Domain\Factory\Schedule
 */
class SpecialDayFactory
{
    /**
     * @param array $data
     *
     * @return SpecialDay
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $specialDay = new SpecialDay(
            new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['startDate'])),
            new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['endDate'])),
            new Collection(isset($data['periodList']) ? $data['periodList'] : [])
        );

        if (isset($data['id'])) {
            $specialDay->setId(new Id($data['id']));
        }

        return $specialDay;
    }
}
