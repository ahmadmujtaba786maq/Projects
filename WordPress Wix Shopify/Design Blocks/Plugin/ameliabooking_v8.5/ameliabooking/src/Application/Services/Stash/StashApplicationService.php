<?php

namespace AmeliaBooking\Application\Services\Stash;

use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Location\AbstractLocationApplicationService;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageService;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Location\LocationFactory;
use AmeliaBooking\Domain\Factory\User\ProviderFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventTagsRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use Interop\Container\Exception\ContainerException;

/**
 * Class StashApplicationService
 *
 * @package AmeliaBooking\Application\Stash
 */
class StashApplicationService
{
    private $container;

    /**
     * StashApplicationService constructor.
     *
     * @param Container $container
     *
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $daysOffNew
     *
     * @return void
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws ContainerException
     */
    public function setStash($daysOffNew = null)
    {
        /** @var SettingsService $settingsDomainService */
        $settingsDomainService = $this->container->get('domain.settings.service');

        /** @var ProviderApplicationService $providerAS */
        $providerAS = $this->container->get('application.user.provider.service');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        /** @var ProviderService $providerService */
        $providerService = $this->container->get('domain.user.provider.service');

        /** @var AbstractLocationApplicationService $locationAS */
        $locationAS = $this->container->get('application.location.service');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        /** @var EventTagsRepository $eventTagsRepository */
        $eventTagsRepository = $this->container->get('domain.booking.event.tag.repository');

        /** @var Collection $events */
        $events = $eventAS->getEventsByCriteria(
            [
                'dates' => [DateTimeService::getNowDateTime()],
                'show'  => 1
            ],
            [
            ],
            0
        );

        /** @var TaxApplicationService $taxApplicationService */
        $taxApplicationService = $this->container->get('application.tax.service');

        /** @var Collection $taxes */
        $taxes = $taxApplicationService->getAll();

        /** @var Collection $services */
        $services = $serviceRepository->getAllArrayIndexedById();

        /** @var Collection $locations */
        $locations = $locationAS->getAllOrderedByName();

        /** @var Collection $providers */
        $providers = $providerRepository->getWithSchedule(
            ['dates' => [DateTimeService::getNowDateTimeObject()->modify('-1 days')->format('Y-m-d H:i:s')]]
        );


        /** @var Collection $availableLocations */
        $availableLocations = new Collection();

        /** @var Collection $availableServices */
        $availableServices = new Collection();

        /** @var Collection $availableProviders */
        $availableProviders = new Collection();

        $entitiesRelations = [];

        /** @var Provider $provider */
        foreach ($providers->getItems() as $providerId => $provider) {
            if ($provider->getLocationId() && !$availableLocations->keyExists($provider->getLocationId()->getValue())) {
                $availableLocations->addItem(
                    $locations->getItem($provider->getLocationId()->getValue()),
                    $provider->getLocationId()->getValue()
                );
            }

            $providerService->setProviderServices($provider, $services, true);

            if ($data = $providerAS->getProviderServiceLocations($provider, $locations, $services, true)) {
                $entitiesRelations[$providerId] = $data;
            }
        }

        foreach ($entitiesRelations as $providerId => $providerServiceRelations) {
            foreach ($providerServiceRelations as $serviceId => $serviceLocationRelations) {
                foreach ($serviceLocationRelations as $locationId) {
                    if ($locationId && !$availableLocations->keyExists($locationId)) {
                        $availableLocations->addItem($locations->getItem($locationId), $locationId);
                    }
                }

                if (!$availableServices->keyExists($serviceId)) {
                    $availableServices->addItem($services->getItem($serviceId), $serviceId);
                }
            }

            $availableProviders->addItem($providers->getItem($providerId), $providerId);
        }

        /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsAS*/
        $settingsAS = $this->container->get('application.settings.service');
        $daysOff    =  $settingsAS->getDaysOff($daysOffNew);

        $resultData = [
            'categories'   => [],
            'employees'    => [],
            'locations'    => [],
            'customFields' => [],
            'tags'         => [],
            'packages'     => [],
            'settings'     => [
                'daysOff' => $daysOff
            ],
        ];

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            if ($event->getLocationId() && !$availableLocations->keyExists($event->getLocationId()->getValue())) {
                $availableLocations->addItem(
                    $locations->getItem($event->getLocationId()->getValue()),
                    $event->getLocationId()->getValue()
                );
            }
        }

