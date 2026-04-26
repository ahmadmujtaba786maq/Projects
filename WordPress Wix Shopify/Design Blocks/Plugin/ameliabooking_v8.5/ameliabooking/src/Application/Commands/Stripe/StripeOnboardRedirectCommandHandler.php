<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Stripe;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Stripe\StripeFactory;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Payment\StripeService;
use AmeliaStripe\Exception\ApiErrorException;
use Interop\Container\Exception\ContainerException;

/**
 * Class StripeOnboardRedirectCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Stripe
 */
class StripeOnboardRedirectCommandHandler extends CommandHandler
{
    /**
     * @param StripeOnboardRedirectCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws ApiErrorException
     */
    public function handle(StripeOnboardRedirectCommand $command)
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

        /** @var ProviderApplicationService $providerService */
        $providerService = $this->container->get('application.user.provider.service');

        /** @var Provider $provider */
        $provider = $providerService->getProviderWithServicesAndSchedule((int)$command->getArg('id'));

        /** @var StripeService $stripeService */
        $stripeService = $this->container->get('infrastructure.payment.stripe.service');

        $onBoardData = $stripeService->onBoardProvider(
            $command->getField('accountType') === 'express' ? $provider->getEmail()->getValue() : null,
            $provider->getStripeConnect() && $provider->getStripeConnect()->getId() ?
                $provider->getStripeConnect()->getId()->getValue() : null,
            $command->getField('returnUrl'),
            $command->getField('accountType')
        );

        if (!$provider->getStripeConnect() || !$provider->getStripeConnect()->getId()) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');

            if (!$provider->getStripeConnect()) {
                $provider->setStripeConnect(
                    StripeFactory::create(
                        [
                            'id' => null,
                        ]
                    )
                );
            }

            $provider->getStripeConnect()->setId(new Name($onBoardData['id']));

            $providerRepository->updateFieldById(
                $provider->getId()->getValue(),
                json_encode($provider->getStripeConnect()->toArray()),
                'stripeConnect'
            );
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved stripeData.');
        $result->setData(
            [
                'url' => $onBoardData['url'],
            ]
        );

        return $result;
    }
}
