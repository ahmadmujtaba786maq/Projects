<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Search;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Domain\Services\Entity\EntityService;
use AmeliaBooking\Domain\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService as ApplicationTimeSlotService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\SlotsEntities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetSearchCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Search
 */
class GetSearchCommandHandler extends CommandHandler
{
    /**
     * @param GetSearchCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(GetSearchCommand $command)
    {
        $result = new CommandResult();

        $resultData = [];

        $params = $command->getField('params');

        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var EntityService $entityService */
        $entityService = $this->container->get('domain.entity.service');
        /** @var TimeSlotService $timeSlotService */
        $timeSlotService = $this->container->get('domain.timeSlot.service');
        /** @var ApplicationTimeSlotService $applicationTimeSlotService */
        $applicationTimeSlotService = $this->container->get('application.timeSlot.service');

        if (
            isset($params['startOffset'], $params['endOffset'])
            && $settingsDS->getSetting('general', 'showClientTimeZone')
        ) {
            $searchStartDateTimeString = DateTimeService::getCustomDateTimeFromUtc(
                DateTimeService::getClientUtcCustomDateTime(
                    $params['date'] . ' ' . (isset($params['timeFrom']) ? $params['timeFrom'] : '00:00:00'),
                    -$params['startOffset']
                )
            );

            $searchEndDateTimeString = DateTimeService::getCustomDateTimeFromUtc(
                DateTimeService::getClientUtcCustomDateTime(
                    $params['date'] . ' ' . (isset($params['timeTo']) ? $params['timeTo'] : '23:59:00'),
                    -$params['endOffset']
                )
            );

            $searchStartDateString = explode(' ', $searchStartDateTimeString)[0];
            $searchEndDateString   = explode(' ', $searchEndDateTimeString)[0];

            $searchTimeFrom = explode(' ', $searchStartDateTimeString)[1];
            $searchTimeTo   = explode(' ', $searchEndDateTimeString)[1];
        } else {
            $searchStartDateString = $params['date'];
            $searchEndDateString   = $params['date'];
            $searchTimeFrom        = isset($params['timeFrom']) ? $params['timeFrom'] : null;
            $searchTimeTo          = isset($params['timeTo']) ? $params['timeTo'] : null;
        }

        /** @var SlotsEntities $slotsEntities */
        $slotsEntities = $applicationTimeSlotService->getSlotsEntities(
            [
                'isFrontEndBooking' => true,
                'providerIds'       => !empty($params['providers']) ? $params['providers'] : [],
                'serviceCriteria'   => [
                    'sort' => !empty($params['sort']) ? $params['sort'] : null,
                ],
            ]
        );

        $startDateTimeObject = DateTimeService::getCustomDateTimeObject($searchStartDateString . ' 00:00:00');

        $endDateTimeObject = DateTimeService::getCustomDateTimeObject($searchEndDateString . ' 23:59:00');

        $props = [
            'providerIds'          => !empty($params['providers']) ? $params['providers'] : [],
            'locationId'           => !empty($params['location']) ? (int)$params['location'] : null,
            'extras'               => [],
            'excludeAppointmentId' => null,
            'personsCount'         => 1,
            'isFrontEndBooking'    => true,
            'startDateTime'        => $startDateTimeObject->modify('-1 days'),
            'endDateTime'          => $endDateTimeObject->modify('+1 days'),
        ];

        $settings = $applicationTimeSlotService->getSlotsSettings(true, $slotsEntities);

        $applicationTimeSlotService->setBlockerAppointments($slotsEntities->getProviders(), $props);

        /** @var Collection $appointments */
        $appointments = $applicationTimeSlotService->getBookedAppointments($slotsEntities, $props);

        $servicesIds = !empty($params['services']) ? $params['services'] : $slotsEntities->getServices()->keys();

        $filteredServicesIds = [];

        if (!empty($params['search'])) {
            foreach ($servicesIds as $serviceId) {
                /** @var Service $service */
                $service = $slotsEntities->getServices()->getItem($serviceId);

                if (strpos($service->getName()->getValue(), $params['search']) !== false) {
                    $filteredServicesIds[] = $serviceId;
                }
            }
        } else {
            $filteredServicesIds = $servicesIds;
        }

