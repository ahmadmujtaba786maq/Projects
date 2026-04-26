<?php

namespace AmeliaBooking\Application\Services\Invoice;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Dompdf\Dompdf;

/**
 * Class AbstractInvoiceApplicationService
 *
 * @package AmeliaBooking\Application\Services\Invoice
 */
abstract class AbstractInvoiceApplicationService
{
    protected $container;

    /**
     * AbstractInvoiceApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param int $paymentId
     *
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function generateInvoice($paymentId);
}
