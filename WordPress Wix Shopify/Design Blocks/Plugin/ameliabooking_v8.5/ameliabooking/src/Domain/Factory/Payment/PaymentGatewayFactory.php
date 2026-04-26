<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Payment;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Payment\PaymentGateway;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class PaymentGatewayFactory
 *
 * @package AmeliaBooking\Domain\Factory\Payment
 */
class PaymentGatewayFactory
{
    /**
     * @param $data
     *
     * @return PaymentGateway
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        return new PaymentGateway(
            new Name($data['name'])
        );
    }
}