        foreach ($filteredServicesIds as $serviceId) {
            /** @var SlotsEntities $filteredSlotEntities */
            $filteredSlotEntities = $entityService->getFilteredSlotsEntities(
                $settings,
                array_merge($props, ['serviceId' => $serviceId]),
                $slotsEntities
            );

            /** @var Service $service */
            $service = $filteredSlotEntities->getServices()->getItem($serviceId);

            if (!$service->getShow()->getValue()) {
                continue;
            }

            $minimumBookingTimeInSeconds = $settingsDS
                ->getEntitySettings($service->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToBooking();

            $offset = DateTimeService::getNowDateTimeObject()
                ->modify("+{$minimumBookingTimeInSeconds} seconds");

            $startDateTime = DateTimeService::getCustomDateTimeObject($searchStartDateString);

            $startDateTime = $offset > $startDateTime ? $offset : $startDateTime;

            $endDateTime = DateTimeService::getCustomDateTimeObject($searchEndDateString);

            $maximumBookingTimeInDays = $settingsDS
                ->getEntitySettings($service->getSettings())
                ->getGeneralSettings()
                ->getNumberOfDaysAvailableForBooking();

            $maxEndDateTime = $applicationTimeSlotService->getMaximumDateTimeForBooking(
                $endDateTime->format('Y-m-d H:i:s'),
                true,
                $maximumBookingTimeInDays
            );

            if ($maxEndDateTime < $endDateTime) {
                continue;
            }

            $settings['defaultAppointmentStatus'] = $settingsDS
                ->getEntitySettings($slotsEntities->getServices()->getItem($serviceId)->getSettings())
                ->getGeneralSettings()
                ->getDefaultAppointmentStatus();

            $freeSlots = $timeSlotService->getSlots(
                $settings,
                array_merge(
                    $props,
                    [
                        'serviceId'     => $serviceId,
                        'startDateTime' => $startDateTime,
                        'endDateTime'   => $endDateTime->modify('+1 day'),
                    ]
                ),
                $filteredSlotEntities,
                $appointments
            )['available'];

            if ($searchTimeFrom && array_key_exists($searchStartDateString, $freeSlots)) {
                $freeSlots = $this->filterByTimeFrom($searchStartDateString, $searchTimeFrom, $freeSlots);
            }

            if ($searchTimeTo && array_key_exists($searchEndDateString, $freeSlots)) {
                $freeSlots = $this->filterByTimeTo($searchEndDateString, $searchTimeTo, $freeSlots);
            }

            if (
                !array_key_exists($searchStartDateString, $freeSlots) &&
                !array_key_exists($searchEndDateString, $freeSlots)
            ) {
                continue;
            }

            $providersIds = [];

            foreach ($freeSlots as $dateSlot) {
                foreach ($dateSlot as $timeSlot) {
                    foreach ($timeSlot as $infoSlot) {
                        $providersIds[$infoSlot[0]][] = [
                            $infoSlot[1],
                            isset($infoSlot[2]) ? $infoSlot[2] : null
                        ];
                    }
                }
            }

            foreach ($providersIds as $providersId => $providersData) {
                $resultData[] = [
                    $service->getId()->getValue() => $providersId,
                    'places'                      =>
                        (min(array_filter(array_column($providersData, 1)) ?: [0]) ?: 0) ?: null,
                    'locations'                   =>
                        array_values(array_unique(array_column($providersData, 0))),
                    'price'                       => $filteredSlotEntities->getProviders()
                        ->getItem($providersId)
                        ->getServiceList()
                        ->getItem($service->getId()->getValue())
                        ->getPrice()
                        ->getValue()
                ];
            }
        }

        if (strpos($params['sort'], 'price') !== false) {
            usort(
                $resultData,
                function ($service1, $service2) {
                    return $service1['price'] > $service2['price'];
                }
            );

            if ($params['sort'] === '-price') {
                $resultData = array_reverse($resultData);
            }
        }

        // Pagination
        $itemsPerPage = $settingsDS->getSetting('general', 'itemsPerPage');

        $resultDataPaginated = array_slice($resultData, ($params['page'] - 1) * $itemsPerPage, $itemsPerPage);

        $resultDataPaginated = apply_filters('amelia_get_search_slots_filter', $resultDataPaginated);

        do_action('amelia_get_search_slots', $resultDataPaginated);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved searched services.');
        $result->setData(
            [
                'providersServices' => $resultDataPaginated,
                'total'             => count($resultData)
            ]
        );

        return $result;
    }

    /**
     * @param $date
     * @param $time
     * @param $freeSlots
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function filterByTimeFrom($date, $time, $freeSlots)
    {
        foreach (array_keys($freeSlots[$date]) as $freeSlotKey) {
            if (
                DateTimeService::getCustomDateTimeObject($date . ' ' . $freeSlotKey) >=
                DateTimeService::getCustomDateTimeObject($date . ' ' . $time)
            ) {
                break;
            }

            unset($freeSlots[$date][$freeSlotKey]);

            if (empty($freeSlots[$date])) {
                unset($freeSlots[$date]);
            }
        }

        return $freeSlots;
    }

    /**
     * @param $date
     * @param $time
     * @param $freeSlots
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function filterByTimeTo($date, $time, $freeSlots)
    {
        foreach (array_reverse(array_keys($freeSlots[$date])) as $freeSlotKey) {
            if (
                DateTimeService::getCustomDateTimeObject($date . ' ' . $freeSlotKey) <=
                DateTimeService::getCustomDateTimeObject($date . ' ' . $time)
            ) {
                break;
            }

            unset($freeSlots[$date][$freeSlotKey]);

            if (empty($freeSlots[$date])) {
                unset($freeSlots[$date]);
            }
        }

        return $freeSlots;
    }
}
