<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Stripe;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Payment\StripeService;
use Exception;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetStripeAccountCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Stripe
 */
class GetStripeAccountCommandHandler extends CommandHandler
{
    /**
     * @param GetStripeAccountCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(GetStripeAccountCommand $command)
    {
        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

        $result = new CommandResult();

        try {
            /** @var AbstractUser $user */
            $user = $userAS->authorization($command->getToken(), Entities::PROVIDER);
        } catch (AuthorizationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData(
                [
                    'reauthorize' => true
                ]
            );

            return $result;
        }

        if ($userAS->isCustomer($user)) {
            throw new AccessDeniedException('You are not allowed');
        }

        $result = new CommandResult();

        /** @var StripeService $stripeService */
        $stripeService = $this->container->get('infrastructure.payment.stripe.service');

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var Provider $provider */
        $provider = $providerRepository->getById((int)$command->getArg('id'));

        $stripeAccount = $provider->getStripeConnect() && $provider->getStripeConnect()->getId()
            ? $stripeService->getAccount($provider->getStripeConnect()->getId()->getValue())
            : null;

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved stripeData.');
        $result->setData(
            [
                'account' => $stripeAccount ? [
                    'id'        => $stripeAccount->id,
                    'email'     => $stripeAccount->email,
                    'type'      => $stripeAccount->type,
                    'completed' => $stripeAccount->charges_enabled,
                ] : null
            ]
        );

        return $result;
    }
}
