<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;

/**
 * Class GetCouponsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class GetCouponsCommandHandler extends CommandHandler
{
    /**
     * @param GetCouponsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetCouponsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::COUPONS)) {
            throw new AccessDeniedException('You are not allowed to read coupons.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var Collection $coupons */
        $coupons = $couponRepository->getFiltered(
            $command->getField('params'),
            $settingsService->getSetting('general', 'itemsPerPage')
        );

        if ($coupons->length()) {
            /** @var Collection $couponsWithUsedBookings */
            $couponsWithUsedBookings = $couponRepository->getAllByCriteria(
                [
                    'couponIds' => $coupons->keys(),
                ]
            );

            /** @var Coupon $couponWithUsedBookings */
            foreach ($couponsWithUsedBookings->getItems() as $couponWithUsedBookings) {
                /** @var Coupon $coupon */
                $coupon = $coupons->getItem($couponWithUsedBookings->getId()->getValue());

                /** @var PackageCustomerRepository $packageCustomerRepository */
                $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

                $couponWithUsedPackage = $packageCustomerRepository->getByEntityId(
                    $couponWithUsedBookings->getId()->getValue(),
                    'couponId'
                );

                $coupon->setUsed(new WholeNumber($couponWithUsedBookings->getUsed()->getValue() + $couponWithUsedPackage->length()));
            }

            /** @var Collection $allServices */
            $allServices = $serviceRepository->getAllIndexedById();

            foreach ($couponRepository->getCouponsServicesIds($coupons->keys()) as $ids) {
                /** @var Coupon $coupon */
                $coupon = $coupons->getItem($ids['couponId']);

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

                $coupon->getPackageList()->addItem(
                    $allPackages->getItem($ids['packageId']),
                    $ids['packageId']
                );
            }
        }

        $couponsArray = $coupons->toArray();

        $couponsArray = apply_filters('amelia_get_coupons_filter', $couponsArray);

        do_action('amelia_get_coupons', $couponsArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved coupons.');
        $result->setData(
            [
                Entities::COUPONS => $couponsArray,
                'filteredCount'   => (int)$couponRepository->getCount($command->getField('params')),
                'totalCount'      => (int)$couponRepository->getCount([]),
            ]
        );

        return $result;
    }
}
