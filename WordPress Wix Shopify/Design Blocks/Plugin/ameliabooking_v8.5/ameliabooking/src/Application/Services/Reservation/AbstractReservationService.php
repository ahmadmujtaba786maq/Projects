<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingsLimitReachedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\EventBookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Common\Exceptions\PackageBookingUnavailableException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Booking\AbstractCustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Factory\Tax\TaxFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\AmountType;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Domain\ValueObjects\String\DepositType;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService;
use AmeliaBooking\Infrastructure\Services\Recaptcha\AbstractRecaptchaService;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
abstract class AbstractReservationService implements ReservationServiceInterface
{
    protected $container;

    /**
     * AbstractReservationService constructor.
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
     * @param Reservation $reservation
     *
     * @return void
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    protected function deleteSingleReservation($reservation)
    {
        if ($reservation->getReservation()) {
            switch ($reservation->getReservation()->getType()->getValue()) {
                case (Entities::APPOINTMENT):
                    if ($reservation->getReservation()->getBookings()->length() === 1) {
                        /** @var AppointmentApplicationService $appointmentApplicationService */
                        $appointmentApplicationService =
                            $this->container->get('application.booking.appointment.service');

                        $appointmentApplicationService->delete($reservation->getReservation());
                    } else {
                        /** @var BookingApplicationService $bookingApplicationService */
                        $bookingApplicationService = $this->container->get('application.booking.booking.service');

                        $bookingApplicationService->delete($reservation->getBooking());
                    }

                    break;

                case (Entities::EVENT):
                    /** @var EventApplicationService $eventApplicationService */
                    $eventApplicationService = $this->container->get('application.booking.event.service');

                    $eventApplicationService->deleteEventBooking($reservation->getBooking());

                    break;
            }
        }
    }

    /**
     * @param Reservation $reservation
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function deleteReservation($reservation)
    {
        $this->deleteSingleReservation($reservation);

        if ($reservation->getRecurring()) {
            foreach ($reservation->getRecurring()->getItems() as $recurringReservation) {
                $this->deleteSingleReservation($recurringReservation);
            }
        }

        if ($reservation->getPackageReservations()) {
            foreach ($reservation->getPackageReservations()->getItems() as $packageReservation) {
                $this->deleteSingleReservation($packageReservation);
            }
        }

        if ($reservation->getPackageCustomerServices()) {
            /** @var AbstractPackageApplicationService $packageApplicationService */
            $packageApplicationService = $this->container->get('application.bookable.package');

            $packageApplicationService->deletePackageCustomer($reservation->getPackageCustomerServices());
        }
    }

    /**
     * @param int|null $newUserId
     * @throws QueryExecutionException
     */
    protected function deleteUserIfNew($newUserId)
    {
        if ($newUserId !== null) {
            /** @var CustomerRepository $customerRepository */
            $customerRepository = $this->container->get('domain.users.customers.repository');

            $customerRepository->delete($newUserId);
        }
    }

    /**
     * @param array       $data
     * @param Reservation $reservation
     * @param bool        $save
     *
     * @return CommandResult
     *
     * @throws ForbiddenFileUploadException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function processRequest($data, $reservation, $save)
    {
        $result = new CommandResult();

        $type = $data['type'] ?: Entities::APPOINTMENT;

        if (
            !empty($data['payment']['gateway']) &&
            ($data['payment']['gateway'] === 'onSite' || ($data['payment']['gateway'] === 'stripe' && empty($data['payment']['data']['paymentIntentId']))) &&
            empty($data['isBackendOrCabinet'])
        ) {
            /** @var SettingsService $settingsService */
            $settingsService = $this->container->get('domain.settings.service');

            $googleRecaptchaSettings = $settingsService->getSetting(
                'general',
                'googleRecaptcha'
            );

            if ($googleRecaptchaSettings['enabled']) {
                /** @var AbstractRecaptchaService $recaptchaService */
                $recaptchaService = $this->container->get('infrastructure.recaptcha.service');

                if (!array_key_exists('recaptcha', $data) || !$recaptchaService->verify($data['recaptcha'])) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setData(['recaptchaError' => true]);

                    return $result;
                }
            }
        }

        $this->processBooking($result, $data, $reservation, $save);

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        $paymentTransactionId = null;

        $transfers = [];

        $paymentCompleted =
            !empty($data['bookings'][0]['packageCustomerService']['id']) ||
            !empty($data['bookings'][0]['packageCustomerService']['packageCustomer']['id']) ||
            $paymentAS->processPayment(
                $result,
                $data['payment'],
                $reservation,
                new BookingType($type),
                $paymentTransactionId,
                $transfers
            );

        if (!$paymentCompleted || $result->getResult() === CommandResult::RESULT_ERROR) {
            if ($save) {
                $this->deleteReservation($reservation);
            }

            if ($reservation->isNewUser()->getValue() && $reservation->getCustomer()) {
                $this->deleteUserIfNew($reservation->getCustomer()->getId()->getValue());
            }

            return $result;
        }

        $this->finalize(
            $result,
            $reservation,
            new BookingType($type),
            isset($data['isCart']) && is_string($data['isCart']) ?
                filter_var($data['isCart'], FILTER_VALIDATE_BOOLEAN) :
                !empty($data['isCart'])
        );

        $paymentAS->setPaymentTransactionId(
            !empty($result->getData()['payment']['id']) ? $result->getData()['payment']['id'] : null,
            $paymentTransactionId
        );

        if (!empty($transfers)) {
            $paymentAS->setPaymentsTransfers($transfers);
        }

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult $result
     * @param array         $appointmentData
     * @param Reservation   $reservation
     * @param bool          $save
     *
     * @return void
     *
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function processBooking($result, $appointmentData, $reservation, $save)
    {
        $appointmentData['bookings'][0]['info'] = !empty($appointmentData['bookings'][0]['customer']) ? json_encode(
            [
                'firstName' => $appointmentData['bookings'][0]['customer']['firstName'],
                'lastName'  => $appointmentData['bookings'][0]['customer']['lastName'],
                'phone'     => $appointmentData['bookings'][0]['customer']['phone'],
                'locale'    => isset($appointmentData['locale']) ? $appointmentData['locale'] : null,
                'timeZone'  => isset($appointmentData['timeZone']) ? $appointmentData['timeZone'] : null,
                'urlParams' => !empty($appointmentData['urlParams']) ? $appointmentData['urlParams'] : null,
            ]
        ) : null;

        if (!empty($appointmentData['bookings'][0]['customer'])) {
            // Fix for customFields being sent as JSON string
            if (
                !empty($appointmentData['bookings'][0]['customer']['customFields']) &&
                !is_array($appointmentData['bookings'][0]['customer']['customFields'])
            ) {
                $appointmentData['bookings'][0]['customer']['customFields'] = null;
            }

            // add customer language from booking info
            $appointmentData['bookings'][0]['customer'] = array_merge(
                $appointmentData['bookings'][0]['customer'],
                empty($appointmentData['bookings'][0]['customer']['translations']) ?
                    ['translations' => json_encode(array('defaultLanguage' => isset($appointmentData['locale']) ? $appointmentData['locale'] : ''))] : [],
                !empty($appointmentData['bookings'][0]['customer']['customFields']) ?
                    ['customFields' => json_encode($appointmentData['bookings'][0]['customer']['customFields'])] : []
            );
        }

        $newUserId = null;

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        if (!empty($appointmentData['bookings'][0]['customer']['id'])) {
            $existingCustomersIds = $userRepository->getIds(
                ['id' => [(int)$appointmentData['bookings'][0]['customer']['id']]]
            );

            if (!in_array((int)$appointmentData['bookings'][0]['customer']['id'], $existingCustomersIds)) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(['message' => 'Unknown User']);

                return null;
            }
        }

        $user = null;

        // Create a new user if it doesn't exist. For adding appointment from the front-end.
        if (!$appointmentData['bookings'][0]['customerId'] && !$appointmentData['bookings'][0]['customer']['id']) {
            /** @var CustomerApplicationService $customerAS */
            $customerAS = $this->container->get('application.user.customer.service');

            /** @var Customer $user */
            $user = $customerAS->getNewOrExistingCustomer($appointmentData['bookings'][0]['customer'], $result, true);

            if ($user && $user->getTranslations()) {
                $appointmentData['bookings'][0]['customer']['translations'] = $user->getTranslations()->getValue();
            }

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return null;
            }

            if ($save && !$user->getId()) {
                if (!($newUserId = $userRepository->add($user))) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setData(['emailError' => true]);

                    return null;
                }

                $user->setId(new Id($newUserId));
            }

            if ($user->getId()) {
                $appointmentData['bookings'][0]['customerId'] = $user->getId()->getValue();

                $appointmentData['bookings'][0]['customer']['id'] = $user->getId()->getValue();

                if ($user->getType() === AbstractUser::USER_ROLE_CUSTOMER && $user->getStripeConnect() && $user->getStripeConnect()->getId()) {
                    $appointmentData['bookings'][0]['customer']['stripeConnect'] = $user->getStripeConnect()->toArray();
                }
            }
        } elseif (!empty($appointmentData['bookings'][0]['customer']['email'])) {
            $user = $userRepository->getByEmail($appointmentData['bookings'][0]['customer']['email']);

            if ($user && $user->getId()) {
                $appointmentData['bookings'][0]['customer']['id'] = $user->getId()->getValue();

                $appointmentData['bookings'][0]['customerId'] = $user->getId()->getValue();
            }
        } else {
            $user = $userRepository->getById(
                $appointmentData['bookings'][0]['customerId'] ?: $appointmentData['bookings'][0]['customer']['id']
            );
        }

        if (!isset($appointmentData['isBackendOrCabinet']) && $user && $user->getStatus() && $user->getStatus()->getValue() === Status::BLOCKED) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['customer_blocked']);
            $result->setData(['customerBlocked' => true]);

            return null;
        }

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        if ($reservation->hasCustomFieldsValidation()->getValue()) {
            $appointmentData['uploadedCustomFieldFilesInfo'] = [];

            if (isset($appointmentData['bookings'][0]['customFields'])) {
                $appointmentData['uploadedCustomFieldFilesInfo'] = $customFieldService->processCustomFields(
                    $appointmentData['bookings'][0]['customFields']
                );
            }
        }

        if (
            !empty($appointmentData['bookings'][0]['customer']) &&
            !empty($appointmentData['bookings'][0]['customer']['customFields'])
        ) {
            $customerCustomFields = json_decode($appointmentData['bookings'][0]['customer']['customFields'], true);

            $appointmentData['uploadedCustomerCustomFieldFilesInfo'] = $customFieldService->processCustomFields(
                $customerCustomFields
            );

            if (isset($appointmentData['bookings'][0]['customer']['id'])) {
                $userRepository->updateFieldById(
                    $appointmentData['bookings'][0]['customer']['id'],
                    json_encode($customerCustomFields),
                    'customFields'
                );
            }
        }

        if (
            isset($appointmentData['bookings'][0]['customFields']) &&
            is_array($appointmentData['bookings'][0]['customFields'])
        ) {
            $appointmentData['bookings'][0]['customFields'] = json_encode(
                $appointmentData['bookings'][0]['customFields']
            );
        }

        if (!empty($appointmentData['bookings'][0]['customer']['email']) && !empty($appointmentData['bookings'][0]['customer']['subscribeToMailchimp'])) {
            /** @var AbstractMailchimpService $mailchimpService */
            $mailchimpService = $this->container->get('infrastructure.mailchimp.service');
            $userData = $user ? $user->toArray() : $appointmentData['bookings'][0]['customer'];
            $mailchimpService->addOrUpdateSubscriber($userData['email'], $userData);
        }

        try {
            $this->book($appointmentData, $reservation, $save);
        } catch (CustomerBookedException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['customerAlreadyBooked' => true]);
        } catch (BookingUnavailableException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['timeSlotUnavailable' => true]);
        } catch (PackageBookingUnavailableException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['packageBookingUnavailable' => true]);
        } catch (BookingsLimitReachedException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['bookingsLimitReached' => true]);
        } catch (EventBookingUnavailableException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['eventBookingUnavailable' => true]);
        } catch (CouponUnknownException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['couponUnknown' => true]);
        } catch (CouponInvalidException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['couponInvalid' => true]);
        } catch (CouponExpiredException $e) {
            return $this->manageException($result, $newUserId, $e->getMessage(), ['couponExpired' => true]);
        }

        $reservation->setIsNewUser(new BooleanValueObject($newUserId !== null));

        $reservation->setLocale(new Label(isset($appointmentData['locale']) ? $appointmentData['locale'] : ''));

        $reservation->setTimezone(new Label(!empty($appointmentData['timeZone']) ? $appointmentData['timeZone'] : ''));

        if (array_key_exists('uploadedCustomFieldFilesInfo', $appointmentData)) {
            $reservation->setUploadedCustomFieldFilesInfo($appointmentData['uploadedCustomFieldFilesInfo']);
        }

        if (array_key_exists('uploadedCustomerCustomFieldFilesInfo', $appointmentData)) {
            $reservation->setUploadedCustomerCustomFieldFilesInfo($appointmentData['uploadedCustomerCustomFieldFilesInfo']);
        }
    }

    /**
     * @param CommandResult $result
     * @param int|null      $newUserId
     * @param string        $message
     * @param array         $data
     *
     * @return null
     *
     * @throws QueryExecutionException
     */
    private function manageException($result, $newUserId, $message, $data)
    {
        $this->deleteUserIfNew($newUserId);

        $result->setResult(CommandResult::RESULT_ERROR);
        $result->setMessage($message);
        $result->setData($data);

        return null;
    }

    /**
     * @param CommandResult $result
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     * @param bool          $isCart
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws ForbiddenFileUploadException
     * @throws InvalidArgumentException
     */
    public function finalize($result, $reservation, $bookingType, $isCart)
    {
        /** @var CustomerApplicationService $customerApplicationService */
        $customerApplicationService = $this->container->get('application.user.customer.service');

        /** @var AbstractUser $loggedInUser */
        $loggedInUser = $this->container->get('logged.in.user');

        $isAdmin = $loggedInUser && $loggedInUser->getType() === AbstractUser::USER_ROLE_ADMIN;

        if (!$isAdmin) {
            $customerApplicationService->setWPUserForCustomer(
                $reservation->getCustomer(),
                $reservation->isNewUser()->getValue()
            );
        }

        /** @var Payment|null $payment */
        $payment = null;

        switch ($bookingType->getValue()) {
            case (Entities::APPOINTMENT):
            case (Entities::EVENT):
                $payment = $reservation->getBooking()->getPayments()->length() ?
                    $reservation->getBooking()->getPayments()->getItem(0) : null;

                break;

            case (Entities::PACKAGE):
                /** @var PackageCustomerService $packageCustomerService */
                foreach ($reservation->getPackageCustomerServices()->getItems() as $packageCustomerService) {
                    $payment =
                        $packageCustomerService->getPackageCustomer()->getPayments()
                            ->getItem($packageCustomerService->getPackageCustomer()->getPayments()->keys()[0]);

                    break;
                }

                break;
        }

        if (!$reservation->getBooking() && $reservation->getPackageReservations()->length() === 0) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully added booking');
            $result->setData(
                [
                    'type'                     => $bookingType->getValue(),
                    'customer'                 => array_merge(
                        $reservation->getCustomer()->toArray(),
                        [
                            'locale'   => $reservation->getLocale()->getValue(),
                            'timeZone' => $reservation->getTimeZone()->getValue()
                        ]
                    ),
                    $bookingType->getValue()   => null,
                    Entities::BOOKING          => null,
                    'utcTime'                  => [],
                    'appointmentStatusChanged' => false,
                    'packageId'                => $reservation->getBookable()->getId()->getValue(),
                    'isPackageAppointment'     => true,
                    'package'                  => [],
                    'isCart'                   => $isCart,
                    'recurring'                => [],
                    'bookable'                 => $reservation->getBookable()->toArray(),
                    'paymentId'                => $payment->getId()->getValue(),
                    'packageCustomerId'        => $payment->getPackageCustomerId()->getValue(),
                    'payment'                  => $payment->toArray(),
                    'packageCustomerToken'     => $reservation->getPackageCustomer() && $reservation->getPackageCustomer()->getToken()
                        ? $reservation->getPackageCustomer()->getToken()->getValue() : null,
                ]
            );

            return;
        }

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        if ($reservation->getBooking() && $reservation->getBooking()->getId()) {
            $customFieldService->saveUploadedFiles(
                $reservation->getBooking()->getId()->getValue(),
                $reservation->getUploadedCustomFieldFilesInfo(),
                '',
                $reservation->getRecurring() && $reservation->getRecurring()->length()
            );
        }

        if ($reservation->getCustomer()) {
            $customFieldService->saveUploadedFiles(
                $reservation->getCustomer()->getId()->getValue(),
                $reservation->getUploadedCustomerCustomFieldFilesInfo(),
                '',
                false
            );
        }

        $recurringReservations = [];

        if ($bookingType->getValue() === Entities::APPOINTMENT) {
            /** @var Reservation $recurringReservation */
            foreach ($reservation->getRecurring()->getItems() as $key => $recurringReservation) {
                $customFieldService->saveUploadedFiles(
                    $recurringReservation->getBooking()->getId()->getValue(),
                    $reservation->getUploadedCustomFieldFilesInfo(),
                    '',
                    $key !== $reservation->getRecurring()->length() - 1
                );

                $recurringReservations[] = $this->getResultData($recurringReservation, $bookingType);
            }
        }

        $packageReservations = [];

        if ($bookingType->getValue() === Entities::PACKAGE) {
            /** @var Reservation $packageReservation */
            foreach ($reservation->getPackageReservations()->getItems() as $key => $packageReservation) {
                $customFieldService->saveUploadedFiles(
                    $packageReservation->getBooking()->getId()->getValue(),
                    $reservation->getUploadedCustomFieldFilesInfo(),
                    '',
                    $key !== $reservation->getPackageReservations()->length() - 1
                );

                $packageReservations[] = $this->getResultData(
                    $packageReservation,
                    new BookingType(Entities::APPOINTMENT)
                );
            }
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added booking');
        $result->setData(
            array_merge(
                $this->getResultData($reservation, $bookingType),
                [
                    'recurring' => $recurringReservations,
                    'package'   => $packageReservations,
                    'packageId' => $packageReservations ? $reservation->getBookable()->getId()->getValue() : null,
                    'isPackageAppointment' =>
                        ($reservation->getBooking() && $reservation->getBooking()->getPackageCustomerService() !== null) ||
                        $reservation->getReservation()->getType()->getValue() === BookableType::PACKAGE,
                    'customer'  => $reservation->getCustomer() ? array_merge(
                        $reservation->getCustomer()->toArray(),
                        [
                            'locale'   => $reservation->getLocale()->getValue(),
                            'timeZone' => $reservation->getTimeZone()->getValue()
                        ]
                    ) : [],
                    'bookable'  => $reservation->getBookable()->toArray(),
                    'paymentId' => $payment ? $payment->getId()->getValue() : null,
                    'packageCustomerId' => $payment && $payment->getPackageCustomerId() ?
                        $payment->getPackageCustomerId()->getValue() : null,
                    'payment'   => $payment ? $payment->toArray() : null,
                    'isCart'    => $isCart,
                    'packageCustomerToken' => $reservation->getPackageCustomer() && $reservation->getPackageCustomer()->getToken()
                        ? $reservation->getPackageCustomer()->getToken()->getValue() : null,
                ]
            )
        );
    }

    /**
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     *
     * @return array
     */
    public function getResultData($reservation, $bookingType)
    {
        return [
            'type'                     => $bookingType->getValue(),
            $bookingType->getValue()   => array_merge(
                $reservation->getReservation()->toArray(),
                [
                    'bookings' => $reservation->getBooking() ? [
                        $reservation->getBooking()->toArray()
                    ] : []
                ]
            ),
            Entities::BOOKING          => $reservation->getBooking() ? $reservation->getBooking()->toArray() : null,
            'utcTime'                  => $reservation->getBooking() ? $this->getBookingPeriods(
                $reservation->getReservation(),
                $reservation->getBooking(),
                $reservation->getBookable()
            ) : [],
            'appointmentStatusChanged' => $reservation->isStatusChanged() ?
                $reservation->isStatusChanged()->getValue() : false,
        ];
    }

    /**
     * @param Json $taxJson
     *
     * @return Tax
     * @throws InvalidArgumentException
     */
    public function getTax($taxJson)
    {
        return $taxJson &&
            ($taxData = json_decode($taxJson->getValue(), true)) &&
            !empty($taxData[0])
                ? TaxFactory::create($taxData[0])
                : null;
    }

    /**
     * @param Tax   $tax
     * @param float $amount
     *
     * @return float
     */
    protected function getTaxAmount($tax, $amount)
    {
        switch ($tax->getType()->getValue()) {
            case (AmountType::PERCENTAGE):
                return round($amount / 100 * (float)$tax->getAmount()->getValue(), 4);

            case (AmountType::FIXED):
                return $tax->getAmount()->getValue();
        }

        return 0;
    }

    /**
     * @param Tax   $tax
     *
     * @return string
     */
    protected function getTaxRate($tax)
    {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        switch ($tax->getType()->getValue()) {
            case (AmountType::PERCENTAGE):
                return $tax->getAmount()->getValue() . '%';

            case (AmountType::FIXED):
                return $helperService->getFormattedPrice($tax->getAmount()->getValue());
        }

        return 0;
    }

    /**
     * @param Coupon $coupon
     * @param float  $amount
     *
     * @return float
     */
    protected function getCouponDiscountAmount($coupon, $amount)
    {
        return $coupon && $coupon->getDiscount()
            ? round($amount / 100 * (float)$coupon->getDiscount()->getValue(), 4)
            : 0;
    }

    /**
     * @param AbstractCustomerBooking $booking
     * @param AbstractBookable        $bookable
     *
     * @return array
     */
    abstract public function getPaymentAmount($booking, $bookable, $invoice = false);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param int|null  $bookingId
     * @param int|null  $packageCustomerId
     * @param array     $paymentData
     * @param float     $amount
     * @param DateTime  $dateTime
     * @param string    $entityType
     *
     * @return Payment
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function addPayment($bookingId, $packageCustomerId, $paymentData, $amount, $dateTime, $entityType)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $paymentStatus = PaymentStatus::PENDING;

        switch ($paymentData['gateway']) {
            case (PaymentType::WC):
                $paymentStatus = $paymentData['status'];
                break;
            case (PaymentType::MOLLIE):
            case (PaymentType::SQUARE):
            case (PaymentType::PAY_PAL):
            case (PaymentType::STRIPE):
            case (PaymentType::RAZORPAY):
                $paymentStatus = PaymentStatus::PAID;
                break;
        }

        $paymentAmount = $paymentData['gateway'] === PaymentType::ON_SITE ? 0 : $amount;

        if (
            !$amount &&
            $paymentData['gateway'] !== PaymentType::ON_SITE &&
            $paymentData['gateway'] !== PaymentType::WC
        ) {
            $paymentData['gateway'] = PaymentType::ON_SITE;
        }

        if (!empty($paymentData['orderStatus'])) {
            $paymentStatus = $this->getWcStatus(
                $entityType,
                $paymentData['orderStatus'],
                'payment',
                false
            ) ?: $paymentStatus;
        }

        if (!empty($paymentData['deposit'])) {
            $paymentStatus = PaymentStatus::PARTIALLY_PAID;
        }

        if (in_array($paymentData['gateway'], [PaymentType::MOLLIE])) {
            $paymentStatus = PaymentStatus::PENDING;
        }

        $paymentEntryData = apply_filters(
            'amelia_before_payment',
            [
                'customerBookingId' => $bookingId,
                'packageCustomerId' => $packageCustomerId,
                'amount'            => $paymentAmount,
                'status'            => $amount > 0 ? $paymentStatus : PaymentStatus::PAID,
                'gateway'           => $paymentData['gateway'],
                'dateTime'          => ($paymentData['gateway'] === PaymentType::ON_SITE) ?
                    $dateTime->format('Y-m-d H:i:s') : DateTimeService::getNowDateTimeObject()->format('Y-m-d H:i:s'),
                'gatewayTitle'      => isset($paymentData['gatewayTitle']) ? $paymentData['gatewayTitle'] : '',
                'parentId'          => !empty($paymentData['parentId']) ? $paymentData['parentId'] : null,
                'wcOrderId'         => !empty($paymentData['wcOrderId']) && $paymentData['gateway'] === 'wc' ?
                    $paymentData['wcOrderId'] : null,
                'wcOrderItemId'     => !empty($paymentData['wcOrderItemId']) && $paymentData['gateway'] === 'wc' ?
                    $paymentData['wcOrderItemId'] : null,
                'invoiceNumber'     => !empty($paymentData['invoiceNumber']) ? $paymentData['invoiceNumber'] : null
            ],
            $amount
        );

        if (!empty($paymentData['isBackendBooking'])) {
            $paymentEntryData['actionsCompleted'] = 1;
        }

        /** @var Payment $payment */
        $payment = PaymentFactory::create($paymentEntryData);

        $payment->setEntity(new Name($entityType));

        if (!($payment instanceof Payment)) {
            throw new InvalidArgumentException('Unknown type');
        }

        $paymentId = $paymentRepository->add($payment);

        $payment->setId(new Id($paymentId));

        if (!$payment->getInvoiceNumber() || !$payment->getInvoiceNumber()->getValue()) {
            $paymentNew = $paymentRepository->getById($paymentId);
            if ($payment instanceof Payment) {
                $payment = $paymentNew;
            }
        }

        return $payment;
    }

    /**
     * @param DateTime $bookingStart
     * @param int      $minimumCancelTime
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws BookingCancellationException
     */
    public function inspectMinimumCancellationTime($bookingStart, $minimumCancelTime)
    {
        if (
            DateTimeService::getNowDateTimeObject() >=
            DateTimeService::getCustomDateTimeObject(
                $bookingStart->format('Y-m-d H:i:s')
            )->modify("-{$minimumCancelTime} second")
        ) {
            throw new BookingCancellationException(
                FrontendStrings::getCabinetStrings()['booking_cancel_exception']
            );
        }

        return true;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param int    $bookingId
     * @param string $type
     * @param array  $recurring
     * @param bool   $isCart
     * @param bool   $appointmentStatusChanged
     * @param int    $packageId
     * @param array  $customerData
     * @param int    $paymentId
     * @param int    $packageCustomerId
     *
     * @return CommandResult
     *
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws QueryExecutionException
     */
    public function getSuccessBookingResponse(
        $bookingId,
        $type,
        $recurring,
        $isCart,
        $appointmentStatusChanged,
        $packageId,
        $customerData,
        $paymentId,
        $packageCustomerId,
        $isPackageAppointment,
        $packageBookingFromBackend
    ) {
        $result = new CommandResult();

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        if ($packageId && (int)$bookingId === 0) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully get booking');
            $result->setData(
                [
                    'type'                     => Entities::APPOINTMENT,
                    Entities::APPOINTMENT      => null,
                    Entities::BOOKING          => null,
                    'appointmentStatusChanged' => false,
                    'packageId'                => $packageId,
                    'customer'                 => $customerData,
                    'recurring'                => [],
                    'isCart'                   => false,
                    'paymentId'                => $paymentId,
                    'packageCustomerId'        => $packageCustomerId,
                    'isPackageAppointment'     => $isPackageAppointment,
                    'packageBookingFromBackend' => $packageBookingFromBackend
                ]
            );

            $result->setDataInResponse(false);

            return $result;
        }

        /** @var Appointment|Event $reservation */
        $reservation = $reservationService->getReservationByBookingId((int)$bookingId);

        /** @var CustomerBooking $booking */
        $booking = $reservation->getBookings()->getItem(
            (int)$bookingId
        );

        $booking->setChangedStatus(new BooleanValueObject(true));

        $recurringReservations = [];

        $recurring = !empty($recurring) ? $recurring : [];

        foreach ($recurring as $recurringData) {
            /** @var Appointment $recurringReservation */
            $recurringReservation = $reservationService->getReservationByBookingId((int)$recurringData['id']);

            /** @var CustomerBooking $recurringBooking */
            $recurringBooking = $recurringReservation->getBookings()->getItem(
                (int)$recurringData['id']
            );

            $recurringBooking->setChangedStatus(new BooleanValueObject(true));

            $recurringReservations[] = [
                'type'                                       => $recurringReservation->getType()->getValue(),
                $recurringReservation->getType()->getValue() => $recurringReservation->toArray(),
                Entities::BOOKING                            => $recurringBooking->toArray(),
                'appointmentStatusChanged'                   => $recurringData['appointmentStatusChanged'],
            ];
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully get booking');
        $result->setData(
            array_merge(
                [
                    'type'                              => $reservation->getType()->getValue(),
                    'isCart'                            => $isCart,
                    $reservation->getType()->getValue() => $reservation->toArray(),
                    Entities::BOOKING                   => $booking->toArray(),
                    'appointmentStatusChanged'          => $appointmentStatusChanged,
                    'paymentId'                         => $paymentId,
                    'packageCustomerId'                 => $packageCustomerId,
                    'isPackageAppointment'              => $isPackageAppointment,
                    'packageBookingFromBackend'         => $packageBookingFromBackend
                ],
                [
                    'customer'  => $customerData,
                    'packageId' => $packageId,
                    'recurring' => $recurringReservations
                ]
            )
        );

        $result->setDataInResponse(false);

        return $result;
    }

    /**
     * @param CommandResult $result
     *
     * @return void
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function runPostBookingActions($result)
    {
        if ($result->getResult() === CommandResult::RESULT_SUCCESS) {
            $recurring = [];

            $bookingId = 0;
            $appointmentStatusChanged = false;

            /** @var PaymentRepository $paymentRepository */
            $paymentRepository = $this->container->get('domain.payment.repository');

            if (!empty($result->getData()['paymentId'])) {
                /** @var Payment $payment */
                $payment = $paymentRepository->getById($result->getData()['paymentId']);

                if (
                    $payment && $payment->getActionsCompleted() && $payment->getActionsCompleted()->getValue() ||
                    $payment && $payment->getTriggeredActions() && $payment->getTriggeredActions()->getValue()
                ) {
                    return;
                } elseif ($payment && !$payment->getTriggeredActions()) {
                    $paymentRepository->updateFieldById($payment->getId()->getValue(), 1, 'triggeredActions');
                }
            }

            switch ($result->getData()['type']) {
                case (Entities::APPOINTMENT):
                    $bookingId = $result->getData()[Entities::BOOKING]['id'];
                    $appointmentStatusChanged = $result->getData()['appointmentStatusChanged'];

                    foreach ($result->getData()['recurring'] as $recurringData) {
                        $recurring[] = [
                            'id'                       => $recurringData[Entities::BOOKING]['id'],
                            'type'                     => $recurringData['type'],
                            'appointmentStatusChanged' => $recurringData['appointmentStatusChanged'],
                        ];
                    }

                    break;

                case (Entities::EVENT):
                    $bookingId = $result->getData()[Entities::BOOKING]['id'];

                    $appointmentStatusChanged = $result->getData()['appointmentStatusChanged'];

                    break;

                case (Entities::PACKAGE):
                    $packageReservations = $result->getData()['package'];

                    foreach ($packageReservations as $index => $packageData) {
                        if ($index > 0) {
                            $recurring[] = [
                                'id'                       => $packageData[Entities::BOOKING]['id'],
                                'type'                     => $packageData['type'],
                                'appointmentStatusChanged' => $packageData['appointmentStatusChanged'],
                            ];
                        } else {
                            $bookingId = $packageData[Entities::BOOKING]['id'];

                            $appointmentStatusChanged = $packageData['appointmentStatusChanged'];
                        }
                    }

                    break;
            }

            /** @var CommandResult $resultData */
            $resultData = $this->getSuccessBookingResponse(
                $bookingId,
                $result->getData()['type'],
                $recurring,
                $result->getData()['isCart'],
                $appointmentStatusChanged,
                $result->getData()['packageId'],
                $result->getData()['customer'],
                !empty($result->getData()['paymentId']) ? $result->getData()['paymentId'] : null,
                !empty($result->getData()['packageCustomerId']) ?
                    $result->getData()['packageCustomerId'] : null,
                !empty($result->getData()['isPackageAppointment']) ?
                    $result->getData()['isPackageAppointment'] : null,
                !empty($result->getData()['packageBookingFromBackend']) ?
                    $result->getData()['packageBookingFromBackend'] : null
            );


            do_action('AmeliaBookingAddedBeforeNotify', $resultData->getData(), $this->container);

            BookingAddedEventHandler::handle(
                $resultData,
                $this->container
            );
        }
    }

    /**
     * @param bool $couponValidation
     * @param bool $customFieldsValidation
     * @param bool $availabilityValidation
     *
     * @return Reservation
     */
    public function getNew($couponValidation, $customFieldsValidation, $availabilityValidation)
    {
        /** @var Reservation $entity */
        $entity = new Reservation();

        $entity->setCouponValidation(new BooleanValueObject($couponValidation));

        $entity->setCustomFieldsValidation(new BooleanValueObject($customFieldsValidation));

        $entity->setAvailabilityValidation(new BooleanValueObject($availabilityValidation));

        return $entity;
    }

    /**
     * @param string $type
     * @param string $orderStatus
     * @param string $statusTarget
     * @param bool   $isUpdate
     *
     * @return mixed
     */
    public function getWcStatus($type, $orderStatus, $statusTarget, $isUpdate)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $wcSettings = $settingsService->getSetting('payments', 'wc');

        if (!empty($wcSettings['rules'][$type])) {
            foreach ($wcSettings['rules'][$type] as $rule) {
                if ($rule['order'] === $orderStatus && ($isUpdate ? $rule['update'] : true)) {
                    return $rule[$statusTarget] !== 'default' ? $rule[$statusTarget] : null;
                }
            }
        }

        return false;
    }

    /**
     * @param CustomerBooking $booking
     *
     * @return void
     * @throws QueryExecutionException
     */
    public function setToken($booking)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        $token = $bookingRepository->getToken($booking->getId()->getValue());

        if (!empty($token['token'])) {
            $booking->setToken(new Token($token['token']));
        }
    }

    /**
     * @param Reservation $reservation
     * @param bool        $usePayment
     *
     * @return array
     */
    public function getProvidersPaymentAmount($reservation, $usePayment = true)
    {
        return [];
    }

    /**
     * @param AbstractBookable $bookable
     * @param bool             $applyDeposit
     *
     * @return boolean
     */
    public function applyDeposit($bookable, $applyDeposit)
    {
        $depositPaymentEnabled = $bookable->getDeposit() &&
            $bookable->getDeposit()->getValue() !== DepositType::DISABLED;

        $fullPaymentEnabled =
            $depositPaymentEnabled &&
            $bookable->getFullPayment() &&
            $bookable->getFullPayment()->getValue();

        return $applyDeposit ? $depositPaymentEnabled : $depositPaymentEnabled && !$fullPaymentEnabled;
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws QueryExecutionException
     */
    abstract public function manageTaxes(&$data);

    /**
     * @param int $bookingId
     * @param string $token
     *
     * @return array
     *
     * @throws AccessDeniedException
     */
    abstract public function deleteBooking($bookingId, $token = null);
}
