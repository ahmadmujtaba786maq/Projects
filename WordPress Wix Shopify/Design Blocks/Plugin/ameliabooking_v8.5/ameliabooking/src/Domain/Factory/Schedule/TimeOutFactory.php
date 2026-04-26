<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\TimeOut;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;

class TimeOutFactory
{
    /**
     * @param array $data
     *
     * @return TimeOut
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $timeOut = new TimeOut(
            new DateTimeValue(\DateTime::createFromFormat('H:i:s', $data['startTime'])),
            new DateTimeValue(\DateTime::createFromFormat('H:i:s', $data['endTime']))
        );

        if (isset($data['id'])) {
            $timeOut->setId(new Id($data['id']));
        }

        return $timeOut;
    }
}
