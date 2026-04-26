<?php

namespace AmeliaBooking\Application\Services\User;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\Entity\Schedule\Period;
use AmeliaBooking\Domain\Entity\Schedule\PeriodLocation;
use AmeliaBooking\Domain\Entity\Schedule\PeriodService;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriod;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodLocation;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodService;
use AmeliaBooking\Domain\Entity\Schedule\TimeOut;
use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Location\ProviderLocationFactory;
use AmeliaBooking\Domain\Factory\Schedule\PeriodLocationFactory;
use AmeliaBooking\Domain\Factory\Schedule\SpecialDayPeriodLocationFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Repository\User\UserRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Interval\IntervalService;
use AmeliaBooking\Domain\Services\Location\LocationService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Password;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageServiceProviderRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ProviderServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceEntitiesRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventProvidersRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Google\GoogleCalendarRepository;
use AmeliaBooking\Infrastructure\Repository\Location\ProviderLocationRepository;
use AmeliaBooking\Infrastructure\Repository\Outlook\OutlookCalendarRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\DayOffRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodLocationRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodLocationRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\TimeOutRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\WeekDayRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class ProviderApplicationService
 *
 * @package AmeliaBooking\Application\Services\User
 */
class ProviderApplicationService
{
    private $container;

    /**
     * ProviderApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    /**
     * @param Provider $user
     *
     * @return int
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function add($user)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');

        /** @var ProviderLocationRepository $providerLocationRepo */
        $providerLocationRepo = $this->container->get('domain.bookable.service.providerLocation.repository');

        /** @var DayOffRepository $dayOffRepository */
        $dayOffRepository = $this->container->get('domain.schedule.dayOff.repository');

        /** @var WeekDayRepository $weekDayRepository */
        $weekDayRepository = $this->container->get('domain.schedule.weekDay.repository');

        /** @var TimeOutRepository $timeOutRepository */
        $timeOutRepository = $this->container->get('domain.schedule.timeOut.repository');

        /** @var PeriodRepository $periodRepository */
        $periodRepository = $this->container->get('domain.schedule.period.repository');

        /** @var PeriodServiceRepository $periodServiceRepository */
        $periodServiceRepository = $this->container->get('domain.schedule.period.service.repository');

        /** @var PeriodLocationRepository $periodLocationRepository */
        $periodLocationRepository = $this->container->get('domain.schedule.period.location.repository');

        /** @var SpecialDayRepository $specialDayRepository */
        $specialDayRepository = $this->container->get('domain.schedule.specialDay.repository');

        /** @var SpecialDayPeriodRepository $specialDayPeriodRepository */
        $specialDayPeriodRepository = $this->container->get('domain.schedule.specialDay.period.repository');

        /** @var SpecialDayPeriodServiceRepository $specialDayPeriodServiceRepository */
        $specialDayPeriodServiceRepository =
            $this->container->get('domain.schedule.specialDay.period.service.repository');

        /** @var SpecialDayPeriodLocationRepository $specialDayPeriodLocationRepository */
        $specialDayPeriodLocationRepository =
            $this->container->get('domain.schedule.specialDay.period.location.repository');

        $this->modifyPeriodsWithSingleLocationBeforePersist($user->getWeekDayList());
        $this->modifyPeriodsWithSingleLocationBeforePersist($user->getSpecialDayList());

        // add provider
        $userId = $userRepository->add($user);

        $user->setId(new Id($userId));


        if ($user->getLocationId()) {
            $providerLocation = ProviderLocationFactory::create(
                [
                    'userId'     => $userId,
                    'locationId' => $user->getLocationId()->getValue()
                ]
            );

            $providerLocationRepo->add($providerLocation);
        }


