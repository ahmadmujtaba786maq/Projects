<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\PeriodLocation;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

class SpecialDayPeriodLocationFactory
{
    /**
     * @param array $data
     *
     * @return PeriodLocation
     */
    public static function create($data)
    {
        $periodLocation = new PeriodLocation(
            new Id($data['locationId'])
        );

        if (isset($data['id'])) {
            $periodLocation->setId(new Id($data['id']));
        }

        return $periodLocation;
    }
}
