<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Report;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Report\AbstractReportService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class GetCouponsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetCouponsCommandHandler extends CommandHandler
{
    /**
     * @param GetCouponsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
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

        /** @var AbstractReportService $reportService */
        $reportService = $this->container->get('infrastructure.report.csv.service');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var Collection $coupons */
        $coupons = $couponRepository->getFiltered(
            $command->getField('params'),
            0
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

                $coupon->setUsed(new WholeNumber($couponWithUsedBookings->getUsed()->getValue()));
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

        $rows = [];

        $fields    = $command->getField('params')['fields'];
        $delimiter = $command->getField('params')['delimiter'];

        foreach ((array)$coupons->toArray() as $coupon) {
            $row = [];

            if (in_array('code', $fields, true)) {
                $row[BackendStrings::getFinanceStrings()['code']] = $coupon['code'];
            }

            if (in_array('discount', $fields, true)) {
                $row[BackendStrings::getCommonStrings()['discount']] = $coupon['discount'];
            }

            if (in_array('deduction', $fields, true)) {
                $row[BackendStrings::getPaymentStrings()['deduction']] = $coupon['deduction'];
            }

            if (in_array('services', $fields, true)) {
                $row[BackendStrings::getCommonStrings()['services']] =
                    $coupon['serviceList'] ? $coupon['serviceList'][0]['name'] .
                        (sizeof($coupon['serviceList']) > 1 ?
                            ' & Other Services' : '') : '';
            }

            if (in_array('events', $fields, true)) {
                $row[BackendStrings::getCommonStrings()['events']] =
                    $coupon['eventList'] ? $coupon['eventList'][0]['name'] .
                        (sizeof($coupon['eventList']) > 1 ?
                            ' & Other Events' : '') : '';
            }

            if (in_array('limit', $fields, true)) {
                $row[BackendStrings::getFinanceStrings()['limit']] = $coupon['limit'];
            }

            if (in_array('used', $fields, true)) {
                $row[BackendStrings::getFinanceStrings()['used']] = $coupon['used'];
            }

            $row = apply_filters('amelia_before_csv_export_coupons', $row, $coupon);

            $rows[] = $row;
        }

        $reportService->generateReport($rows, Entities::COUPONS, $delimiter);

        $result->setAttachment(true);

        return $result;
    }
}
