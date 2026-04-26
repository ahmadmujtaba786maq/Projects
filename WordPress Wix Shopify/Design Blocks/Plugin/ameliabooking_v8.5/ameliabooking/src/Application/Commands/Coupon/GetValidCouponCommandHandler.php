<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetValidCouponCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class GetValidCouponCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'code',
        'user',
        'id',
        'type'
    ];

    /**
     * @param GetValidCouponCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws QueryExecutionException
     */
    public function handle(GetValidCouponCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        $userData = $command->getField('user');

        /** @var Customer $user */
        $user = ($userData['firstName'] && $userData['lastName']) ?
            $customerAS->getNewOrExistingCustomer($command->getField('user'), $result, false) : null;

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        $entitiesIds = explode(',', $command->getField('id'));

        $code = $command->getField('code');

        $data = [
          'code' => $code,
          'entitiesIds' => $entitiesIds,
          'type' => $command->getField('type'),
          'user' => ($user && $user->getId()) ? $user->getId()->getValue() : null,
        ];

        $data = apply_filters('amelia_before_validate_coupon_filter', $data);

        do_action('amelia_before_validate_coupon', $data);

        try {
            /** @var Coupon $coupon */
            $coupon = $couponAS->processCoupon(
                $data['code'],
                $data['entitiesIds'],
                $data['type'],
                $data['user'],
                true
            );

            /** @var Collection $entitiesList */
            $entitiesList = new Collection();

            switch ($command->getField('type')) {
                case Entities::APPOINTMENT:
                    $coupon->setEventList(new Collection());
                    $coupon->setPackageList(new Collection());

                    $entitiesList = $coupon->getServiceList();

                    break;

                case Entities::EVENT:
                    $coupon->setServiceList(new Collection());
                    $coupon->setPackageList(new Collection());

                    $entitiesList = $coupon->getEventList();

                    break;

                case Entities::PACKAGE:
                    $coupon->setServiceList(new Collection());
                    $coupon->setEventList(new Collection());

                    $entitiesList = $coupon->getPackageList();

                    break;
            }

            foreach ($entitiesList->getItems() as $entityId => $entity) {
                if (!in_array($entityId, $entitiesIds)) {
                    $entitiesList->deleteItem($entityId);
                }
            }
        } catch (CouponUnknownException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(
                [
                    'couponUnknown' => true
                ]
            );

            return $result;
        } catch (CouponInvalidException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(
                [
                    'couponInvalid' => true
                ]
            );

            return $result;
        } catch (CouponExpiredException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(
                [
                    'couponExpired' => true
                ]
            );

            return $result;
        }

        do_action('amelia_after_validate_coupon', $data, $coupon->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved coupon.');
        $result->setData(
            [
                Entities::COUPON => $coupon->toArray(),
                'limit'          => $couponAS->getAllowedCouponLimit(
                    $coupon,
                    $user && $user->getId() ? $user->getId()->getValue() : null
                )
            ]
        );

        return $result;
    }
}
