<?php

namespace AmeliaBooking\Domain\Services\Entity;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\SlotsEntities;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\SlotsEntitiesFactory;
use AmeliaBooking\Domain\Services\Resource\AbstractResourceService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Domain\ValueObjects\Duration;

/**
 * Class EntityService
 *
 * @package AmeliaBooking\Domain\Services\Entity
 */
class EntityService
{
    /** @var ProviderService */
    private $providerService;

    /** @var AbstractResourceService */
    private $resourceService;

    /**
     * EntityService constructor.
     *
     * @param ProviderService $providerService
     * @param AbstractResourceService $resourceService
     */
    public function __construct(
        ProviderService $providerService,
        AbstractResourceService $resourceService
    ) {
        $this->providerService = $providerService;

        $this->resourceService = $resourceService;
    }

    /**
     * get filtered entities needed for slots calculation.
     *
     * @param array         $settings
     * @param array         $props
     * @param SlotsEntities $slotsEntities
     *
     * @return SlotsEntities
     * @throws InvalidArgumentException
     */
    public function getFilteredSlotsEntities($settings, $props, $slotsEntities)
    {
        /** @var Collection $services */
        $services = $slotsEntities->getServices() ?: new Collection();

        /** @var Collection $providers */
        $providers = $slotsEntities->getProviders() ?: new Collection();

        /** @var Collection $locations */
        $locations = $slotsEntities->getLocations() ?: new Collection();

        if (!empty($props['locationIds'])) {
            /** @var Collection $filteredLocations */
            $filteredLocations = new Collection();
            foreach ($props['locationIds'] as $locationId) {
                if ($locations->keyExists($locationId)) {
                    $filteredLocations->addItem(
                        $locations->getItem($locationId),
                        $locationId
                    );
                }
            }
            $locations = $filteredLocations;
        }

        /** @var Collection $filteredProviders */
        $filteredProviders = new Collection();

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            if ($provider->getServiceList()->keyExists($props['serviceId'])) {
                if ($settings['allowAdminBookAtAnyTime']) {
                    $this->providerService->setProvidersAlwaysAvailable(
                        $providers
                    );
                }

                $this->providerService->setProviderServices(
                    $provider,
                    $services,
                    false
                );

                /** @var Service $service */
                foreach ($provider->getServiceList()->getItems() as $service) {
                    $this->checkServiceTimes($service);
                }

                $filteredProviders->addItem($provider, $provider->getId()->getValue());
            }
        }

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $this->checkServiceTimes($service);
        }

        /** @var Collection $serviceResources */
        $serviceResources = $slotsEntities->getResources() ? $this->resourceService->getServiceResources(
            $slotsEntities->getResources(),
            $props['serviceId']
        ) : new Collection();

        $this->resourceService->setNonSharedResources(
            $serviceResources,
            [
                'service'  => $services->keys(),
                'location' => $locations->keys(),
            ]
        );

        /** @var SlotsEntities $filteredSlotsEntities */
        $filteredSlotsEntities = SlotsEntitiesFactory::create();

        $filteredSlotsEntities->setServices($services);

        $filteredSlotsEntities->setProviders($filteredProviders);

        $filteredSlotsEntities->setLocations($locations);

        $filteredSlotsEntities->setResources($serviceResources);

        return $filteredSlotsEntities;
    }

    /**
     * Add 0 as duration for service time before or time after if it is null
     *
     * @param Service $service
     *
     * @throws InvalidArgumentException
     */
    private function checkServiceTimes($service)
    {
        if (!$service->getTimeBefore()) {
            $service->setTimeBefore(new Duration(0));
        }

        if (!$service->getTimeAfter()) {
            $service->setTimeAfter(new Duration(0));
        }
    }

    /**
     * filter appointments required for slots calculation
     *
     * @param SlotsEntities $slotsEntities
     * @param Collection    $appointments
     * @param array         $props
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function filterSlotsAppointments($slotsEntities, $appointments, $props)
    {
        /** @var Collection $services */
        $services = $slotsEntities->getServices();

        /** @var Collection $providers */
        $providers = $slotsEntities->getProviders();

        $providersIds = $providers->keys();

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $index => $appointment) {
            if (
                !in_array($appointment->getProviderId()->getValue(), $providersIds) ||
                (
                    $props['excludeAppointmentId'] && $index === $props['excludeAppointmentId']
                )
            ) {
                $appointments->deleteItem($index);
            }
        }

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $index => $appointment) {
            /** @var Provider $provider */
            $provider = $providers->getItem($appointment->getProviderId()->getValue());

            /** @var Service $providerService */
            $providerService = $provider->getServiceList()->keyExists($appointment->getServiceId()->getValue()) ?
                $provider->getServiceList()->getItem($appointment->getServiceId()->getValue()) :
                $services->getItem($appointment->getServiceId()->getValue());

            $appointment->setService($providerService);
        }
    }

    /**
     * Return required time for the appointment in seconds by summing service duration, service time before and after
     * and each passed extra.
     *
     * @param Service $service
     * @param array   $selectedExtras
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getAppointmentRequiredTime($service, $selectedExtras)
    {
        $requiredTime =
            $service->getTimeBefore()->getValue() +
            $service->getDuration()->getValue() +
            $service->getTimeAfter()->getValue();

        $extraIds = array_column($selectedExtras, 'id');

        /** @var Extra $extra */
        foreach ($service->getExtras()->getItems() as $extra) {
            if (in_array($extra->getId()->getValue(), $extraIds, false)) {
                if (!$extra->getDuration()) {
                    $extra->setDuration(new Duration(0));
                }

                $requiredTime += ($extra->getDuration()->getValue() *
                    array_column($selectedExtras, 'quantity', 'id')[$extra->getId()->getValue()]);
            }
        }

        return $requiredTime;
    }
}
