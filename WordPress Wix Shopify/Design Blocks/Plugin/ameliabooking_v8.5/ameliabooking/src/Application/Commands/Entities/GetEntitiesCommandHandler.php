<?php

namespace AmeliaBooking\Application\Commands\Entities;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Coupon\AbstractCouponApplicationService;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Location\AbstractLocationApplicationService;
use AmeliaBooking\Application\Services\Resource\AbstractResourceApplicationService;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\Booking\EventDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Licence\Licence as Licence;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventTagsRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\LessonSpace\AbstractLessonSpaceService;
use AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetEntitiesCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Entities
 */
class GetEntitiesCommandHandler extends CommandHandler
{
    /**
     * @param GetEntitiesCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function handle(GetEntitiesCommand $command)
    {
        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

        /** @var EventDomainService $eventDS */
        $eventDS = $this->container->get('domain.booking.event.service');

        /** @var ProviderApplicationService $providerAS */
        $providerAS = $this->container->get('application.user.provider.service');

        /** @var AbstractCustomFieldApplicationService $customFieldAS */
        $customFieldAS = $this->container->get('application.customField.service');

        /** @var AbstractLocationApplicationService $locationAS */
        $locationAS = $this->container->get('application.location.service');

        /** @var AbstractCouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        /** @var ProviderService $providerService */
        $providerService = $this->container->get('domain.user.provider.service');

        try {
            /** @var AbstractUser $currentUser */
            $currentUser = $command->getUserApplicationService()->authorization(
                $command->getPage() === 'cabinet' ? $command->getToken() : null,
                $command->getCabinetType()
            );
        } catch (AuthorizationException $e) {
            $currentUser =  null;
        }

        $params = $command->getField('params');

        $params['types'] = !empty($params['types']) ? $params['types'] : [];

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var Collection $allServices */
        $allServices = new Collection();

        /** @var Collection $services */
        $services = new Collection();

        /** @var Collection $locations */
        $locations = new Collection();

        /** @var Collection $categories */
        $categories = new Collection();

        /** @var Collection $events */
        $events = new Collection();

        $resultData = [];

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');


        /** Events */
        if (in_array(Entities::EVENTS, $params['types'], true)) {
            /** @var EventApplicationService $eventAS */
            $eventAS = $this->container->get('application.booking.event.service');

            /** @var Collection $events */
            $events = $eventAS->getEventsByCriteria(
                [
                    'dates' => [DateTimeService::getNowDateTime()],
                    'page'  => 1,
                ],
                [
                    'fetchEventsPeriods' => true,
                ],
                $settingsDS->getSetting('general', 'eventsFilterLimit') ?: 1000
            );

            $resultData['events'] = $eventDS->getShortcodeForEventList($this->container, $events->toArray());
        }

        /** Event Tags */
        if (in_array(Entities::TAGS, $params['types'], true)) {
            /** @var EventTagsRepository $eventTagsRepository */
            $eventTagsRepository = $this->container->get('domain.booking.event.tag.repository');

            /** @var Collection $eventsTags */
            $eventsTags = $eventTagsRepository->getAllDistinctByCriteria(
                $events->length() ? ['eventIds' => array_column($events->toArray(), 'id')] : []
            );

            $resultData['tags'] = $eventsTags->toArray();
        }

        if (
            in_array(Entities::LOCATIONS, $params['types'], true) ||
            in_array(Entities::EMPLOYEES, $params['types'], true)
        ) {
            /** @var Collection $locations */
            $locations = $locationAS->getAllOrderedByName();
        }

        /** Locations */
        if (in_array(Entities::LOCATIONS, $params['types'], true)) {
            $resultData['locations'] = $locations->toArray();
        }

        if (
            in_array(Entities::CATEGORIES, $params['types'], true) ||
            in_array(Entities::EMPLOYEES, $params['types'], true) ||
            in_array(Entities::COUPONS, $params['types'], true)
        ) {
            /** @var ServiceRepository $serviceRepository */
            $serviceRepository = $this->container->get('domain.bookable.service.repository');
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->container->get('domain.bookable.category.repository');
            /** @var BookableApplicationService $bookableAS */
            $bookableAS = $this->container->get('application.bookable.service');

            /** @var Collection $allServices */
            $allServices = $serviceRepository->getAllArrayIndexedById();

            /** @var Service $service */
            foreach ($allServices->getItems() as $service) {
                if (
                    $service->getStatus()->getValue() === Status::VISIBLE ||
                    Licence::$premium ||
                    ($currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_ADMIN)
                ) {
                    $services->addItem($service, $service->getId()->getValue());
                }
            }

            /** @var Collection $categories */
            $categories = $categoryRepository->getAllIndexedById();

            $bookableAS->addServicesToCategories($categories, $services);
        }

