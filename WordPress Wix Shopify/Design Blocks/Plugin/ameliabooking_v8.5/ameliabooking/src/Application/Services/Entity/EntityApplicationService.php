<?php

namespace AmeliaBooking\Application\Services\Entity;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventTicketRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use InvalidArgumentException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class EntityApplicationService
 *
 * @package AmeliaBooking\Application\Services\Entities
 */
class EntityApplicationService
{
    private $container;

    /**
     * EntityApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $idsArrays
     *
     * @return array
     */
    private function getUniqueIds(...$idsArrays)
    {
        $uniqueIds = [];

        foreach ($idsArrays as $idsArray) {
            $uniqueIds = array_merge(
                $uniqueIds,
                array_map('intval', $idsArray ? $idsArray : [])
            );
        }

        return array_unique($uniqueIds);
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForEvent(&$data)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');
        /** @var EventTicketRepository $eventTicketRepository */
        $eventTicketRepository = $this->container->get('domain.booking.event.ticket.repository');

        $providersIds = self::getUniqueIds(
            !empty($data['providers']) ? array_column($data['providers'], 'id') : [],
            !empty($data['organizerId']) ? [$data['organizerId']] : []
        );

        $existingProvidersIds = $providersIds ? $userRepository->getIds(['id' => $providersIds]) : [];

        if (!empty($data['organizerId']) && !in_array((int)$data['organizerId'], $existingProvidersIds)) {
            $data['organizerId'] = null;
        }

        foreach ($data['providers'] as $index => $item) {
            if (!in_array((int)$item['id'], $existingProvidersIds)) {
                unset($data['providers'][$index]);
            }
        }


        $locationsIds = !empty($data['locationId']) ? [(int)$data['locationId']] : [];

        $existingLocationsIds = $locationsIds ? $locationRepository->getIds(['id' => $locationsIds]) : [];

        if (!empty($data['locationId']) && !in_array((int)$data['locationId'], $existingLocationsIds)) {
            $data['locationId'] = null;
        }


        $ticketsIds = [];

        foreach ($data['customTickets'] as $item) {
            if (!empty($item['id'])) {
                $ticketsIds[] = $item['id'];
            }
        }

        $ticketsIds = self::getUniqueIds($ticketsIds);

        $existingTicketsIds = $ticketsIds ? $eventTicketRepository->getIds(['id' => $ticketsIds]) : [];

        foreach ($data['customTickets'] as $index => $item) {
            if (!empty($item['id']) && !in_array((int)$item['id'], $existingTicketsIds)) {
                unset($data['customTickets'][$index]);
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForCustomField(&$data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');


        $servicesIds = self::getUniqueIds(array_column($data['services'], 'id'));

        $existingServicesIds = $servicesIds ? $serviceRepository->getIds(['id' => $servicesIds]) : [];

        foreach ($data['services'] as $index => $item) {
            if (!in_array((int)$item['id'], $existingServicesIds)) {
                unset($data['services'][$index]);
            }
        }


        $eventsIds = self::getUniqueIds(array_column($data['events'], 'id'));

        $existingEventsIds = $eventsIds ? $eventRepository->getIds(['id' => $eventsIds]) : [];

        foreach ($data['events'] as $index => $item) {
            if (!in_array((int)$item['id'], $existingEventsIds)) {
                unset($data['events'][$index]);
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForNotification(&$data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');


        $servicesIds = $data['entity'] === 'appointment' ? self::getUniqueIds($data['entityIds']) : [];

        $existingServicesIds = $servicesIds ? $serviceRepository->getIds(['id' => $servicesIds]) : [];


        $eventsIds = $data['entity'] === 'event' ? self::getUniqueIds($data['entityIds']) : [];

        $existingEventsIds = $eventsIds ? $eventRepository->getIds(['id' => $eventsIds]) : [];


        foreach ($data['entityIds'] as $index => $id) {
            if (
                ($data['entity'] === 'appointment' && !in_array((int)$id, $existingServicesIds)) ||
                ($data['entity'] === 'event' && !in_array((int)$id, $existingEventsIds))
            ) {
                unset($data['entityIds'][$index]);
            }
        }
    }

    /**
     * @param array $data
     *
     * @return string
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function getMissingEntityForNotification($data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');


        $servicesIds = $data['entity'] === 'appointment' ? self::getUniqueIds($data['entityIds']) : [];

        $existingServicesIds = $servicesIds ? $serviceRepository->getIds(['id' => $servicesIds]) : [];


        $eventsIds = $data['entity'] === 'event' ? self::getUniqueIds($data['entityIds']) : [];

        $existingEventsIds = $eventsIds ? $eventRepository->getIds(['id' => $eventsIds]) : [];


        foreach ($data['entityIds'] as $id) {
            if ($data['entity'] === 'appointment' && !in_array((int)$id, $existingServicesIds)) {
                return Entities::SERVICE;
            }

            if ($data['entity'] === 'event' && !in_array((int)$id, $existingEventsIds)) {
                return Entities::EVENT;
            }
        }

        return '';
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForPackage(&$data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');


        $servicesIds = [];

        $providersIds = [];

        $locationsIds = [];

        foreach ($data['bookable'] as $item) {
            $servicesIds[] = (int)$item['service']['id'];

            $providersIds = self::getUniqueIds($providersIds, array_column($item['providers'], 'id'));

            $locationsIds = self::getUniqueIds($locationsIds, array_column($item['locations'], 'id'));
        }


        $existingServicesIds = $servicesIds ? $serviceRepository->getIds(['id' => $servicesIds]) : [];

        $existingProvidersIds = $providersIds ? $userRepository->getIds(['id' => $providersIds]) : [];

        $existingLocationsIds = $locationsIds ? $locationRepository->getIds(['id' => $locationsIds]) : [];


        foreach ($data['bookable'] as $index => $item) {
            if (!in_array((int)$item['service']['id'], $existingServicesIds)) {
                unset($data['bookable'][$index]);

                continue;
            }

            foreach ($item['providers'] as $providerIndex => $provider) {
                if (!in_array((int)$provider['id'], $existingProvidersIds)) {
                    unset($data['bookable'][$index]['providers'][$providerIndex]);
                }
            }

            foreach ($item['locations'] as $locationIndex => $location) {
                if (!in_array((int)$location['id'], $existingLocationsIds)) {
                    unset($data['bookable'][$index]['locations'][$locationIndex]);
                }
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForResource(&$data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');


        $servicesIds = [];

        $providersIds = [];

        $locationsIds = [];

        foreach ($data['entities'] as $item) {
            switch ($item['entityType']) {
                case 'service':
                    $servicesIds[] = $item['entityId'];

                    break;

                case 'employee':
                    $providersIds[] = $item['entityId'];

                    break;

                case 'location':
                    $locationsIds[] = $item['entityId'];

                    break;
            }
        }

        $servicesIds = self::getUniqueIds($servicesIds);

        $existingServicesIds = $servicesIds ? $serviceRepository->getIds(['id' => $servicesIds]) : [];


        $providersIds = self::getUniqueIds($providersIds);

        $existingProvidersIds = $providersIds ? $userRepository->getIds(['id' => $providersIds]) : [];


        $locationsIds = self::getUniqueIds($locationsIds);

        $existingLocationsIds = $locationsIds ? $locationRepository->getIds(['id' => $locationsIds]) : [];


        foreach ($data['entities'] as $index => $item) {
            switch ($item['entityType']) {
                case 'service':
                    if (!in_array((int)$item['entityId'], $existingServicesIds)) {
                        unset($data['entities'][$index]);
                    }

                    break;

                case 'employee':
                    if (!in_array((int)$item['entityId'], $existingProvidersIds)) {
                        unset($data['entities'][$index]);
                    }

                    break;

                case 'location':
                    if (!in_array((int)$item['entityId'], $existingLocationsIds)) {
                        unset($data['entities'][$index]);
                    }

                    break;
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForService(&$data)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        $providersIds = self::getUniqueIds($data['providers']);

        $existingProvidersIds = $data['providers'] ? $userRepository->getIds(['id' => $providersIds]) : [];

        foreach ($data['providers'] as $index => $id) {
            if (!in_array((int)$id, $existingProvidersIds)) {
                unset($data['providers'][$index]);
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws QueryExecutionException
     */
    public function removeMissingEntitiesForProvider(&$data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        if (isset($data['serviceList'])) {
            $servicesIds = self::getUniqueIds(array_column($data['serviceList'], 'id'));
        }

        $locationsIds = [];

        if (isset($data['locationId'])) {
            $locationsIds[] = (int)$data['locationId'];
        }

        foreach (['weekDayList', 'specialDayList'] as $type) {
            if (isset($data[$type])) {
                foreach ($data[$type] as $day) {
                    foreach ($day['periodList'] as $period) {
                        $locationsIds = self::getUniqueIds(
                            $locationsIds,
                            $period['locationId'] ? [$period['locationId']] : [],
                            array_column($period['periodLocationList'], 'locationId')
                        );

                        if (isset($servicesIds)) {
                            $servicesIds = self::getUniqueIds(
                                $servicesIds,
                                array_column($period['periodServiceList'], 'serviceId')
                            );
                        }
                    }
                }
            }
        }


        $existingServicesIds = !empty($servicesIds) ? $serviceRepository->getIds(['id' => $servicesIds]) : [];

        $existingLocationsIds = !empty($locationsIds) ? $locationRepository->getIds(['id' => $locationsIds]) : [];


        if (isset($data['locationId']) && !in_array((int)$data['locationId'], $existingLocationsIds)) {
            $data['locationId'] = null;
        }

        foreach (['weekDayList', 'specialDayList'] as $dayType) {
            if (isset($data[$dayType])) {
                foreach ($data[$dayType] as $dayIndex => $day) {
                    foreach ($day['periodList'] as $periodIndex => $period) {
                        if (!in_array((int)$period['locationId'], $existingLocationsIds)) {
                            $data[$dayType][$dayIndex]['periodList'][$periodIndex]['locationId'] = null;
                        }

                        foreach ($period['periodLocationList'] as $index => $periodLocation) {
                            if (!in_array((int)$periodLocation['locationId'], $existingLocationsIds)) {
                                unset($data[$dayType][$dayIndex]['periodList'][$periodIndex]['periodLocationList'][$index]);
                            }
                        }

                        foreach ($period['periodServiceList'] as $index => $periodService) {
                            if (!in_array((int)$periodService['serviceId'], $existingServicesIds)) {
                                unset($data[$dayType][$dayIndex]['periodList'][$periodIndex]['periodServiceList'][$index]);
                            }
                        }
                    }
                }
            }
        }

        if (isset($data['serviceList'])) {
            foreach ($data['serviceList'] as $index => $item) {
                if (!in_array((int)$item['id'], $existingServicesIds)) {
                    unset($data['serviceList'][$index]);
                }
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function removeMissingEntityForAppointment(&$data)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');


        $customersIds = self::getUniqueIds(array_column($data['bookings'], 'customerId'));

        if (!empty($customersIds)) {
            $existingCustomersIds = $userRepository->getIds(['id' => $customersIds]);

            foreach ($data['bookings'] as $index => $item) {
                if (!in_array((int)$item['customerId'], $existingCustomersIds)) {
                    unset($data['bookings'][$index]);
                }
            }
        }


        $extrasIds = [];

        foreach ($data['bookings'] as $item) {
            if (empty($item['extras'])) {
                continue;
            }
            $extrasIds = self::getUniqueIds(
                $extrasIds,
                array_column($item['extras'], 'extraId')
            );
        }

        $existingExtrasIds = $extrasIds ? $extraRepository->getIds(['id' => $extrasIds]) : [];

        foreach ($data['bookings'] as $bookingIndex => $bookingItem) {
            if (empty($bookingItem['extras'])) {
                continue;
            }
            foreach ($bookingItem['extras'] as $extraIndex => $extraItem) {
                if (!in_array((int)$extraItem['extraId'], $existingExtrasIds)) {
                    unset($data['bookings'][$bookingIndex]['extras'][$extraIndex]);
                }
            }
        }


        $couponsIds = self::getUniqueIds(array_column(array_column($data['bookings'], 'coupon'), 'id'));

        $existingCouponsIds = $couponsIds ? $couponRepository->getIds(['id' => $couponsIds]) : [];

        foreach ($data['bookings'] as $index => $item) {
            if (!empty($item['coupon']) && !in_array((int)$item['coupon']['id'], $existingCouponsIds)) {
                $data['bookings'][$index]['coupon'] = null;
            }
        }


        $existingLocationsIds = !empty($data['locationId']) ?
            $locationRepository->getIds(['id' => [(int)$data['locationId']]]) : [];

        if (!empty($data['locationId']) && !in_array((int)$data['locationId'], $existingLocationsIds)) {
            $data['locationId'] = null;
        }
    }

    /**
     * @param array $data
     *
     * @return string
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function getMissingEntityForAppointment($data)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');


        $customersIds = self::getUniqueIds(array_column($data['bookings'], 'customerId'));

        $existingCustomersIds = $userRepository->getIds(['id' => $customersIds]);

        foreach ($data['bookings'] as $item) {
            if (!in_array((int)$item['customerId'], $existingCustomersIds)) {
                return Entities::CUSTOMER;
            }
        }


        $extrasIds = [];

        foreach ($data['bookings'] as $item) {
            if (empty($item['extras'])) {
                continue;
            }
            $extrasIds = self::getUniqueIds(
                $extrasIds,
                array_column($item['extras'], 'extraId')
            );
        }

        $existingExtrasIds = $extrasIds ? $extraRepository->getIds(['id' => $extrasIds]) : [];

        foreach ($data['bookings'] as $bookingItem) {
            if (empty($bookingItem['extras'])) {
                continue;
            }
            foreach ($bookingItem['extras'] as $extraItem) {
                if (!in_array((int)$extraItem['extraId'], $existingExtrasIds)) {
                    return Entities::EXTRA;
                }
            }
        }


        $couponsIds = self::getUniqueIds(array_column(array_column($data['bookings'], 'coupon'), 'id'));

        $existingCouponsIds = $couponsIds ? $couponRepository->getIds(['id' => $couponsIds]) : [];

        foreach ($data['bookings'] as $item) {
            if (!empty($item['coupon']) && !in_array((int)$item['coupon']['id'], $existingCouponsIds)) {
                return Entities::COUPON;
            }
        }


        $existingServicesIds = $serviceRepository->getIds(['id' => [(int)$data['serviceId']]]);

        if (!in_array((int)$data['serviceId'], $existingServicesIds)) {
            return Entities::SERVICE;
        }


        $existingProvidersIds = $userRepository->getIds(['id' => [(int)$data['providerId']]]);

        if (!in_array((int)$data['providerId'], $existingProvidersIds)) {
            return Entities::PROVIDER;
        }


        $existingLocationsIds = !empty($data['locationId']) ?
            $locationRepository->getIds(['id' => [(int)$data['locationId']]]) : [];

        if (!empty($data['locationId']) && !in_array((int)$data['locationId'], $existingLocationsIds)) {
            return Entities::LOCATION;
        }

        return '';
    }

    /**
     * @return CommandResult
     */
    public function getMissingEntityResponse($type)
    {
        $result = new CommandResult();

        $result->setResult(CommandResult::RESULT_ERROR);
        $result->setMessage("Entity missing ($type). Please refresh page and try again.");
        $result->setData(
            [
                'entityMissing' => true,
            ]
        );

        return $result;
    }
}
