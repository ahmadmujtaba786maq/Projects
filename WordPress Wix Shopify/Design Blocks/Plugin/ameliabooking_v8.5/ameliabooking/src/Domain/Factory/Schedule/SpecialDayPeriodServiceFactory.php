<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\PeriodService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

class SpecialDayPeriodServiceFactory
{
    /**
     * @param array $data
     *
     * @return PeriodService
     */
    public static function create($data)
    {
        $periodService = new PeriodService(
            new Id($data['serviceId'])
        );

        if (isset($data['id'])) {
            $periodService->setId(new Id($data['id']));
        }

        return $periodService;
    }
}