        /**
         * Add provider services
         */
        foreach ((array)$user->getServiceList()->keys() as $key) {
            if (!($service = $user->getServiceList()->getItem($key)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            $providerServiceRepo->add($service, $user->getId()->getValue());
        }


        // add provider day off
        foreach ((array)$user->getDayOffList()->keys() as $key) {
            if (!($providerDayOff = $user->getDayOffList()->getItem($key)) instanceof DayOff) {
                throw new InvalidArgumentException('Unknown type');
            }

            $providerDayOffId = $dayOffRepository->add($providerDayOff, $user->getId()->getValue());

            $providerDayOff->setId(new Id($providerDayOffId));
        }


        // add provider week day / time out
        foreach ((array)$user->getWeekDayList()->keys() as $weekDayKey) {
            // add day work hours
            /** @var WeekDay $weekDay */
            if (!($weekDay = $user->getWeekDayList()->getItem($weekDayKey)) instanceof WeekDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $weekDayId = $weekDayRepository->add($weekDay, $user->getId()->getValue());

            $weekDay->setId(new Id($weekDayId));


            // add day time out values
            foreach ((array)$weekDay->getTimeOutList()->keys() as $timeOutKey) {
                /** @var TimeOut $timeOut */
                if (!($timeOut = $weekDay->getTimeOutList()->getItem($timeOutKey)) instanceof TimeOut) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $timeOutId = $timeOutRepository->add($timeOut, $weekDayId);

                $timeOut->setId(new Id($timeOutId));
            }


            // add day period values
            foreach ((array)$weekDay->getPeriodList()->keys() as $periodKey) {
                /** @var Period $period */
                if (!($period = $weekDay->getPeriodList()->getItem($periodKey)) instanceof Period) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $periodId = $periodRepository->add($period, $weekDay->getId()->getValue());

                foreach ((array)$period->getPeriodServiceList()->keys() as $periodServiceKey) {
                    /** @var PeriodService $periodService */
                    $periodService = $period->getPeriodServiceList()->getItem($periodServiceKey);

                    $periodServiceRepository->add($periodService, $periodId);
                }

                foreach ((array)$period->getPeriodLocationList()->keys() as $periodLocationKey) {
                    /** @var PeriodLocation $periodLocation */
                    $periodLocation = $period->getPeriodLocationList()->getItem($periodLocationKey);

                    $periodLocationRepository->add($periodLocation, $periodId);
                }
            }
        }

        foreach ((array)$user->getSpecialDayList()->keys() as $specialDayKey) {
            // add special day work hours
            /** @var SpecialDay $specialDay */
            if (!($specialDay = $user->getSpecialDayList()->getItem($specialDayKey)) instanceof SpecialDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $specialDayId = $specialDayRepository->add($specialDay, $user->getId()->getValue());

            $specialDay->setId(new Id($specialDayId));

            // add special day period values
            foreach ((array)$specialDay->getPeriodList()->keys() as $periodKey) {
                /** @var SpecialDayPeriod $period */
                if (!($period = $specialDay->getPeriodList()->getItem($periodKey)) instanceof SpecialDayPeriod) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $periodId = $specialDayPeriodRepository->add($period, $specialDay->getId()->getValue());

                foreach ((array)$period->getPeriodServiceList()->keys() as $periodServiceKey) {
                    /** @var SpecialDayPeriodService $periodService */
                    $periodService = $period->getPeriodServiceList()->getItem($periodServiceKey);

                    $specialDayPeriodServiceRepository->add($periodService, $periodId);
                }

                foreach ((array)$period->getPeriodLocationList()->keys() as $periodLocationKey) {
                    /** @var SpecialDayPeriodLocation $periodLocation */
                    $periodLocation = $period->getPeriodLocationList()->getItem($periodLocationKey);

                    $specialDayPeriodLocationRepository->add($periodLocation, $periodId);
                }
            }
        }

        return $userId;
    }


    /**
     * @param array $fields
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function createProvider($fields, $bb = false)
    {
        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');
        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForProvider($fields);

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $result = new CommandResult();

        /** @var Provider $user */
        $user = UserFactory::create($fields);

        if (!($user instanceof AbstractUser)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create a new user entity.');

            return $result;
        }

        if ($oldUser = $providerRepository->getByEmail($user->getEmail()->getValue())) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Email already exist.');
            $result->setData('This email is already in use.');

            return $result;
        }

        $providerRepository->beginTransaction();

        try {
            $userId = $this->add($user);

            if ($fields['externalId'] === 0) {
                $userAS->setWpUserIdForNewUser($userId, $user, $fields['password']);
            }

            if (!empty($fields['password'])) {
                $newPassword = new Password($fields['password']);

                $providerRepository->updateFieldById($userId, $newPassword->getValue(), 'password');
            }

            $user->setId(new Id($userId));
        } catch (QueryExecutionException $e) {
            $providerRepository->rollback();
            throw $e;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new user.');
        $result->setData(
            [
                Entities::USER                 => $user->toArray(),
                'sendEmployeePanelAccessEmail' =>
                    !empty($fields['password']) && $fields['sendEmployeePanelAccessEmail'],
                'password'                     => !empty($fields['password']) ? $fields['password'] : null,
            ]
        );

        $providerRepository->commit();

        return $result;
    }


    /**
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function update($oldUser, $newUser)
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        $userRepository->update($oldUser->getId()->getValue(), $newUser);

        $this->updateProviderLocations($oldUser, $newUser);
        $this->updateProviderServices($newUser);
        $this->updateProviderDaysOff($oldUser, $newUser);
        $this->updateProviderWorkDays($oldUser, $newUser);
        $this->updateProviderSpecialDays($oldUser, $newUser);

        if ($newUser->getGoogleCalendar() && $newUser->getGoogleCalendar()->getId()) {
            $this->updateProviderGoogleCalendar($newUser);
        }

        if ($newUser->getOutlookCalendar() && $newUser->getOutlookCalendar()->getId()) {
            $this->updateProviderOutlookCalendar($newUser);
        }

        return true;
    }

    /**
     * Modify period for persist if there is only one location in period
     *
     * @param Collection $dayList
     *
     * @return void
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     */
    public function modifyPeriodsWithSingleLocationBeforePersist($dayList)
    {
        /** @var WeekDay|SpecialDay $day */
        foreach ($dayList->getItems() as $day) {
            /** @var Period|SpecialDayPeriod $period */
            foreach ($day->getPeriodList()->getItems() as $period) {
                if ($period->getPeriodLocationList()->length() === 1) {
                    /** @var PeriodLocation|SpecialDayPeriodLocation $periodLocation */
                    $periodLocation = $period->getPeriodLocationList()->getItem(0);

                    $period->setLocationId(new Id($periodLocation->getLocationId()->getValue()));

                    $period->getPeriodLocationList()->deleteItem(0);
                } elseif ($period->getPeriodLocationList()->length() === 0) {
                    $period->setLocationId(new Id(0));
                } else {
                    /** @var PeriodLocation|SpecialDayPeriodLocation $periodLocation */
                    $periodLocation = $period->getPeriodLocationList()->getItem(0);

                    $period->setLocationId(new Id($periodLocation->getLocationId()->getValue()));
                }
            }
        }
    }

    /**
     * Modify period after fetch if there is no locations in period and there is period location
     *
     * @param Collection $dayList
     *
     * @return void
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     */
    public function modifyPeriodsWithSingleLocationAfterFetch($dayList)
    {
        /** @var WeekDay|SpecialDay $day */
        foreach ($dayList->getItems() as $day) {
            /** @var Period|SpecialDayPeriod $period */
            foreach ($day->getPeriodList()->getItems() as $period) {
                if ($period->getPeriodLocationList()->length() === 0 && $period->getLocationId()) {
                    if ($period instanceof Period) {
                        $period->getPeriodLocationList()->addItem(
                            PeriodLocationFactory::create(
                                [
                                    'locationId' => $period->getLocationId()->getValue(),
                                ]
                            )
                        );
                    } elseif ($period instanceof SpecialDayPeriod) {
                        $period->getPeriodLocationList()->addItem(
                            SpecialDayPeriodLocationFactory::create(
                                [
                                    'locationId' => $period->getLocationId()->getValue(),
                                ]
                            )
                        );
                    }

                    $period->setLocationId(new Id(0));
                }
            }
        }
    }

    /**
     * Update provider week day / time out
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function updateProviderWorkDays($oldUser, $newUser)
    {
        /** @var WeekDayRepository $weekDayRepository */
        $weekDayRepository = $this->container->get('domain.schedule.weekDay.repository');

        /** @var TimeOutRepository $timeOutRepository */
        $timeOutRepository = $this->container->get('domain.schedule.timeOut.repository');

        /** @var PeriodRepository $periodRepository */
        $periodRepository = $this->container->get('domain.schedule.period.repository');

        /** @var PeriodServiceRepository $periodServiceRepository */
        $periodServiceRepository = $this->container->get('domain.schedule.period.service.repository');

        /** @var PeriodLocationRepository $periodLocationRepository */
        $periodLocationRepository = $this->container->get('domain.schedule.period.location.repository');

        $this->modifyPeriodsWithSingleLocationBeforePersist($newUser->getWeekDayList());

        $existingWeekDayIds = [];

        $existingTimeOutIds = [];

        $existingPeriodIds = [];

        $existingPeriodServicesIds = [];

        $existingPeriodLocationsIds = [];

        foreach ((array)$newUser->getWeekDayList()->keys() as $newUserWeekDayKey) {
            // add day work hours
            /** @var WeekDay $newWeekDay */
            $newWeekDay = $newUser->getWeekDayList()->getItem($newUserWeekDayKey);

            // update week day if ID exist
            if ($newWeekDay->getId() && $newWeekDay->getId()->getValue()) {
                $weekDayRepository->update($newWeekDay, $newWeekDay->getId()->getValue());
            }

            // add week day off if ID does not exist
            if (!$newWeekDay->getId()) {
                $newWeekDayId = $weekDayRepository->add($newWeekDay, $newUser->getId()->getValue());

                $newWeekDay->setId(new Id($newWeekDayId));
            }

            $existingWeekDayIds[$newWeekDay->getId()->getValue()] = true;

            $existingTimeOutIds[$newWeekDay->getId()->getValue()] = [];

            $existingPeriodIds[$newWeekDay->getId()->getValue()] = [];

            $existingPeriodServicesIds[$newWeekDay->getId()->getValue()] = [];

            $existingPeriodLocationsIds[$newWeekDay->getId()->getValue()] = [];

            // add day time out values
            foreach ((array)$newWeekDay->getTimeOutList()->keys() as $newTimeOutKey) {
                /** @var TimeOut $newTimeOut */
                if (!($newTimeOut = $newWeekDay->getTimeOutList()->getItem($newTimeOutKey)) instanceof TimeOut) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // update week day time out if ID exist
                if ($newTimeOut->getId() && $newTimeOut->getId()->getValue()) {
                    $timeOutRepository->update($newTimeOut, $newTimeOut->getId()->getValue());
                }

                // add week day time out if ID does not exist
                if (!$newTimeOut->getId()) {
                    $newTimeOutId = $timeOutRepository->add($newTimeOut, $newWeekDay->getId()->getValue());

                    $newTimeOut->setId(new Id($newTimeOutId));
                }

                $existingTimeOutIds[$newWeekDay->getId()->getValue()][$newTimeOut->getId()->getValue()] = true;
            }

            // add day period values
            foreach ((array)$newWeekDay->getPeriodList()->keys() as $newPeriodKey) {
                /** @var Period $newPeriod */
                if (!($newPeriod = $newWeekDay->getPeriodList()->getItem($newPeriodKey)) instanceof Period) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // update week day period if ID exist
                if ($newPeriod->getId() && $newPeriod->getId()->getValue()) {
                    $periodRepository->update($newPeriod, $newPeriod->getId()->getValue());

                    $existingPeriodServicesIds[$newWeekDay->getId()->getValue()][$newPeriod->getId()->getValue()] = [];

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var PeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        if (!$periodService->getId()) {
                            $periodServiceId = $periodServiceRepository->add(
                                $periodService,
                                $newPeriod->getId()->getValue()
                            );

                            $periodService->setId(new Id($periodServiceId));
                        }

                        $existingPeriodServicesIds
                            [$newWeekDay->getId()->getValue()]
                            [$newPeriod->getId()->getValue()]
                            [$periodService->getId()->getValue()] = true;
                    }

                    $existingPeriodLocationsIds[$newWeekDay->getId()->getValue()][$newPeriod->getId()->getValue()] = [];

                    foreach ((array)$newPeriod->getPeriodLocationList()->keys() as $periodLocationKey) {
                        /** @var PeriodLocation $periodLocation */
                        $periodLocation = $newPeriod->getPeriodLocationList()->getItem($periodLocationKey);

                        if (!$periodLocation->getId()) {
                            $periodLocationId = $periodLocationRepository->add(
                                $periodLocation,
                                $newPeriod->getId()->getValue()
                            );

                            $periodLocation->setId(new Id($periodLocationId));
                        }

                        $existingPeriodLocationsIds
                            [$newWeekDay->getId()->getValue()]
                            [$newPeriod->getId()->getValue()]
                            [$periodLocation->getId()->getValue()] = true;
                    }
                }

                // add week day period if ID does not exist
                if (!$newPeriod->getId()) {
                    $newPeriodId = $periodRepository->add($newPeriod, $newWeekDay->getId()->getValue());

                    $newPeriod->setId(new Id($newPeriodId));

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var PeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        $periodServiceRepository->add($periodService, $newPeriodId);
                    }

                    foreach ((array)$newPeriod->getPeriodLocationList()->keys() as $periodLocationKey) {
                        /** @var PeriodLocation $periodLocation */
                        $periodLocation = $newPeriod->getPeriodLocationList()->getItem($periodLocationKey);

                        $periodLocationRepository->add($periodLocation, $newPeriodId);
                    }
                }

                $existingPeriodIds[$newWeekDay->getId()->getValue()][$newPeriod->getId()->getValue()] = true;
            }
        }

