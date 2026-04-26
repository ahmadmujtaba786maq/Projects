<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Invoice;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Invoice\AbstractInvoiceApplicationService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Payment\InvoiceApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class GenerateInvoiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Invoice
 */
class GenerateInvoiceCommandHandler extends CommandHandler
{
    /**
     * @param GenerateInvoiceCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GenerateInvoiceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to read payments.');
        }

        $result = new CommandResult();

        /** @var AbstractInvoiceApplicationService $invoiceService */
        $invoiceService = $this->container->get('application.invoice.service');

        $paymentId = $command->getArg('id');

        try {
            $file = $invoiceService->generateInvoice($paymentId);
        } catch (\Exception $e) {
            $result->setMessage($e->getMessage());
            $result->setResult(CommandResult::RESULT_ERROR);
            return $result;
        }

        $customerId = $file['customerId'];

        unset($file['customerId']);

        if ($command->getField('sendEmail')) {
            /** @var EmailNotificationService $emailNotificationService */
            $emailNotificationService = $this->container->get('application.emailNotification.service');
            $emailNotificationService->sendInvoiceNotification($customerId, $file);
            $result->setMessage('Successfully sent email with invoice');
        } else {
            $file['content'] = base64_encode($file['content']);

            $result->setAttachment(true);
            $result->setFile($file);
            $result->setMessage('Successfully generated invoice PDF');
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);

        return $result;
    }
}
