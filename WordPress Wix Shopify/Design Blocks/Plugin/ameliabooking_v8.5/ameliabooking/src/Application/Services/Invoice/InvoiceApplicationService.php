<?php

namespace AmeliaBooking\Application\Services\Invoice;

use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use Dompdf\Dompdf;
use Slim\Exception\ContainerException;

/**
 * Class InvoiceApplicationService
 *
 * @package AmeliaBooking\Application\Services\Invoice
 */
class InvoiceApplicationService extends AbstractInvoiceApplicationService
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
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function generateInvoice($paymentId)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var Payment $payment */
        $payment = $paymentRepository->getById($paymentId);

        if (empty($payment->getInvoiceNumber())) {
            $data = [
                'paymentId'   => $paymentId,
                'parentId'    => $payment->getParentId() ? $payment->getParentId()->getValue() : null,
                'columnName'  => $payment->getPackageCustomerId() ? 'packageCustomerId' : 'customerBookingId',
                'columnValue' => $payment->getPackageCustomerId()
                    ? $payment->getPackageCustomerId()->getValue()
                    : $payment->getCustomerBookingId()->getValue(),
            ];
            $paymentRepository->setInvoiceNumber($data);
        }

        $type = $payment->getEntity()->getValue();

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get("application.placeholder.{$type}.service");

        $reservation = $reservationService->getReservationByPayment($payment);

        $reservationData = $reservation->getData();

        $invoiceData = $placeholderService->getInvoicePlaceholdersData($reservationData);

        ob_start();
        include AMELIA_PATH . '/templates/invoice/invoice.inc';
        $html = ob_get_clean();

        $dompdf = new Dompdf();

        $dompdf->setPaper('A4');

        $dompdf->loadHtml($html);

        $dompdf->render();

        return [
            'name'       => 'Invoice.pdf',
            'type'       => 'application/pdf',
            'content'    =>  $dompdf->output(),
            'customerId' => $reservation->getData()['customer']['id']
        ];
    }
}
