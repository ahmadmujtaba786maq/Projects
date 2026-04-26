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
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;

/**
 * Class DeleteCouponCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class DeleteCouponCommandHandler extends CommandHandler
{
    /**
     * @param DeleteCouponCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    public function handle(DeleteCouponCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::COUPONS)) {
            throw new AccessDeniedException('You are not allowed to delete coupons.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var CouponApplicationService $couponApplicationService */
        $couponApplicationService = $this->container->get('application.coupon.service');

        /** @var Coupon $coupon */
        $coupon = $couponRepository->getById($command->getArg('id'));

        if (!$coupon instanceof Coupon) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete coupon.');

            return $result;
        }

        $couponRepository->beginTransaction();

        do_action('amelia_before_coupon_deleted', $coupon->toArray());

        if (!$couponApplicationService->delete($coupon)) {
            $couponRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete coupon.');

            return $result;
        }

        $couponRepository->commit();

        do_action('amelia_after_coupon_deleted', $coupon->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Coupon successfully deleted.');
        $result->setData(
            [
                Entities::COUPON => $coupon->toArray()
            ]
        );

        return $result;
    }
}