        /** @var Collection $eventsTags */
        $eventsTags = $eventTagsRepository->getAllDistinctByCriteria([]);

        $resultData['tags'] = $eventsTags->toArray();

        if ($locations->length() && !$availableLocations->length()) {
            $settingsDomainService->setStashEntities($resultData);

            return;
        }


        /** @var Location $location */
        foreach ($availableLocations->getItems() as $location) {
            $resultData['locations'][] = [
                'id'     => $location->getId()->getValue(),
                'name'   => $location->getName()->getValue(),
                'status' => $location->getStatus()->getValue(),
                'address' => $location->getAddress()->getValue(),
                'description' => $location->getDescription() ? $location->getDescription()->getValue() : null,
                'translations' => $location->getTranslations() ? $location->getTranslations()->getValue() : null
            ];
        }


        /** @var Collection $categories */
        $categories = $categoryRepository->getAllIndexedById();

        $availableCategories = new Collection();

        /** @var Service $service */
        foreach ($availableServices->getItems() as $service) {
            if (!$availableCategories->keyExists($service->getCategoryId()->getValue())) {
                /** @var Category $category */
                $category = $categories->getItem($service->getCategoryId()->getValue());

                $category->setServiceList(new Collection());

                $availableCategories->addItem(
                    $category,
                    $service->getCategoryId()->getValue()
                );
            }

            /** @var Category $category */
            $category = $availableCategories->getItem($service->getCategoryId()->getValue());

            $category->getServiceList()->addItem($service, $service->getId()->getValue());
        }

        $resultData['categories'] = $availableCategories->toArray();


        /** @var Provider $provider */
        foreach ($availableProviders->getItems() as $provider) {
            $providerData = [
                'id'               => $provider->getId()->getValue(),
                'firstName'        => $provider->getFirstName()->getValue(),
                'lastName'         => $provider->getLastName()->getValue(),
                'email'            => $provider->getEmail()->getValue(),
                'status'           => $provider->getStatus()->getValue(),
                'show'             => $provider->getShow()->getValue(),
                'pictureFullPath'  => $provider->getPicture() ? $provider->getPicture()->getFullPath() : null,
                'pictureThumbPath' => $provider->getPicture() ? $provider->getPicture()->getThumbPath() : null,
                'locationId'       => $provider->getLocationId() ? $provider->getLocationId()->getValue() : null,
                'serviceList'      => [],
                'weekDayList'      => $provider->getWeekDayList()->toArray(),
                'dayOffList'       => $provider->getDayOffList()->toArray(),
                'specialDayList'   => $provider->getSpecialDayList()->toArray(),
                'translations'     => $provider->getTranslations() ? $provider->getTranslations()->getValue() : null,
                'description'      => $provider->getDescription() ? $provider->getDescription()->getValue() : null,
                'badgeId'          => $provider->getBadgeId() ? $provider->getBadgeId()->getValue() : null,
            ];

            /** @var Service $service */
            foreach ($provider->getServiceList()->getItems() as $service) {
                if ($availableServices->keyExists($service->getId()->getValue())) {
                    $providerData['serviceList'][] = [
                        'id'            => $service->getId()->getValue(),
                        'price'         => $service->getPrice()->getValue(),
                        'duration'      => $service->getDuration()->getValue(),
                        'customPricing' => $service->getCustomPricing()
                            ? $service->getCustomPricing()->getValue() : null,
                        'minCapacity'   => $service->getMinCapacity()->getValue(),
                        'maxCapacity'   => $service->getMaxCapacity()->getValue(),
                        'status'        => $service->getStatus()->getValue(),
                        'categoryId'    => $service->getCategoryId()->getValue()
                    ];
                }
            }

            $resultData['employees'][] = $providerData;
        }


        /** @var Collection $customFields */
        $customFields = $customFieldRepository->getAll();

        /** @var CustomField $customField */
        foreach ($customFields->getItems() as $customField) {
            $customFieldData = array_merge(
                $customField->toArray(),
                [
                    'services' => [],
                    'events'   => []
                ]
            );

            /** @var Service $service */
            foreach ($customField->getServices()->getItems() as $service) {
                $customFieldData['services'][] = [
                    'id' => $service->getId()->getValue()
                ];
            }

            /** @var Event $event */
            foreach ($customField->getEvents()->getItems() as $event) {
                $customFieldData['events'][] = [
                    'id' => $event->getId()->getValue()
                ];
            }

            $resultData['customFields'][] = $customFieldData;
        }


        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var Collection $packages */
        $packages = $packageRepository->getByCriteria([]);

