<?php

namespace AmeliaBooking\Application\Services\User;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Booking\AbstractBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventPeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class CustomerApplicationService
 *
 * @package AmeliaBooking\Application\Services\User
 */
class CustomerApplicationService extends UserApplicationService
{
    private $container;

    /**
     * CustomerApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->container = $container;
    }

    /**
     * @param array $fields
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function createCustomer($fields)
    {
        $result = new CommandResult();

        $user = UserFactory::create($fields);

        if (!$user instanceof AbstractUser) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create a new user entity.');

            return $result;
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');


        if ($oldUser = $userRepository->getByEmail($user->getEmail()->getValue())) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Email already exist.');
            $result->setData('This email is already in use.');

            return $result;
        }

        $userRepository->beginTransaction();

        if ($userId = $userRepository->add($user)) {
            $user->setId(new Id($userId));

            if ($fields['externalId'] === 0) {
                /** @var UserApplicationService $userAS */
                $userAS = $this->container->get('application.user.service');

                $userAS->setWpUserIdForNewUser($userId, $user);
            }

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully added new user.');
            $result->setData(
                [
                    Entities::USER => $user->toArray()
                ]
            );

            $userRepository->commit();

            return $result;
        }

        $userRepository->rollback();

        return $result;
    }

    /**
     * @param Collection   $bookings
     * @param AbstractUser $user
     *
     * @return boolean
     */
    public function hasCustomerBooking($bookings, $user)
    {
        $isCustomerBooking = false;

        /** @var CustomerBooking $booking */
        foreach ($bookings->getItems() as $booking) {
            if ($user && $user->getId()->getValue() === $booking->getCustomerId()->getValue()) {
                $isCustomerBooking = true;

                break;
            }
        }

        return $isCustomerBooking;
    }

    /**
     * Create a new user if doesn't exists. For adding appointment from the front-end.
     *
     * @param array         $userData
     * @param CommandResult $result
     * @param bool          $validateUserName
     *
     * @return Customer
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getNewOrExistingCustomer($userData, $result, $validateUserName)
    {
        /** @var AbstractUser $loggedInUser */
        $loggedInUser = $this->container->get('logged.in.user');

        if ($loggedInUser) {
            if ($loggedInUser->getType() === AbstractUser::USER_ROLE_ADMIN) {
                $userData['type'] = AbstractUser::USER_ROLE_CUSTOMER;

                $userData['externalId'] = null;
            } elseif ($loggedInUser->getType() === AbstractUser::USER_ROLE_MANAGER) {
                $userData['type'] = AbstractUser::USER_ROLE_MANAGER;
            }
        }

        /** @var Customer $user */
        $user = UserFactory::create($userData);

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        // Check if email already exists
        $userWithSameMail = $userRepository->getByEmail($user->getEmail()->getValue());

        // Check if phone already exists
        $userWithSamePhone = $user->getPhone() ? $userRepository->getByPhone($user->getPhone()->getValue()) : null;

        if ($userWithSameMail || $userWithSamePhone) {
            $userWithSameValue = $userWithSameMail ?: $userWithSamePhone;

            /** @var SettingsService $settingsService */
            $settingsService = $this->container->get('domain.settings.service');

            // If email already exists, check if First Name and Last Name from request are same with the First Name
            // and Last Name from $userWithSameMail. If these are not same return error message.
            if (
                $settingsService->getSetting('roles', 'inspectCustomerInfo') &&
                $validateUserName &&
                (strtolower(trim($userWithSameValue->getFirstName()->getValue())) !==
                    strtolower(trim($user->getFirstName()->getValue())) ||
                    strtolower(trim($userWithSameValue->getLastName()->getValue())) !==
                    strtolower(trim($user->getLastName()->getValue())))
            ) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData($userWithSameMail ? ['emailError' => true] : ['phoneError' => true]);
            }

            return $userWithSameValue;
        }

        return $user;
    }

    /**
     * @param AbstractUser $user
     * @param Collection   $reservations
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function removeBookingsForOtherCustomers($user, $reservations)
    {
        $isCustomer = $user === null || ($user && $user->getType() === AbstractUser::USER_ROLE_CUSTOMER);

        /** @var AbstractBooking $reservation */
        foreach ($reservations->getItems() as $reservation) {
            /** @var CustomerBooking $booking */
            foreach ($reservation->getBookings()->getItems() as $key => $booking) {
                if (
                    $isCustomer &&
                    (!$user || ($user && $user->getId()->getValue() !== $booking->getCustomerId()->getValue()))
                ) {
                    $reservation->getBookings()->deleteItem($key);
                }
            }
        }
    }

    /**
     * @param Customer $customer
     * @param bool     $isNewCustomer
     *
     * @return void
     * @throws ContainerException
     */
    public function setWPUserForCustomer($customer, $isNewCustomer)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $createNewUser = $settingsService->getSetting('roles', 'automaticallyCreateCustomer');

        if ($createNewUser && $isNewCustomer && $customer && $customer->getEmail()) {
            /** @var UserApplicationService $userAS */
            $userAS = $this->container->get('application.user.service');

            try {
                if ($customer->getExternalId()) {
                    $userAS->setWpUserIdForExistingUser($customer->getId()->getValue(), $customer);
                } else {
                    $userAS->setWpUserIdForNewUser($customer->getId()->getValue(), $customer);

                    do_action('AmeliaCustomerWPCreated', $customer->toArray(), $this->container);
                    do_action('amelia_customer_wp_created', $customer->toArray(), $this->container);
                }
            } catch (Exception $e) {
            }
        }

        if ($createNewUser && $customer && $customer->getExternalId()) {
            $customer->setExternalId(new Id($customer->getExternalId()->getValue()));
        }
    }

    /**
     * @param AbstractUser|Customer $customer
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function delete($customer)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerBookingEventPeriodRepository $bookingEventPeriodRepository */
        $bookingEventPeriodRepository = $this->container->get('domain.booking.customerBookingEventPeriod.repository');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        /** @var AbstractMailchimpService $mailchimpService */
        $mailchimpService = $this->container->get('infrastructure.mailchimp.service');

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered(
            [
                'customerId' => $customer->getId()->getValue()
            ]
        );

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $bookingId => $booking) {
                if ($booking->getCustomer()->getId()->getValue() !== $customer->getId()->getValue()) {
                    continue;
                }

                /** @var Collection $payments */
                $payments = $paymentRepository->getByEntityId($bookingId, 'customerBookingId');

                /** @var Payment $payment */
                foreach ($payments->getItems() as $payment) {
                    if (!$paymentAS->delete($payment)) {
                        return false;
                    }
                }

                if (
                    !$customerBookingExtraRepository->deleteByEntityId($bookingId, 'customerBookingId') ||
                    !$bookingRepository->delete($bookingId)
                ) {
                    return false;
                }
            }
        }

        /** @var Collection $packageCustomerCollection */
        $packageCustomerCollection = $packageCustomerRepository->getByEntityId(
            $customer->getId()->getValue(),
            'customerId'
        );

        /** @var PackageCustomer $packageCustomer */
        foreach ($packageCustomerCollection->getItems() as $packageCustomer) {
            /** @var Collection $payments */
            $payments = $paymentRepository->getByEntityId(
                $packageCustomer->getId()->getValue(),
                'packageCustomerId'
            );

            /** @var Payment $payment */
            foreach ($payments->getItems() as $payment) {
                if (!$paymentAS->delete($payment)) {
                    return false;
                }
            }

            if (
                !$packageCustomerServiceRepository->deleteByEntityId(
                    $packageCustomer->getId()->getValue(),
                    'packageCustomerId'
                ) || !$packageCustomerRepository->delete($packageCustomer->getId()->getValue())
            ) {
                return false;
            }
        }

        /** @var Collection $events */
        $events = $eventAS->getEventsByCriteria(
            [
                'customerId' => $customer->getId()->getValue(),
            ],
            [
                'fetchBookings' => true,
            ],
            0
        );

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            /** @var CustomerBooking $booking */
            foreach ($event->getBookings()->getItems() as $bookingId => $booking) {
                if ($booking->getCustomerId()->getValue() !== $customer->getId()->getValue()) {
                    continue;
                }

                /** @var Collection $payments */
                $payments = $paymentRepository->getByEntityId($booking->getId()->getValue(), 'customerBookingId');

                /** @var Payment $payment */
                foreach ($payments->getItems() as $payment) {
                    if (!$paymentAS->delete($payment)) {
                        return false;
                    }
                }

                if (
                    !$bookingEventPeriodRepository->deleteByEntityId($bookingId, 'customerBookingId') ||
                    !$bookingRepository->delete($bookingId)
                ) {
                    return false;
                }
            }
        }

        if ($customer->getEmail() && $customer->getEmail()->getValue()) {
            $mailchimpService->deleteSubscriber($customer->getEmail()->getValue());
        }

        return $userRepository->delete($customer->getId()->getValue());
    }
}
