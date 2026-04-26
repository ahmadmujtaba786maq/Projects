<?php

namespace AmeliaBooking\Infrastructure\Licence\Lite;

use AmeliaBooking\Domain\Services as DomainServices;
use Interop\Container\Exception\ContainerException;

/**
 * Class DomainService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Lite
 */
class DomainService
{
    /**
     * Container $c
     *
     * @return DomainServices\Permissions\PermissionsService
     * @throws ContainerException
     */
    public static function getPermissionService($c)
    {
        return new DomainServices\Permissions\PermissionsService(
            $c,
            new \AmeliaBooking\Infrastructure\WP\PermissionsService\PermissionsChecker()
        );
    }

    /**
     * @return DomainServices\Api\BasicApiService
     */
    public static function getApiService()
    {
        return new DomainServices\Api\BasicApiService();
    }

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

        return new DomainServices\Resource\BasicResourceService(
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

        $resourceService = new DomainServices\Resource\BasicResourceService(
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

        $resourceService = new DomainServices\Resource\BasicResourceService(
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
