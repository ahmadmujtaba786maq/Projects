<?php

namespace AmeliaBooking\Application\Services\TimeSlot;

use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Location\AbstractLocationApplicationService;
use AmeliaBooking\Application\Services\Resource\AbstractResourceApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\SlotsEntities;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\SlotsEntitiesFactory;
use AmeliaBooking\Domain\Services\Entity\EntityService;
use AmeliaBooking\Domain\Services\Resource\AbstractResourceService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Services\TimeSlot\TimeSlotService as DomainTimeSlotService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Apple\AbstractAppleCalendarService;
use AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class TimeSlotService
 *
 * @package AmeliaBooking\Application\Services\TimeSlot
 */
class TimeSlotService
{
    /** @var Container $container */
    private $container;

    /**
     * TimeSlotService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Service  $service
     * @param DateTime $requiredDateTime
     * @param DateTime $minimumAppointmentDateTime
     * @param DateTime $maximumAppointmentDateTime
     * @param int      $providerId
     * @param int|null $locationId
     * @param array    $selectedExtras
     * @param int|null $excludeAppointmentId
     * @param int      $personsCount
     * @param boolean  $isFrontEndBooking
     *
     * @return boolean
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws Exception
     */
    public function isSlotFree(
        $service,
        $requiredDateTime,
        $minimumAppointmentDateTime,
        $maximumAppointmentDateTime,
        $providerId,
        $locationId,
        $selectedExtras,
        $excludeAppointmentId,
        $personsCount,
        $isFrontEndBooking
    ) {
        $dateKey = $requiredDateTime->format('Y-m-d');
        $timeKey = $requiredDateTime->format('H:i');

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var EntityService $entityService */
        $entityService = $this->container->get('domain.entity.service');

        $minimumBookingDateTime = $this->getMinimumDateTimeForBooking(
            '',
            $isFrontEndBooking,
            $settingsDS
                ->getEntitySettings($service->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToBooking()
        );

        if ($requiredDateTime < $minimumBookingDateTime) {
            return false;
        }

        $searchStartDateTime = clone $requiredDateTime;

        $searchStartDateTime->modify('-1 days');

        $searchEndDateTime = clone $requiredDateTime;

        $searchEndDateTime->modify('+1 days');

        /** @var SlotsEntities $slotsEntities */
        $slotsEntities = $this->getSlotsEntities(
            [
                'isFrontEndBooking' => $isFrontEndBooking,
                'providerIds'       => [$providerId],
            ]
        );

        /** @var Service $slotEntitiesService */
        foreach ($slotsEntities->getServices()->getItems() as $slotEntitiesService) {
            if ($slotEntitiesService->getId()->getValue() === $service->getId()->getValue()) {
                $slotEntitiesService->setDuration($service->getDuration());
                break;
            }
        }

        $settings = $this->getSlotsSettings($isFrontEndBooking, $slotsEntities);

        $props = [
            'startDateTime'        => $searchStartDateTime->modify('first day of this month')->modify('- 7 days'),
            'endDateTime'          => $searchEndDateTime,
            'minimumDateTime'      => $minimumAppointmentDateTime,
            'maximumDateTime'      => $maximumAppointmentDateTime,
            'serviceId'            => $service->getId()->getValue(),
            'providerIds'          => [$providerId],
            'locationId'           => $locationId,
            'extras'               => $selectedExtras,
            'excludeAppointmentId' => $excludeAppointmentId,
            'personsCount'         => $personsCount,
            'isFrontEndBooking'    => $isFrontEndBooking,
        ];

        /** @var SlotsEntities $filteredSlotEntities */
        $filteredSlotEntities = $entityService->getFilteredSlotsEntities(
            $settings,
            $props,
            $slotsEntities
        );

        $freeSlots = $this->getSlotsByProps(
            $settings,
            $props,
            $filteredSlotEntities
        );

        return
            array_key_exists($dateKey, $freeSlots['available']) &&
            array_key_exists($timeKey, $freeSlots['available'][$dateKey]);
    }

    /**
     * @param string  $requiredBookingDateTimeString
     * @param boolean $isFrontEndBooking
     * @param string  $minimumTime
     *
     * @return DateTime
     * @throws Exception
     */
    public function getMinimumDateTimeForBooking($requiredBookingDateTimeString, $isFrontEndBooking, $minimumTime)
    {
        $requiredTimeOffset = $isFrontEndBooking ? $minimumTime : 0;

        $minimumBookingDateTime = DateTimeService::getNowDateTimeObject()->modify("+{$requiredTimeOffset} seconds");

        $requiredBookingDateTime = DateTimeService::getCustomDateTimeObject($requiredBookingDateTimeString);

        $minimumDateTime = ($minimumBookingDateTime > $requiredBookingDateTime ||
            $minimumBookingDateTime->format('Y-m-d') === $requiredBookingDateTime->format('Y-m-d')
        ) ? $minimumBookingDateTime : $requiredBookingDateTime->setTime(0, 0, 0);

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $pastAvailableDays = $settingsDS->getSetting('general', 'backendSlotsDaysInPast');

        if (!$isFrontEndBooking && $pastAvailableDays) {
            $minimumDateTime->modify("-{$pastAvailableDays} days");
        }

        return $minimumDateTime;
    }

    /**
     * @param string  $requiredBookingDateTimeString
     * @param boolean $isFrontEndBooking
     * @param int     $maximumTime
     *
     * @return DateTime
     * @throws Exception
     */
    public function getMaximumDateTimeForBooking($requiredBookingDateTimeString, $isFrontEndBooking, $maximumTime)
    {
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $futureAvailableDays = $settingsDS->getSetting('general', 'backendSlotsDaysInFuture');

        $days = $maximumTime > $futureAvailableDays ?
            $maximumTime :
            $futureAvailableDays;

        $daysAvailableForBooking = $isFrontEndBooking ? $maximumTime : $days;

        $maximumBookingDateTime = DateTimeService::getNowDateTimeObject()->modify("+{$daysAvailableForBooking} day");

        $requiredBookingDateTime = $requiredBookingDateTimeString ?
            DateTimeService::getCustomDateTimeObject($requiredBookingDateTimeString) : $maximumBookingDateTime;

        return ($maximumBookingDateTime < $requiredBookingDateTime ||
            $maximumBookingDateTime->format('Y-m-d') === $requiredBookingDateTime->format('Y-m-d')
        ) ? $maximumBookingDateTime : $requiredBookingDateTime;
    }

    /**
     * get provider id values for appointments fetch needed for slot calculation
     *
     * @param Collection $providers
     * @param Collection $resources
     *
     * @return array
     */
    private function getAppointmentsProvidersIds($providers, $resources)
    {
        /** @var AbstractResourceService $resourceService */
        $resourceService = $this->container->get('domain.resource.service');

        if ($resources->length()) {
            $resourcesProvidersIds = $resourceService->getResourcesProvidersIds($resources);

            return $resourcesProvidersIds ? array_unique(array_merge($providers->keys(), $resourcesProvidersIds)) : [];
        }

        return $providers->keys();
    }

    /**
     * add busy appointments to providers from google calendar events, outlook calendar events and amelia events
     *
     * @param Collection $providers
     * @param array      $props
     *
     * @throws ContainerException
     * @throws Exception
     */
    public function setBlockerAppointments($providers, $props)
    {
        /** @var AbstractGoogleCalendarService $googleCalendarService */
        $googleCalendarService = $this->container->get('infrastructure.google.calendar.service');

        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');

        /** @var AbstractAppleCalendarService $appleCalendarService */
        $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');

        try {
            $googleCalendarService->removeSlotsFromGoogleCalendar(
                $providers,
                $props['excludeAppointmentId'],
                !empty($props['minimumDateTime']) ? $props['minimumDateTime'] : $props['startDateTime'],
                !empty($props['maximumDateTime']) ? $props['maximumDateTime'] : $props['endDateTime']
            );
        } catch (Exception $e) {
        }

        try {
            $outlookCalendarService->removeSlotsFromOutlookCalendar(
                $providers,
                $props['excludeAppointmentId'],
                !empty($props['minimumDateTime']) ? $props['minimumDateTime'] : $props['startDateTime'],
                !empty($props['maximumDateTime']) ? $props['maximumDateTime'] : $props['endDateTime']
            );
        } catch (Exception $e) {
        }

        try {
            $appleCalendarService->removeSlotsFromAppleCalendar(
                $providers,
                $props['excludeAppointmentId'],
                !empty($props['minimumDateTime']) ? $props['minimumDateTime'] : $props['startDateTime'],
                !empty($props['maximumDateTime']) ? $props['maximumDateTime'] : $props['endDateTime']
            );
        } catch (Exception $e) {
        }

        $eventApplicationService->removeSlotsFromEvents(
            $providers,
            [
                DateTimeService::getCustomDateTimeObject($props['startDateTime']->format('Y-m-d H:i:s'))
                    ->modify('-10 day')
                    ->format('Y-m-d H:i:s'),
                DateTimeService::getCustomDateTimeObject($props['startDateTime']->format('Y-m-d H:i:s'))
                    ->modify('+2 years')
                    ->format('Y-m-d H:i:s')
            ]
        );
    }

    /**
     * add busy appointments to providers from google calendar events, outlook calendar events and amelia events
     *
     * @param array         $props
     * @param SlotsEntities $slotsEntities
     *
     * @return Collection
     * @throws Exception
     */
    public function getBookedAppointments($slotsEntities, $props)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $appointments */
        $appointments = new Collection();

        $startDateTime = DateTimeService::getCustomDateTimeObjectInUtc($props['startDateTime']->format('Y-m-d H:i:s'))
            ->format('Y-m-d H:i:s');

        if ($props['startDateTime']->format('Y-m-d') == DateTimeService::getNowDateTimeObjectInUtc()->format('Y-m-d')) {
            $startDateTime = DateTimeService::getCustomDateTimeObjectInUtc($props['startDateTime']->format('Y-m-d H:i:s'))
                ->setTime(0, 0, 0)
                ->format('Y-m-d H:i:s');
        }

        $appointmentRepository->getFutureAppointments(
            $appointments,
            $this->getAppointmentsProvidersIds(
                $slotsEntities->getProviders(),
                $slotsEntities->getResources()
            ),
            $startDateTime,
            DateTimeService::getCustomDateTimeObjectInUtc($props['endDateTime']->format('Y-m-d H:i:s'))
                ->modify('+1 day')
                ->format('Y-m-d H:i:s')
        );

        return $appointments;
    }

