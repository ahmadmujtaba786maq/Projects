<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;

/**
 * Class AddCouponCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class AddCouponCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'code',
        'discount',
        'deduction',
        'limit',
        'status',
        'services',
        'events',
        'packages'
    ];

    /**
     * @param AddCouponCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(AddCouponCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::COUPONS)) {
            throw new AccessDeniedException('You are not allowed to add new coupon.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        $couponArray = $command->getFields();

        $couponArray = apply_filters('amelia_before_coupon_added_filter', $couponArray);

        do_action('amelia_before_coupon_added', $couponArray);

        /** @var Coupon $coupon */
        $coupon = CouponFactory::create($couponArray);

        if (!($coupon instanceof Coupon)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to create coupon.');

            return $result;
        }

        /** @var Collection $services */
        $services = $command->getFields()['services'] ? $serviceRepository->getByCriteria(
            [
                'services' => $command->getFields()['services']
            ]
        ) : new Collection();

        $coupon->setServiceList($services);

        /** @var Collection $events */
        $events = $command->getFields()['events'] ? $eventAS->getEventsByIds(
            $command->getFields()['events'],
            [
            ]
        ) : new Collection();

        $coupon->setEventList($events);

        /** @var Collection $packages */
        $packages = $command->getFields()['packages'] ? $packageRepository->getByCriteria(
            [
                'packages' => $command->getFields()['packages']
            ]
        ) : new Collection();

        $coupon->setPackageList($packages);

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        $couponRepository->beginTransaction();

        if (!$couponAS->add($coupon)) {
            $couponRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to create coupon.');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('New coupon successfully created.');
        $result->setData(
            [
                Entities::COUPON => $coupon->toArray(),
            ]
        );

        $couponRepository->commit();

        do_action('amelia_after_coupon_added', $coupon->toArray());

        return $result;
    }
}