        /** @var Package $package */
        foreach ($packages->getItems() as $package) {
            $packageData = array_merge(
                $package->toArray(),
                ['bookable' => []]
            );

            /** @var PackageService $bookable */
            foreach ($package->getBookable()->getItems() as $bookable) {
                $bookableData = array_merge(
                    $bookable->toArray(),
                    [
                        'service'   => ['id' => $bookable->getService()->getId()->getValue()],
                        'providers' => [],
                        'locations' => [],
                    ]
                );

                /** @var Provider $provider */
                foreach ($bookable->getProviders()->getItems() as $provider) {
                    $bookableData['providers'][] = [
                        'id' => $provider->getId()->getValue()
                    ];
                }

                /** @var Location $location */
                foreach ($bookable->getLocations()->getItems() as $location) {
                    $bookableData['locations'][] = [
                        'id' => $location->getId()->getValue()
                    ];
                }

                $packageData['bookable'][] = $bookableData;
            }

            $resultData['packages'][] = $packageData;
        }

        /** @var Tax $tax */
        foreach ($taxes->getItems() as $tax) {
            $taxData = [
                'id'          => $tax->getId()->getValue(),
                'name'        => $tax->getName()->getValue(),
                'type'        => $tax->getType()->getValue(),
                'amount'      => $tax->getAmount()->getValue(),
                'serviceList' => [],
                'extraList'   => [],
                'packageList' => [],
                'eventList'   => [],
            ];

            /** @var Service $service */
            foreach ($tax->getServiceList()->getItems() as $service) {
                $taxData['serviceList'][] = [
                    'id' => $service->getId()->getValue(),
                ];
            }

            /** @var Extra $extra */
            foreach ($tax->getExtraList()->getItems() as $extra) {
                $taxData['extraList'][] = [
                    'id' => $extra->getId()->getValue(),
                ];
            }

            /** @var Package $package */
            foreach ($tax->getPackageList()->getItems() as $package) {
                $taxData['packageList'][] = [
                    'id' => $package->getId()->getValue(),
                ];
            }

            /** @var Event $event */
            foreach ($tax->getEventList()->getItems() as $event) {
                $taxData['eventList'][] = [
                    'id' => $event->getId()->getValue(),
                ];
            }

            $resultData['taxes'][] = $taxData;
        }

        $settingsDomainService->setStashEntities($resultData);
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function getStash()
    {
        /** @var ProviderApplicationService $providerAS */
        $providerAS = $this->container->get('application.user.provider.service');

        /** @var SettingsService $settingsDomainService */
        $settingsDomainService = $this->container->get('domain.settings.service');

        $entitiesData = $settingsDomainService->getStashEntities();

        if ($entitiesData) {
            /** @var Collection $locations */
            $locations = new Collection();

            foreach ($entitiesData['locations'] as $locationData) {
                $locations->addItem(LocationFactory::create($locationData), $locationData['id']);
            }


            /** @var Collection $services */
            $services = new Collection();

            foreach ($entitiesData['categories'] as $categoryData) {
                foreach ($categoryData['serviceList'] as $serviceData) {
                    $services->addItem(ServiceFactory::create($serviceData), $serviceData['id']);
                }
            }


            /** @var Collection $providers */
            $providers = new Collection();

            foreach ($entitiesData['employees'] as &$employeeData) {
                $employeeData['type'] = AbstractUser::USER_ROLE_PROVIDER;

                $providers->addItem(ProviderFactory::create($employeeData), $employeeData['id']);

                unset(
                    $employeeData['email'],
                    $employeeData['weekDayList'],
                    $employeeData['specialDayList'],
                    $employeeData['dayOffList']
                );
            }

            $entitiesRelations = [];

            /** @var Provider $provider */
            foreach ($providers->getItems() as $providerId => $provider) {
                if ($data = $providerAS->getProviderServiceLocations($provider, $locations, $services)) {
                    $entitiesRelations[$providerId] = $data;
                }
            }

            $currentDateTime = DateTimeService::getNowDateTimeObject();

            foreach ($entitiesData['packages'] as &$packageData) {
                $packageData['available'] =
                    !$packageData['endDate'] ||
                    DateTimeService::getCustomDateTimeObject($packageData['endDate']) > $currentDateTime;
            }

            $entitiesData['entitiesRelations'] = $entitiesRelations;
        }

        return $entitiesData;
    }
}