        // delete week day time out and period if not exist in new week day time out list and period list
        foreach ((array)$oldUser->getWeekDayList()->keys() as $oldUserKey) {
            /** @var WeekDay $oldWeekDay */
            if (!($oldWeekDay = $oldUser->getWeekDayList()->getItem($oldUserKey)) instanceof WeekDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $oldWeekDayId = $oldWeekDay->getId()->getValue();

            foreach ((array)$oldWeekDay->getTimeOutList()->keys() as $oldTimeOutKey) {
                if (!($oldTimeOut = $oldWeekDay->getTimeOutList()->getItem($oldTimeOutKey)) instanceof TimeOut) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $oldTimeOutId = $oldTimeOut->getId()->getValue();

                if (!isset($existingTimeOutIds[$oldWeekDayId][$oldTimeOutId])) {
                    $timeOutRepository->delete($oldTimeOutId);
                }
            }

            foreach ((array)$oldWeekDay->getPeriodList()->keys() as $oldPeriodKey) {
                if (!($oldPeriod = $oldWeekDay->getPeriodList()->getItem($oldPeriodKey)) instanceof Period) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $oldPeriodId = $oldPeriod->getId()->getValue();

                foreach ((array)$oldPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                    $oldPeriodServiceId = $oldPeriod->getPeriodServiceList()
                        ->getItem($periodServiceKey)->getId()->getValue();

                    if (!isset($existingPeriodServicesIds[$oldWeekDayId][$oldPeriodId][$oldPeriodServiceId])) {
                        $periodServiceRepository->delete($oldPeriodServiceId);
                    }
                }

                foreach ((array)$oldPeriod->getPeriodLocationList()->keys() as $periodLocationKey) {
                    $oldPeriodLocationId = $oldPeriod->getPeriodLocationList()
                        ->getItem($periodLocationKey)->getId()->getValue();

                    if (!isset($existingPeriodLocationsIds[$oldWeekDayId][$oldPeriodId][$oldPeriodLocationId])) {
                        $periodLocationRepository->delete($oldPeriodLocationId);
                    }
                }

                if (!isset($existingPeriodIds[$oldWeekDayId][$oldPeriodId])) {
                    $periodRepository->delete($oldPeriodId);
                }
            }

            if (!isset($existingWeekDayIds[$oldWeekDayId])) {
                $weekDayRepository->delete($oldWeekDayId);
            }
        }

        return true;
    }