    /**
     * get slot settings
     *
     * @param bool          $isFrontEndBooking
     * @param SlotsEntities $slotsEntities
     * @param array         $customSettings
     *
     * @return array
     * @throws ContainerException
     */
    public function getSlotsSettings($isFrontEndBooking, $slotsEntities, $customSettings = [])
    {
        /** @var SettingsService $settingsDomainService */
        $settingsDomainService = $this->container->get('domain.settings.service');

        /** @var UserApplicationService $userApplicationService */
        $userApplicationService = $this->container->get('application.user.service');

        /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsApplicationService */
        $settingsApplicationService = $this->container->get('application.settings.service');

        return [
            'allowAdminBookAtAnyTime'    =>
                isset($customSettings['allowAdminBookAtAnyTime']) ? filter_var($customSettings['allowAdminBookAtAnyTime'], FILTER_VALIDATE_BOOLEAN) :
                (!$isFrontEndBooking &&
                $userApplicationService->isAdminAndAllowedToBookAtAnyTime()),
            'allowAdminBookOverApp'      => isset($customSettings['allowAdminBookOverApp']) ?
                filter_var($customSettings['allowAdminBookOverApp'], FILTER_VALIDATE_BOOLEAN) :
                (
                    !$isFrontEndBooking &&
                    $userApplicationService->isAdminAndAllowedToBookOver()
                ),
            'isGloballyBusySlot'         =>
                $settingsDomainService->getSetting('appointments', 'isGloballyBusySlot') &&
                !$slotsEntities->getResources()->length(),
            'allowBookingIfPending'      =>
                isset($customSettings['allowBookingIfPending']) ? filter_var($customSettings['allowBookingIfPending'], FILTER_VALIDATE_BOOLEAN) :
                $settingsDomainService->getSetting('appointments', 'allowBookingIfPending'),
            'allowBookingIfNotMin'       =>
                isset($customSettings['allowBookingIfNotMin']) ? filter_var($customSettings['allowBookingIfNotMin'], FILTER_VALIDATE_BOOLEAN) :
                $settingsDomainService->getSetting('appointments', 'allowBookingIfNotMin'),
            'openedBookingAfterMin'      =>
                $settingsDomainService->getSetting('appointments', 'openedBookingAfterMin'),
            'timeSlotLength'             => isset($customSettings['timeSlotLength']) ? (int)$customSettings['timeSlotLength'] :
                $settingsDomainService->getSetting('general', 'timeSlotLength'),
            'serviceDurationAsSlot'      =>
                isset($customSettings['serviceDurationAsSlot']) ? filter_var($customSettings['serviceDurationAsSlot'], FILTER_VALIDATE_BOOLEAN) :
                $settingsDomainService->getSetting('general', 'serviceDurationAsSlot'),
            'bufferTimeInSlot'           =>
                isset($customSettings['bufferTimeInSlot']) ? filter_var($customSettings['bufferTimeInSlot'], FILTER_VALIDATE_BOOLEAN) :
                $settingsDomainService->getSetting('general', 'bufferTimeInSlot'),
            'globalDaysOff'              => $settingsApplicationService->getGlobalDaysOff(),
            'adminServiceDurationAsSlot' => !$isFrontEndBooking &&
                $userApplicationService->isAdminAndAllowedToBookAtAnyTime() &&
                $settingsDomainService->getSetting('roles', 'adminServiceDurationAsSlot'),
            'limitPerEmployee' => $settingsDomainService->getSetting('roles', 'limitPerEmployee'),
        ];
    }

