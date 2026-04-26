<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Package;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddPackageCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Package
 */
class AddPackageCustomerCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'packageId',
        'customerId',
    ];

    /**
     * @param AddPackageCustomerCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function handle(AddPackageCustomerCommand $command)
    {
        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        if (!$command->getPermissionService()->currentUserCanWrite(Entities::PACKAGES)) {
            /** @var AbstractUser $user */
            $user = $userAS->getAuthenticatedUser($command->getToken(), false, 'customerCabinet');

            if ($user === null) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not retrieve user');
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );

                return $result;
            }
        }

        $this->checkMandatoryFields($command);

        /** @var AbstractPackageApplicationService $packageApplicationService */
        $packageApplicationService = $this->container->get('application.bookable.package');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var Coupon $coupon */
        $coupon = $command->getField('couponId')
            ? $couponRepository->getById($command->getField('couponId'))
            : null;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::PACKAGE);


        /** @var Package $package */
        $package = $packageRepository->getById($command->getField('packageId'));


        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = $packageApplicationService->addPackageCustomer(
            $package,
            $command->getField('customerId'),
            $coupon,
            true
        );

        $rules = $command->getField('rules');

        if (empty($rules)) {
            $rules = [];

            foreach ($package->getBookable()->getItems() as $packageService) {
                $rules[] = [
                    'serviceId'  => $packageService->getService()->getId()->getValue(),
                    'providerId' => null,
                    'locationId' => null
                ];
            }
        }

        $packageApplicationService->addPackageCustomerServices(
            $package,
            $packageCustomer,
            $rules,
            true
        );

        /** @var Payment $payment */
        $payment = $reservationService->addPayment(
            null,
            $packageCustomer->getId()->getValue(),
            [
                'isBackendBooking' => true,
                'gateway'          => PaymentType::ON_SITE
            ],
            !empty($package->getPrice()) ? $package->getPrice()->getValue() : 0,
            DateTimeService::getNowDateTimeObject(),
            Entities::PACKAGE
        );

        /** @var Collection $payments */
        $payments = new Collection();
        $payments->addItem($payment, $payment->getId()->getValue());
        $packageCustomer->setPayments($payments);


        do_action('amelia_after_package_booked_backend', $packageCustomer->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new package booking.');
        $result->setData(
            [
                'packageCustomerId' => $packageCustomer->getId() ? $packageCustomer->getId()->getValue() : null,
                'notify'            => $command->getField('notify'),
                'paymentId'         => $payment->getId()->getValue(),
                'onlyOneEmployee'   => $packageApplicationService->getOnlyOneEmployee($package->toArray()),
            ]
        );

        return $result;
    }
}