        /** Categories */
        if (in_array(Entities::CATEGORIES, $params['types'], true)) {
            $resultData['categories'] = $categories->toArray();
        }


        $resultData['customers'] = [];

        /** Customers */
        if (in_array(Entities::CUSTOMERS, $params['types'], true)) {
            /** @var UserRepository $userRepo */
            $userRepo = $this->getContainer()->get('domain.users.repository');

            $resultData['customers'] = [];

            if ($currentUser) {
                switch ($currentUser->getType()) {
                    case (AbstractUser::USER_ROLE_CUSTOMER):
                        if ($currentUser->getId()) {
                            /** @var Customer $customer */
                            $customer = $userRepo->getById($currentUser->getId()->getValue());

                            $resultData['customers'] = [$customer->toArray()];
                        }

                        break;

                    case (AbstractUser::USER_ROLE_PROVIDER):
                        $resultData['customers'] = $providerAS->getAllowedCustomers($currentUser)->toArray();

                        break;

                    default:
                        /** @var Collection $customers */
                        $customers = $userRepo->getAllWithAllowedBooking();

                        $resultData['customers'] = $customers->toArray();
                }
            }

            $noShowTagEnabled = $settingsDS->getSetting('roles', 'enableNoShowTag');

            if ($noShowTagEnabled && $resultData['customers']) {
                /** @var CustomerBookingRepository $bookingRepository */
                $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

                $usersIds = array_map(
                    function ($user) {
                        return $user['id'];
                    },
                    $resultData['customers']
                );

                $customersNoShowCount =  $bookingRepository->countByNoShowStatus($usersIds);

                foreach ($resultData['customers'] as $key => $customer) {
                    $resultData['customers'][$key]['noShowCount'] = $customersNoShowCount[$key]['count'];
                }
            }
        }

        /** Providers */
        if (in_array(Entities::EMPLOYEES, $params['types'], true)) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');

            /** @var Collection $providers */
            $providers = $providerRepository->getWithSchedule(
                [
                    'dates' => !empty($params['dates']) ? $params['dates'] : [
                        DateTimeService::getNowDateTimeObject()->modify('-1 days')->format('Y-m-d H:i:s')
                    ],
                    'fetchCalendars' => $currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_ADMIN,
                ]
            );

            /** @var Provider $provider */
            foreach ($providers->getItems() as $provider) {
                $providerService->setProviderServices($provider, $services, true);
            }

            if (
                array_key_exists('page', $params) &&
                in_array($params['page'], [Entities::CALENDAR, Entities::APPOINTMENTS]) &&
                $userAS->isAdminAndAllowedToBookAtAnyTime()
            ) {
                $providerService->setProvidersAlwaysAvailable($providers);
            }

            $resultData['entitiesRelations'] = [];

            /** @var Provider $provider */
            foreach ($providers->getItems() as $providerId => $provider) {
                if ($data = $providerAS->getProviderServiceLocations($provider, $locations, $services)) {
                    $resultData['entitiesRelations'][$providerId] = $data;
                }
            }


            $resultData['employees'] = $providerAS->removeAllExceptUser(
                $providers->toArray(),
                (array_key_exists('page', $params) && $params['page'] === Entities::BOOKING) ?
                    null : $currentUser
            );