    /**
     * Update provider special day
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function updateProviderSpecialDays($oldUser, $newUser)
    {
        /** @var SpecialDayRepository $specialDayRepository */
        $specialDayRepository = $this->container->get('domain.schedule.specialDay.repository');

        /** @var SpecialDayPeriodRepository $specialDayPeriodRepository */
        $specialDayPeriodRepository = $this->container->get('domain.schedule.specialDay.period.repository');

        /** @var SpecialDayPeriodServiceRepository $specialDayPeriodServiceRepository */
        $specialDayPeriodServiceRepository = $this->container->get('domain.schedule.specialDay.period.service.repository');

        /** @var SpecialDayPeriodLocationRepository $specialDayPeriodLocationRepository */
        $specialDayPeriodLocationRepository = $this->container->get('domain.schedule.specialDay.period.location.repository');

        $this->modifyPeriodsWithSingleLocationBeforePersist($newUser->getSpecialDayList());

        $existingSpecialDayIds = [];

        $existingSpecialDayPeriodIds = [];

        $existingSpecialDayPeriodServicesIds = [];

        $existingSpecialDayPeriodLocationsIds = [];

        foreach ((array)$newUser->getSpecialDayList()->keys() as $newUserSpecialDayKey) {
            // add special day work hours
            /** @var SpecialDay $newSpecialDay */
            $newSpecialDay = $newUser->getSpecialDayList()->getItem($newUserSpecialDayKey);

            // update special day if ID exist
            if ($newSpecialDay->getId() && $newSpecialDay->getId()->getValue()) {
                $specialDayRepository->update($newSpecialDay, $newSpecialDay->getId()->getValue());
            }

            // add special day if ID does not exist
            if (!$newSpecialDay->getId()) {
                $newSpecialDayId = $specialDayRepository->add($newSpecialDay, $newUser->getId()->getValue());

                $newSpecialDay->setId(new Id($newSpecialDayId));
            }

            $existingSpecialDayIds[$newSpecialDay->getId()->getValue()] = true;

            $existingSpecialDayPeriodIds[$newSpecialDay->getId()->getValue()] = [];

            $existingSpecialDayPeriodServicesIds[$newSpecialDay->getId()->getValue()] = [];

            $existingSpecialDayPeriodLocationsIds[$newSpecialDay->getId()->getValue()] = [];

            // add day period values
            foreach ((array)$newSpecialDay->getPeriodList()->keys() as $newPeriodKey) {
                /** @var SpecialDayPeriod $newPeriod */
                if (!($newPeriod = $newSpecialDay->getPeriodList()->getItem($newPeriodKey)) instanceof SpecialDayPeriod) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // update special day period if ID exist
                if ($newPeriod->getId() && $newPeriod->getId()->getValue()) {
                    $specialDayPeriodRepository->update($newPeriod, $newPeriod->getId()->getValue());

                    $existingSpecialDayPeriodServicesIds
                    [$newSpecialDay->getId()->getValue()]
                    [$newPeriod->getId()->getValue()] = [];

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var SpecialDayPeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        if (!$periodService->getId()) {
                            $periodServiceId = $specialDayPeriodServiceRepository->add(
                                $periodService,
                                $newPeriod->getId()->getValue()
                            );

                            $periodService->setId(new Id($periodServiceId));
                        }

                        $existingSpecialDayPeriodServicesIds
                        [$newSpecialDay->getId()->getValue()]
                        [$newPeriod->getId()->getValue()][$periodService->getId()->getValue()] = true;
                    }

                    $existingSpecialDayPeriodLocationsIds
                    [$newSpecialDay->getId()->getValue()]
                    [$newPeriod->getId()->getValue()] = [];

                    foreach ((array)$newPeriod->getPeriodLocationList()->keys() as $periodLocationKey) {
                        /** @var SpecialDayPeriodLocation $periodLocation */
                        $periodLocation = $newPeriod->getPeriodLocationList()->getItem($periodLocationKey);

                        if (!$periodLocation->getId()) {
                            $periodLocationId = $specialDayPeriodLocationRepository->add(
                                $periodLocation,
                                $newPeriod->getId()->getValue()
                            );

                            $periodLocation->setId(new Id($periodLocationId));
                        }

                        $existingSpecialDayPeriodLocationsIds
                        [$newSpecialDay->getId()->getValue()]
                        [$newPeriod->getId()->getValue()][$periodLocation->getId()->getValue()] = true;
                    }
                }

                // add special day period if ID does not exist
                if (!$newPeriod->getId()) {
                    $newPeriodId = $specialDayPeriodRepository->add($newPeriod, $newSpecialDay->getId()->getValue());

                    $newPeriod->setId(new Id($newPeriodId));

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var SpecialDayPeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        $specialDayPeriodServiceRepository->add($periodService, $newPeriodId);
                    }

                    foreach ((array)$newPeriod->getPeriodLocationList()->keys() as $periodLocationKey) {
                        /** @var SpecialDayPeriodLocation $periodLocation */
                        $periodLocation = $newPeriod->getPeriodLocationList()->getItem($periodLocationKey);

                        $specialDayPeriodLocationRepository->add($periodLocation, $newPeriodId);
                    }
                }

