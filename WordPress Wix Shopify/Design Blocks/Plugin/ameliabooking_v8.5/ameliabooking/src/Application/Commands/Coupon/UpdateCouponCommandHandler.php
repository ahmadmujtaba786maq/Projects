<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateCouponCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class UpdateCouponCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'code',
        'discount',
        'deduction',
        'limit',
        'status'
    ];

    /**
     * @param UpdateCouponCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(UpdateCouponCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::COUPONS)) {
            throw new AccessDeniedException('You are not allowed to update coupon.');
        }

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');
        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');
        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $couponId = $command->getArg('id');

        /** @var Coupon $oldCoupon */
        $oldCoupon = $couponRepository->getById($couponId);

        /** @var Collection $allServices */
        $allServices = $serviceRepository->getAll();

        /** @var Collection $services */
        $services = new Collection();

        $couponServices = isset($command->getFields()['services']) ? $command->getFields()['services'] : $oldCoupon->getServiceList()->keys();

        /** @var Service $service */
        foreach ($allServices->getItems() as $service) {
            if (in_array($service->getId()->getValue(), $couponServices)) {
                $services->addItem($service, $service->getId()->getValue());
            }
        }

        /** @var Collection $allEvents */
        $allEvents = $eventRepository->getAll();

        /** @var Collection $events */
        $events = new Collection();

        $couponEvents = isset($command->getFields()['events']) ? $command->getFields()['events'] : $oldCoupon->getEventList()->keys();

        /** @var Event $event */
        foreach ($allEvents->getItems() as $event) {
            if (in_array($event->getId()->getValue(), $couponEvents)) {
                $events->addItem($event, $event->getId()->getValue());
            }
        }

        /** @var Collection $allPackages */
        $allPackages = $packageRepository->getAll();

        /** @var Collection $packages */
        $packages = new Collection();

        $couponPackages = isset($command->getFields()['packages']) ? $command->getFields()['packages'] : $oldCoupon->getPackageList()->keys();

        /** @var Package $package */
        foreach ($allPackages->getItems() as $package) {
            if (in_array($package->getId()->getValue(), $couponPackages)) {
                $packages->addItem($package, $package->getId()->getValue());
            }
        }

        $couponData = $command->getFields();

        $couponData = apply_filters('amelia_before_coupon_updated_filter', $couponData, $oldCoupon ? $oldCoupon->toArray() : null);

        do_action('amelia_before_coupon_updated', $couponData, $oldCoupon ? $oldCoupon->toArray() : null);

        /** @var Coupon $newCoupon */
        $newCoupon = CouponFactory::create($couponData);

        $newCoupon->setServiceList($services);

        $newCoupon->setEventList($events);

        $newCoupon->setPackageList($packages);

        if (!($newCoupon instanceof Coupon)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update coupon.');

            return $result;
        }

        $couponRepository->beginTransaction();

        if (!($couponId = $couponAS->update($oldCoupon, $newCoupon))) {
            $couponRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update coupon.');

            return $result;
        }

        $couponRepository->commit();

        do_action('amelia_after_coupon_updated', $newCoupon ? $newCoupon->toArray() : null, $oldCoupon ? $oldCoupon->toArray() : null);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Coupon successfully updated.');
        $result->setData(
            [
                Entities::COUPON => $newCoupon->toArray(),
            ]
        );

        return $result;
    }
}
