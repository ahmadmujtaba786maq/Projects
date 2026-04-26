<?php

namespace AmeliaBooking\Application\Services\Invoice;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Dompdf\Dompdf;

/**
 * Class StarterInvoiceApplicationService
 *
 * @package AmeliaBooking\Application\Services\Invoice
 */
class StarterInvoiceApplicationService extends AbstractInvoiceApplicationService
{
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
    public function generateInvoice($paymentId)
    {
        return [];
    }
}