            if (
                $currentUser === null ||
                $currentUser->getType() === AbstractUser::USER_ROLE_CUSTOMER ||
                !$command->getPermissionService()->currentUserCanRead(Entities::EMPLOYEES)
            ) {
                foreach ($resultData['employees'] as &$employee) {
                    unset(
                        $employee['appleCalendarId'],
                        $employee['googleCalendar'],
                        $employee['outlookCalendar'],
                        $employee['stripeConnect'],
                        $employee['birthday'],
                        $employee['email'],
                        $employee['externalId'],
                        $employee['phone'],
                        $employee['note'],
                        $employee['employeeAppleCalendar'],
                    );

                    if (isset($params['page']) && $params['page'] !== Entities::CALENDAR) {
                        unset(
                            $employee['weekDayList'],
                            $employee['specialDayList'],
                            $employee['dayOffList']
                        );
                    }
                }
            }
        }

        $resultData[Entities::APPOINTMENTS] = [
            'futureAppointments' => [],
        ];

        if (in_array(Entities::APPOINTMENTS, $params['types'], true)) {
            $userParams = [
                'dates' => [null, null]
            ];

            if (!$command->getPermissionService()->currentUserCanReadOthers(Entities::APPOINTMENTS)) {
                if ($currentUser->getId() === null) {
                    $userParams[$currentUser->getType() . 'Id'] = 0;
                } else {
                    $userParams[$currentUser->getType() . 'Id'] =
                        $currentUser->getId()->getValue();
                }
            }

            /** @var AppointmentRepository $appointmentRepo */
            $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

            /** @var Collection $appointments */
            $appointments = $appointmentRepo->getFiltered($userParams);

            $resultData[Entities::APPOINTMENTS] = [
                'futureAppointments' => $appointments->toArray(),
            ];
        }

        /** Custom Fields */
        if (
            in_array(Entities::CUSTOM_FIELDS, $params['types'], true) ||
            in_array('customFields', $params['types'], true)
        ) {
            /** @var Collection $customFields */
            $customFields = $customFieldAS->getAll();

            if (!empty($params['lite'])) {
                $resultData['customFields'] = [];

                /** @var CustomField $customField */
                foreach ($customFields->getItems() as $customField) {
                    $item = array_merge(
                        $customField->toArray(),
                        [
                            'services' => [],
                            'events'   => [],
                        ]
                    );

                    /** @var Service $service */
                    foreach ($customField->getServices()->getItems() as $service) {
                        $item['services'][] = [
                            'id' => $service->getId()->getValue()
                        ];
                    }

                    /** @var Event $event */
                    foreach ($customField->getEvents()->getItems() as $event) {
                        $item['events'][] = [
                            'id' => $event->getId()->getValue()
                        ];
                    }

                    $resultData['customFields'][] = $item;
                }
            } else {
                $resultData['customFields'] = $customFields->toArray();
            }
        }

        /** Coupons */
        if (
            in_array(Entities::COUPONS, $params['types'], true) &&
            $this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::COUPONS)
        ) {
            /** @var Collection $coupons */
            $coupons = $couponAS->getAll();

            /** @var CouponRepository $couponRepository */
            $couponRepository = $this->container->get('domain.coupon.repository');

            /** @var EventRepository $eventRepository */
            $eventRepository = $this->container->get('domain.booking.event.repository');

            /** @var PackageRepository $packageRepository */
            $packageRepository = $this->container->get('domain.bookable.package.repository');

            if ($coupons->length()) {
                foreach ($couponRepository->getCouponsServicesIds($coupons->keys()) as $ids) {
                    /** @var Coupon $coupon */
                    $coupon = $coupons->getItem($ids['couponId']);

                    if ($coupon->getAllServices() && $coupon->getAllServices()->getValue()) {
                        $coupon->setServiceList(new Collection($allServices->getItems()));
                        continue;
                    }

                    $coupon->getServiceList()->addItem(
                        $allServices->getItem($ids['serviceId']),
                        $ids['serviceId']
                    );
                }

                /** @var Collection $allEvents */
                $allEvents = $eventRepository->getAllIndexedById();

                foreach ($couponRepository->getCouponsEventsIds($coupons->keys()) as $ids) {
                    /** @var Coupon $coupon */
                    $coupon = $coupons->getItem($ids['couponId']);

                    if ($coupon->getAllEvents() && $coupon->getAllEvents()->getValue()) {
                        $coupon->setEventList(new Collection($allEvents->getItems()));
                        continue;
                    }

                    $coupon->getEventList()->addItem(
                        $allEvents->getItem($ids['eventId']),
                        $ids['eventId']
                    );
                }

                /** @var Collection $allPackages */
                $allPackages = $packageRepository->getAllIndexedById();

                foreach ($couponRepository->getCouponsPackagesIds($coupons->keys()) as $ids) {
                    /** @var Coupon $coupon */
                    $coupon = $coupons->getItem($ids['couponId']);

                    if ($coupon->getAllPackages() && $coupon->getAllPackages()->getValue()) {
                        $coupon->setPackageList(new Collection($allPackages->getItems()));
                        continue;
                    }

                    $coupon->getPackageList()->addItem(
                        $allPackages->getItem($ids['packageId']),
                        $ids['packageId']
                    );
                }
            }

            if (!empty($params['lite'])) {
                $resultData['coupons'] = [];

                /** @var Coupon $coupon */
                foreach ($coupons->getItems() as $coupon) {
                    $item = array_merge(
                        $coupon->toArray(),
                        [
                            'serviceList' => [],
                            'eventList'   => [],
                            'packageList' => [],
                        ]
                    );

                    /** @var Service $service */
                    foreach ($coupon->getServiceList()->getItems() as $service) {
                        $item['serviceList'][] = [
                            'id' => $service->getId()->getValue()
                        ];
                    }

                    /** @var Event $event */
                    foreach ($coupon->getEventList()->getItems() as $event) {
                        $item['eventList'][] = [
                            'id' => $event->getId()->getValue()
                        ];
                    }

                    /** @var Package $package */
                    foreach ($coupon->getPackageList()->getItems() as $package) {
                        $item['packageList'][] = [
                            'id' => $package->getId()->getValue()
                        ];
                    }

                    $resultData['coupons'][] = $item;
                }
            } else {
                $resultData['coupons'] = $coupons->toArray();
            }
        }

        /** Settings */
        if (in_array(Entities::SETTINGS, $params['types'], true)) {
            /** @var HelperService $helperService */
            $helperService = $this->container->get('application.helper.service');

            $languages = $helperService->getLanguages();

            usort(
                $languages,
                function ($x, $y) {
                    return strcasecmp($x['name'], $y['name']);
                }
            );

            $languagesSorted = [];

            foreach ($languages as $language) {
                $languagesSorted[$language['wp_locale']] = $language;
            }

            /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsAS*/
            $settingsAS = $this->container->get('application.settings.service');

            $daysOff = $settingsAS->getDaysOff();

            $squareLocations = [];
            if (
                !empty($settingsDS->getSetting('payments', 'square')['accessToken']['access_token'])
                && in_array('squareLocations', $params['types'])
            ) {
                /** @var SquareService $squareService */
                $squareService = $this->container->get('infrastructure.payment.square.service');

                try {
                    $squareLocations = $squareService->getLocations();
                } catch (\Exception $e) {
                }
            }

            $mailchimpLists = [];
            if (
                !empty($settingsDS->getSetting('mailchimp', 'accessToken'))
                && in_array('mailchimpLists', $params['types'])
            ) {
                /** @var AbstractMailchimpService $mailchimpService */
                $mailchimpService = $this->container->get('infrastructure.mailchimp.service');

                try {
                    $mailchimpLists = $mailchimpService->getLists();

                    $mailchimpSettings = $settingsDS->getCategorySettings('mailchimp');
                    if (!empty($mailchimpLists) && !$mailchimpSettings['list']) {
                        $mailchimpSettings['list'] = $mailchimpLists[0];
                        $settingsDS->setCategorySettings('mailchimp', $mailchimpSettings);
                    }
                } catch (\Exception $e) {
                }
            }

            $resultData['settings'] = [
                'general'   => [
                    'usedLanguages' => $settingsDS->getSetting('general', 'usedLanguages'),
                ],
                'languages' => $languagesSorted,
                'daysOff'   => $daysOff,
                'squareLocations' => $squareLocations,
                'mailchimpLists' => $mailchimpLists,
            ];
        }

        /** Packages */
        if (in_array(Entities::PACKAGES, $params['types'], true)) {
            /** @var AbstractPackageApplicationService $packageApplicationService */
            $packageApplicationService = $this->container->get('application.bookable.package');

            $resultData['packages'] = $packageApplicationService->getPackagesArray();
        }

        /** Resources */
        if (in_array(Entities::RESOURCES, $params['types'], true)) {
            /** @var AbstractResourceApplicationService $resourceApplicationService */
            $resourceApplicationService = $this->container->get('application.resource.service');

            /** @var Collection $resources */
            $resources = $resourceApplicationService->getAll([]);

            $resultData['resources'] = $resources->toArray();
        }

        /** Taxes */
        if (in_array(Entities::TAXES, $params['types'], true)) {
            /** @var TaxApplicationService $taxApplicationService */
            $taxApplicationService = $this->container->get('application.tax.service');

            /** @var Collection $taxes */
            $taxes = $taxApplicationService->getAll();

            $resultData['taxes'] = $taxes->toArray();
        }

        /** Lesson Spaces */
        if (
            in_array('lessonSpace_spaces', $params['types'], true) ||
            in_array('spaces', $params['types'], true)
        ) {
            $lessonSpaceApiKey    = $settingsDS->getSetting('lessonSpace', 'apiKey');
            $lessonSpaceEnabled   = $settingsDS->getSetting('lessonSpace', 'enabled');
            $lessonSpaceCompanyId = $settingsDS->getSetting('lessonSpace', 'companyId');

            if ($lessonSpaceEnabled && $lessonSpaceApiKey) {
                /** @var AbstractLessonSpaceService $lessonSpaceService */
                $lessonSpaceService = $this->container->get('infrastructure.lesson.space.service');

                if (empty($lessonSpaceCompanyId)) {
                    $companyDetails       = $lessonSpaceService->getCompanyId($lessonSpaceApiKey);
                    $lessonSpaceCompanyId = !empty($companyDetails) && !empty($companyDetails['id']) ? $companyDetails['id'] : null;
                }

                $resultData['spaces'] = $lessonSpaceService->getAllSpaces(
                    $lessonSpaceApiKey,
                    $lessonSpaceCompanyId,
                    !empty($params['lessonSpaceSearch']) ? $params['lessonSpaceSearch'] : null
                );
            }
        }


        $resultData = apply_filters('amelia_get_entities_filter', $resultData);

        do_action('amelia_get_entities', $resultData);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved entities');
        $result->setData($resultData);

        return $result;
    }
}
