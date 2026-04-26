<?php

namespace AmeliaBooking\Infrastructure\Licence\Pro;

use AmeliaBooking\Domain\Services as DomainServices;

/**
 * Class DomainService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Pro
 */
class DomainService extends \AmeliaBooking\Infrastructure\Licence\Basic\DomainService
{
    /**
     * @return DomainServices\Resource\AbstractResourceService
     */
    public static function getResourceService()
    {
        $intervalService = new DomainServices\Interval\IntervalService();

        $locationService = new DomainServices\Location\LocationService();

        $providerService = new DomainServices\User\ProviderService(
            $intervalService
        );

        $scheduleService = new DomainServices\Schedule\ScheduleService(
            $intervalService,
            $providerService,
            $locationService
        );

        return new DomainServices\Resource\ResourceService(
            $intervalService,
            $scheduleService
        );
    }

    /**
     * @return DomainServices\Entity\EntityService
     */
    public static function getEntityService()
    {
        $intervalService = new DomainServices\Interval\IntervalService();

        $locationService = new DomainServices\Location\LocationService();

        $providerService = new DomainServices\User\ProviderService(
            $intervalService
        );

        $scheduleService = new DomainServices\Schedule\ScheduleService(
            $intervalService,
            $providerService,
            $locationService
        );

        $resourceService = new DomainServices\Resource\ResourceService(
            $intervalService,
            $scheduleService
        );

        return new DomainServices\Entity\EntityService(
            $providerService,
            $resourceService
        );
    }

    /**
     * @return DomainServices\TimeSlot\TimeSlotService
     */
    public static function getTimeSlotService()
    {
        $intervalService = new DomainServices\Interval\IntervalService();

        $locationService = new DomainServices\Location\LocationService();

        $providerService = new DomainServices\User\ProviderService(
            $intervalService
        );

        $scheduleService = new DomainServices\Schedule\ScheduleService(
            $intervalService,
            $providerService,
            $locationService
        );

        $resourceService = new DomainServices\Resource\ResourceService(
            $intervalService,
            $scheduleService
        );

        $entityService = new DomainServices\Entity\EntityService(
            $providerService,
            $resourceService
        );

        return new DomainServices\TimeSlot\TimeSlotService(
            $intervalService,
            $scheduleService,
            $providerService,
            $resourceService,
            $entityService
        );
    }
}
