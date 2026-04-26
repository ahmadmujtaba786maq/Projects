<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\Recaptcha\AbstractRecaptchaService;

/**
 * Class ReauthorizeCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class ReauthorizeCommandHandler extends CommandHandler
{
    /**
     * @param ReauthorizeCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Exception
     */
    public function handle(ReauthorizeCommand $command)
    {
        $result = new CommandResult();

        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');

        /** @var EmailNotificationService $notificationService */
        $notificationService = $this->getContainer()->get('application.emailNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->getContainer()->get('application.whatsAppNotification.service');

        /** @var Customer $customer */
        $customer = $userRepository->getByEmail($command->getField('email'));

        /** @var AbstractRecaptchaService $recaptchaService */
        $recaptchaService = $this->container->get('infrastructure.recaptcha.service');

        if (!$recaptchaService->process($command->getField('recaptcha'), $command->getField('cabinetType'))) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Recaptcha verification failed');
            $result->setData(['recaptcha_error' => true]);

            return $result;
        }

        if ($customer !== null) {
            $notificationService->sendRecoveryEmail($customer, $command->getField('email'), $command->getField('cabinetType'));
        }

        if ($customer !== null && $whatsAppNotificationService->checkRequiredFields() && !empty($customer->getPhone())) {
            $whatsAppNotificationService->sendRecoveryWhatsApp($customer, $command->getField('email'), $command->getField('cabinetType'));
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully sent recovery email');

        return $result;
    }
}