    /**
     * @param array $props
     *
     * @return SlotsEntities
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getSlotsEntities($props)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var AbstractResourceApplicationService $resourceApplicationService */
        $resourceApplicationService = $this->container->get('application.resource.service');

        /** @var AbstractLocationApplicationService $locationAS */
        $locationAS = $this->container->get('application.location.service');

        /** @var Collection $services */
        $services = $serviceRepository->getWithExtras(
            !empty($props['serviceCriteria']) ? $props['serviceCriteria'] : []
        );

        /** @var Collection $providers */
        $providers = $providerRepository->getWithSchedule(
            array_merge(
                [
                    'dates'          => [
                        DateTimeService::getNowDateTimeObject()->modify('-1 days')->format('Y-m-d H:i:s')
                    ],
                    'providers'      => $props['providerIds'],
                    'fetchCalendars' => true,
                ],
                $props['isFrontEndBooking'] ? ['providerStatus' => Status::VISIBLE, 'show' => 1] : []
            )
        );

        /** @var Collection $locations */
        $locations = $locationAS->getAllIndexedById();

        /** @var Collection $resources */
        $resources = $resourceApplicationService->getAll(['status' => Status::VISIBLE]);

        /** @var SlotsEntities $slotEntities */
        $slotEntities = SlotsEntitiesFactory::create();

        $slotEntities->setServices($services);

        $slotEntities->setProviders($providers);

        $slotEntities->setLocations($locations);

        $slotEntities->setResources($resources);

        return $slotEntities;
    }

    /**
     * @param array         $settings
     * @param array         $props
     * @param SlotsEntities $slotsEntities
     *
     * @return array
     * @throws ContainerException
     * @throws Exception
     */
    public function getSlotsByProps($settings, $props, $slotsEntities)
    {
        /** @var DomainTimeSlotService $timeSlotService */
        $timeSlotService = $this->container->get('domain.timeSlot.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $settings['defaultAppointmentStatus'] = $settingsService
            ->getEntitySettings($slotsEntities->getServices()->getItem($props['serviceId'])->getSettings())
            ->getGeneralSettings()
            ->getDefaultAppointmentStatus();

        /** @var Provider $provider */
        foreach ($slotsEntities->getProviders()->getItems() as $provider) {
            $provider->setAppointmentList(new Collection());
        }

        $this->setBlockerAppointments($slotsEntities->getProviders(), $props);

        return $timeSlotService->getSlots(
            $settings,
            $props,
            $slotsEntities,
            $this->getBookedAppointments($slotsEntities, $props)
        );
    }
}