                $existingSpecialDayPeriodIds[$newSpecialDay->getId()->getValue()][$newPeriod->getId()->getValue()] = true;
            }
        }

        // delete week day time out and period if not exist in new week day time out list and period list
        foreach ((array)$oldUser->getSpecialDayList()->keys() as $oldUserKey) {
            /** @var SpecialDay $oldSpecialDay */
            if (!($oldSpecialDay = $oldUser->getSpecialDayList()->getItem($oldUserKey)) instanceof SpecialDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $oldSpecialDayId = $oldSpecialDay->getId()->getValue();

            foreach ((array)$oldSpecialDay->getPeriodList()->keys() as $oldPeriodKey) {
                if (!($oldPeriod = $oldSpecialDay->getPeriodList()->getItem($oldPeriodKey)) instanceof SpecialDayPeriod) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $oldPeriodId = $oldPeriod->getId()->getValue();

                foreach ((array)$oldPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                    $oldPeriodServiceId = $oldPeriod->getPeriodServiceList()
                        ->getItem($periodServiceKey)->getId()->getValue();

                    if (!isset($existingSpecialDayPeriodServicesIds[$oldSpecialDayId][$oldPeriodId][$oldPeriodServiceId])) {
                        $specialDayPeriodServiceRepository->delete($oldPeriodServiceId);
                    }
                }

                foreach ((array)$oldPeriod->getPeriodLocationList()->keys() as $periodLocationKey) {
                    $oldPeriodLocationId = $oldPeriod->getPeriodLocationList()
                        ->getItem($periodLocationKey)->getId()->getValue();

                    if (!isset($existingSpecialDayPeriodLocationsIds[$oldSpecialDayId][$oldPeriodId][$oldPeriodLocationId])) {
                        $specialDayPeriodLocationRepository->delete($oldPeriodLocationId);
                    }
                }

                if (!isset($existingSpecialDayPeriodIds[$oldSpecialDayId][$oldPeriodId])) {
                    $specialDayPeriodRepository->delete($oldPeriodId);
                }
            }

            if (!isset($existingSpecialDayIds[$oldSpecialDayId])) {
                $specialDayRepository->delete($oldSpecialDayId);
            }
        }

        return true;
    }


    /**
     * @param array $providers
     * @param bool  $companyDayOff
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function manageProvidersActivity($providers, $companyDayOff)
    {

        if ($companyDayOff === false) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');
            /** @var AppointmentRepository $appointmentRepo */
            $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

            $providerTimeZones  = [];
            $availableProviders = [];

            $WPtimeZone = DateTimeService::getTimeZone()->getName();

            foreach ($providers as $provider) {
                $providerTimeZones[] = isset($provider['timeZone']) ? $provider['timeZone'] : $WPtimeZone;
            }

            foreach (array_unique($providerTimeZones) as $providerTimeZone) {
                $availableProviders += $providerRepository->getAvailable((int)date('w'), $providerTimeZone);
            }

            $onBreakProviders    = $providerRepository->getOnBreak((int)date('w'));
            $onVacationProviders = $providerRepository->getOnVacation();
            $busyProviders       = $appointmentRepo->getCurrentAppointments();
            $specialDayProviders = $providerRepository->getOnSpecialDay();

            foreach ($providers as &$provider) {
                if (array_key_exists($provider['id'], $availableProviders)) {
                    $provider['activity'] = 'available';
                } else {
                    $provider['activity'] = 'away';
                }

                if (array_key_exists($provider['id'], $onBreakProviders)) {
                    $provider['activity'] = 'break';
                }

                if (array_key_exists($provider['id'], $specialDayProviders)) {
                    $provider['activity'] = $specialDayProviders[$provider['id']]['available'] ? 'available' : 'away';
                }

                if (array_key_exists($provider['id'], $busyProviders)) {
                    $provider['activity'] = 'busy';
                }

                if (array_key_exists($provider['id'], $onVacationProviders)) {
                    $provider['activity'] = 'dayoff';
                }
            }
        } else {
            foreach ($providers as &$provider) {
                $provider['activity'] = 'dayoff';
            }
        }

        return $providers;
    }

    /**
     * @param $companyDaysOff
     *
     * @return bool
     */
    public function checkIfTodayIsCompanyDayOff($companyDaysOff)
    {
        $currentDate = DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0);

        $dayOff = false;
        foreach ((array)$companyDaysOff as $companyDayOff) {
            if (
                $currentDate >= DateTimeService::getCustomDateTimeObject($companyDayOff['startDate']) &&
                $currentDate <= DateTimeService::getCustomDateTimeObject($companyDayOff['endDate'])
            ) {
                $dayOff = true;
                break;
            }
        }

        return $dayOff;
    }

    /**
     * @param array        $providers
     * @param AbstractUser $currentUser
     *
     * @return array
     * @throws ContainerException
     */
    public function removeAllExceptUser($providers, $currentUser)
    {
        if (
            $currentUser !== null &&
            $currentUser->getType() === AbstractUser::USER_ROLE_PROVIDER &&
            !$this->container->getPermissionsService()->currentUserCanReadOthers(Entities::APPOINTMENTS)
        ) {
            if ($currentUser->getId() === null) {
                return [];
            }

            $currentUserId = $currentUser->getId()->getValue();
            foreach ($providers as $key => $provider) {
                if ($provider['id'] !== $currentUserId) {
                    unset($providers[$key]);
                }
            }
        }

        return array_values($providers);
    }

    /**
     * @param Provider $newUser
     *
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function updateProviderGoogleCalendar($newUser)
    {
        /** @var GoogleCalendarRepository $googleCalendarRepository */
        $googleCalendarRepository = $this->container->get('domain.google.calendar.repository');

        $googleCalendarRepository->update(
            $newUser->getGoogleCalendar(),
            $newUser->getGoogleCalendar()->getId()->getValue()
        );
    }

    /**
     * @param Provider $newUser
     *
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function updateProviderOutlookCalendar($newUser)
    {
        /** @var OutlookCalendarRepository $outlookCalendarRepository */
        $outlookCalendarRepository = $this->container->get('domain.outlook.calendar.repository');

        $outlookCalendarRepository->update(
            $newUser->getOutlookCalendar(),
            $newUser->getOutlookCalendar()->getId()->getValue()
        );
    }

    /**
     * Update provider locations
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    private function updateProviderLocations($oldUser, $newUser)
    {
        /** @var ProviderLocationRepository $providerLocationRepo */
        $providerLocationRepo = $this->container->get('domain.bookable.service.providerLocation.repository');

        if ($oldUser->getLocationId() && $newUser->getLocationId()) {
            $providerLocation = ProviderLocationFactory::create(
                [
                'userId'     => $newUser->getId()->getValue(),
                'locationId' => $newUser->getLocationId()->getValue()
                ]
            );

            $providerLocationRepo->update($providerLocation);
        } elseif ($newUser->getLocationId()) {
            $providerLocation = ProviderLocationFactory::create(
                [
                'userId'     => $newUser->getId()->getValue(),
                'locationId' => $newUser->getLocationId()->getValue()
                ]
            );

            $providerLocationRepo->add($providerLocation);
        } elseif ($oldUser->getLocationId()) {
            $providerLocationRepo->delete($oldUser->getId()->getValue());
        }
    }

    /**
     * Update provider services
     *
     * @param Provider $newUser
     *
     * @return void
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    private function updateProviderServices($newUser)
    {
        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');

        $servicesIds = [];

        /** @var Collection $services */
        $services = $newUser->getServiceList();

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $servicesIds[] = $service->getId()->getValue();
        }

        $providerServiceRepo->deleteAllNotInServicesArrayForProvider($servicesIds, $newUser->getId()->getValue());

        $existingServices = $providerServiceRepo->getAllForEntity($newUser->getId()->getValue(), Entities::EMPLOYEE);

        $existingServicesIds = [];

        foreach ($existingServices as $existingService) {
            $existingServicesIds[] = $existingService['serviceId'];
        }

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            if (!in_array($service->getId()->getValue(), $existingServicesIds, false)) {
                $providerServiceRepo->add($service, $newUser->getId()->getValue());
            } else {
                foreach ($existingServices as $providerService) {
                    if ($providerService['serviceId'] === $service->getId()->getValue()) {
                        $providerServiceRepo->update($service, $providerService['id']);
                        break;
                    }
                }
            }
        }

        $providerServiceRepo->deleteDuplicated(
            $newUser->getId()->getValue(),
            Entities::EMPLOYEE
        );
    }

    /**
     * Update provider days off
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    private function updateProviderDaysOff($oldUser, $newUser)
    {
        /** @var DayOffRepository $dayOffRepository */
        $dayOffRepository = $this->container->get('domain.schedule.dayOff.repository');

        $existingDayOffIds = [];

        foreach ((array)$newUser->getDayOffList()->keys() as $newUserKey) {
            $newDayOff = $newUser->getDayOffList()->getItem($newUserKey);

            // update day off if ID exist
            if ($newDayOff->getId() && $newDayOff->getId()->getValue()) {
                $dayOffRepository->update($newDayOff, $newDayOff->getId()->getValue());
            }

            // add new day off if ID does not exist
            if ($newDayOff->getId() === null || $newDayOff->getId()->getValue() === 0) {
                $newDayOffId = $dayOffRepository->add($newDayOff, $newUser->getId()->getValue());

                $newDayOff->setId(new Id($newDayOffId));
            }

            $existingDayOffIds[] = $newDayOff->getId()->getValue();
        }

        // delete day off if not exist in new day off list
        foreach ((array)$oldUser->getDayOffList()->keys() as $oldUserKey) {
            $oldDayOff = $oldUser->getDayOffList()->getItem($oldUserKey);

            if (!in_array($oldDayOff->getId()->getValue(), $existingDayOffIds, true)) {
                $dayOffRepository->delete($oldDayOff->getId()->getValue());
            }
        }

        return true;
    }

    /**
     * get day free intervals in seconds
     *
     * @param Collection $periodList
     * @param Collection $timeOutList
     *
     * @return array
     */
    public function getProviderScheduleIntervals($periodList, $timeOutList)
    {
        /** @var IntervalService $intervalService */
        $intervalService = $this->container->get('domain.interval.service');

        $availableIntervals = [];

        $unavailableIntervals = [];

        /** @var TimeOut $timeOut */
        foreach ($timeOutList->getItems() as $timeOut) {
            $startTimeOut = $intervalService->getSeconds(
                $timeOut->getStartTime()->getValue()->format('H:i:s')
            );

            $endTimeOut = $intervalService->getSeconds(
                $timeOut->getEndTime()->getValue()->format('H:i:s') === '00:00:00' ? '24:00:00' :
                    $timeOut->getEndTime()->getValue()->format('H:i:s')
            );

            $unavailableIntervals[$startTimeOut] = [
                $startTimeOut,
                $endTimeOut
            ];
        }

        /** @var Period $period */
        foreach ($periodList->getItems() as $period) {
            $startPeriod = $intervalService->getSeconds(
                $period->getStartTime()->getValue()->format('H:i:s')
            );

            $endPeriod = $intervalService->getSeconds(
                $period->getEndTime()->getValue()->format('H:i:s') === '00:00:00' ? '24:00:00' :
                    $period->getEndTime()->getValue()->format('H:i:s')
            );

            $periodServices = [];

            /** @var PeriodService $periodService */
            foreach ($period->getPeriodServiceList()->getItems() as $periodService) {
                $periodServices[] = $periodService->getServiceId()->getValue();
            }

            $periodIntervals = $intervalService->getFreeIntervals(
                $unavailableIntervals,
                $startPeriod,
                $endPeriod
            );

            foreach ($periodIntervals as $interval) {
                $availableIntervals[] = [
                    'time'     => [
                        $interval[0],
                        $interval[1]
                    ],
                    'services' => $periodServices
                ];
            }
        }

        return $availableIntervals;
    }

    /**
     * get provider by ID
     *
     * @param int  $providerId
     * @param bool $fetchCalendars
     *
     * @return Provider
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getProviderWithServicesAndSchedule($providerId, $fetchCalendars = false)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var ProviderService $providerService */
        $providerService = $this->container->get('domain.user.provider.service');

        /** @var Collection $services */
        $services = $serviceRepository->getAllArrayIndexedById();

        /** @var Collection $providers */
        $providers = $providerRepository->getWithSchedule(
            [
                'providers'      => [$providerId],
                'fetchCalendars' => $fetchCalendars,
            ]
        );

        /** @var Provider $provider */
        $provider = $providers->getItem($providerId);

        $providerService->setProviderServices($provider, $services, true);

        return $provider;
    }

    /**
     * @param array      $data
     * @param Provider   $provider
     * @param Collection $periodList
     * @param Collection $locations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function setAvailablePeriodServicesLocations(&$data, $provider, $periodList, $locations)
    {
        /** @var LocationService $locationService */
        $locationService = $this->container->get('domain.location.service');

        /** @var ProviderService $providerService */
        $providerService = $this->container->get('domain.user.provider.service');

        $hasVisibleLocations = $locationService->hasVisibleLocations($locations);

        /** @var Location $providerLocation */
        $providerLocation = $provider->getLocationId() && $locations->length() ?
            $locations->getItem($provider->getLocationId()->getValue()) : null;

        /** @var Period|SpecialDayPeriod $period */
        foreach ($periodList->getItems() as $period) {
            /** @var Collection $availablePeriodLocations */
            $availablePeriodLocations = $providerService->getProviderPeriodLocations(
                $period,
                $providerLocation,
                $locations,
                $hasVisibleLocations
            );

            if ($providerLocation && $availablePeriodLocations->length() === 0) {
                $availablePeriodLocations->addItem($providerLocation, $providerLocation->getId()->getValue());
            }

            if ($hasVisibleLocations && !$availablePeriodLocations->length()) {
                continue;
            }

            if ($period->getPeriodServiceList()->length()) {
                /** @var PeriodService $periodService */
                foreach ($period->getPeriodServiceList()->getItems() as $periodService) {
                    if ($availablePeriodLocations->length()) {
                        /** @var Location $availableLocation */
                        foreach ($availablePeriodLocations->getItems() as $availableLocation) {
                            $data[$periodService->getServiceId()->getValue()][] = $availableLocation->getId()->getValue();
                        }
                    } else {
                        $data[$periodService->getServiceId()->getValue()][] = null;
                    }
                }
            } else {
                /** @var Service $service */
                foreach ($provider->getServiceList()->getItems() as $service) {
                    if ($availablePeriodLocations->length()) {
                        /** @var Location $availableLocation */
                        foreach ($availablePeriodLocations->getItems() as $availableLocation) {
                            $data[$service->getId()->getValue()][] = $availableLocation->getId()->getValue();
                        }
                    } else {
                        $data[$service->getId()->getValue()][] = null;
                    }
                }
            }
        }
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Provider   $provider
     * @param Collection $locations
     * @param Collection $services
     * @param bool       $ignoreTime
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getProviderServiceLocations($provider, $locations, $services, $ignoreTime = false)
    {
        $data = [];

        if ($provider->getStatus()->getValue() === Status::HIDDEN) {
            return [];
        }

        $providerLocationId = null;

        if ($provider->getLocationId() && $locations->length()) {
            /** @var Location $providerLocation */
            $providerLocation = $locations->getItem($provider->getLocationId()->getValue());

            $providerLocationId = $providerLocation->getId()->getValue();
        }

        /** @var WeekDay $weekDay */
        foreach ($provider->getWeekDayList()->getItems() as $weekDay) {
            $this->setAvailablePeriodServicesLocations(
                $data,
                $provider,
                $weekDay->getPeriodList(),
                $locations
            );

            if ($weekDay->getPeriodList()->length() === 0) {
                /** @var Service $providerService */
                foreach ($provider->getServiceList()->getItems() as $providerService) {
                    $data[$providerService->getId()->getValue()][] = $providerLocationId;
                }
            }
        }

        $currentDate = DateTimeService::getNowDateTimeObject();

        /** @var SpecialDay $specialDay */
        foreach ($provider->getSpecialDayList()->getItems() as $specialDay) {
            $specialDayCopy = clone $specialDay->getEndDate()->getValue();

            $specialDayCopy->modify('+1 days');

            if ($specialDayCopy < $currentDate && !$ignoreTime) {
                continue;
            }

            $this->setAvailablePeriodServicesLocations(
                $data,
                $provider,
                $specialDay->getPeriodList(),
                $locations
            );
        }

        $result = [];

        foreach ($data as $serviceId => $serviceLocations) {
            /** @var Service $service */
            if (
                $services->keyExists($serviceId) &&
                ($service = $services->getItem($serviceId)) &&
                $service->getStatus()->getValue() === Status::VISIBLE
            ) {
                $result[$serviceId] = array_values(array_unique($serviceLocations));
            }
        }

        return $result;
    }

    /**
     * @param AbstractUser|Provider $provider
     *
     * @return boolean
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function delete($provider)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var ProviderServiceRepository $providerServiceRepository */
        $providerServiceRepository = $this->container->get('domain.bookable.service.providerService.repository');

        /** @var AppointmentApplicationService $appointmentApplicationService */
        $appointmentApplicationService = $this->container->get('application.booking.appointment.service');

        /** @var GoogleCalendarRepository $googleCalendarRepository */
        $googleCalendarRepository = $this->container->get('domain.google.calendar.repository');

        /** @var OutlookCalendarRepository $outlookCalendarRepository */
        $outlookCalendarRepository = $this->container->get('domain.outlook.calendar.repository');

        /** @var ProviderLocationRepository $providerLocationRepository */
        $providerLocationRepository = $this->container->get('domain.bookable.service.providerLocation.repository');

        /** @var PackageServiceProviderRepository $packageServiceProviderRepository */
        $packageServiceProviderRepository = $this->container->get('domain.bookable.package.packageServiceProvider.repository');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var EventProvidersRepository $eventProvidersRepository */
        $eventProvidersRepository = $this->container->get('domain.booking.event.provider.repository');

        /** @var ResourceEntitiesRepository $resourceEntitiesRepository */
        $resourceEntitiesRepository = $this->container->get('domain.bookable.resourceEntities.repository');

        /** @var CustomerApplicationService $customerApplicationService */
        $customerApplicationService = $this->container->get('application.user.customer.service');

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered(
            [
                'providers' => [$provider->getId()->getValue()]
            ]
        );

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            if (!$appointmentApplicationService->delete($appointment)) {
                return false;
            }
        }

        /** @var Provider $newProvider */
        $newProvider = UserFactory::create(
            array_merge(
                $provider->toArray(),
                [
                    'weekDayList'    => [],
                    'specialDayList' => [],
                    'dayOffList'     => []
                ]
            )
        );

        return
            $this->updateProviderDaysOff($provider, $newProvider) &&
            $this->updateProviderSpecialDays($provider, $newProvider) &&
            $this->updateProviderWorkDays($provider, $newProvider) &&
            $providerServiceRepository->deleteByEntityId($provider->getId()->getValue(), 'userId') &&
            $providerLocationRepository->deleteByEntityId($provider->getId()->getValue(), 'userId') &&
            $googleCalendarRepository->deleteByEntityId($provider->getId()->getValue(), 'userId') &&
            $outlookCalendarRepository->deleteByEntityId($provider->getId()->getValue(), 'userId') &&
            $eventProvidersRepository->deleteByEntityId($provider->getId()->getValue(), 'userId') &&
            $eventRepository->updateByEntityId($provider->getId()->getValue(), null, 'organizerId') &&
            $packageServiceProviderRepository->deleteByEntityId($provider->getId()->getValue(), 'userId') &&
            $packageCustomerServiceRepository->updateByEntityId($provider->getId()->getValue(), null, 'providerId') &&
            $providerRepository->deleteViewStats($provider->getId()->getValue()) &&
            $resourceEntitiesRepository->deleteByEntityIdAndEntityType($provider->getId()->getValue(), 'employee') &&
            $customerApplicationService->delete($provider);
    }

    /**
     * @param AbstractUser $currentUser
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllowedCustomers($currentUser)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var Collection $customers */
        $customers = $userRepository->getAllWithAllowedBooking();

        // user_can added here, because currentUser is null in logged.in.user service for cabinet
        if (
            !$this->container->getPermissionsService()->currentUserCanReadOthers(Entities::CUSTOMERS) &&
            !(
                $currentUser !== null && $currentUser->getExternalId() !== null &&
                user_can($currentUser->getExternalId()->getValue(), 'amelia_read_others_customers')
            )
        ) {
            /** @var AppointmentRepository $appointmentRepository */
            $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

            /** @var Collection $appointments */
            $appointments = $appointmentRepository->getFiltered(
                ['providerId' => $currentUser->getId()->getValue()]
            );

            /** @var Collection $customersWithoutBooking */
            $customersWithoutBooking = $userRepository->getAllWithoutBookings();

            /** @var Appointment $appointment */
            foreach ($appointments->getItems() as $appointment) {
                /** @var CustomerBooking $booking */
                foreach ($appointment->getBookings()->getItems() as $booking) {
                    if (!$customersWithoutBooking->keyExists($booking->getCustomerId()->getValue())) {
                        $customersWithoutBooking->addItem(
                            $customers->getItem($booking->getCustomerId()->getValue()),
                            $booking->getCustomerId()->getValue()
                        );
                    }
                }
            }

            $customersWithoutBookingArray = $customersWithoutBooking->getItems();

            usort(
                $customersWithoutBookingArray,
                function ($a, $b) {
                    return strcmp(
                        $a->getFirstName()->getValue() . ' ' . $a->getLastName()->getValue(),
                        $b->getFirstName()->getValue() . ' ' . $b->getLastName()->getValue()
                    );
                }
            );


            return new Collection($customersWithoutBookingArray);
        }

        return $customers;
    }

    /**
     * @param int $providerId
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function getMandatoryServicesIds($providerId)
    {
        /** @var ProviderServiceRepository $providerServiceRepository */
        $providerServiceRepository = $this->container->get('domain.bookable.service.providerService.repository');

        return $providerServiceRepository->getMandatoryServicesIdsForProvider($providerId);
    }
}
