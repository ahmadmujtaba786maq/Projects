<?php

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Repository\Coupon\CouponRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;

/**
 * Class UpdateCouponStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class UpdateCouponStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdateCouponStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function handle(UpdateCouponStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::COUPONS)) {
            throw new AccessDeniedException('You are not allowed to update coupon!');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->getContainer()->get('domain.coupon.repository');

        $status = $command->getField('status');

        do_action('amelia_before_coupon_status_updated', $command->getArg('id'), $status);

        $couponRepository->updateStatusById(
            $command->getArg('id'),
            $status
        );

        do_action('amelia_after_coupon_status_updated', $command->getArg('id'), $status);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated coupon');
        $result->setData(true);

        return $result;
    }
}
