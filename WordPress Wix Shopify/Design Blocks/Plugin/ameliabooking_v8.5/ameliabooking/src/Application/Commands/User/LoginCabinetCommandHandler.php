<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\LoginType;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\Recaptcha\AbstractRecaptchaService;
use AmeliaBooking\Infrastructure\WP\UserService\UserService;
use Interop\Container\Exception\ContainerException;

/**
 * Class LoginCabinetCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User
 */
class LoginCabinetCommandHandler extends CommandHandler
{
    /**
     * @param LoginCabinetCommand $command
     *
     * @return CommandResult
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     */
    public function handle(LoginCabinetCommand $command)
    {
        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

        /** @var Provider $user */
        $user = $this->container->get('logged.in.user');

        /** @var UserService $userService */
        $userService = $this->container->get('users.service');

        /** @var AbstractRecaptchaService $recaptchaService */
        $recaptchaService = $this->container->get('infrastructure.recaptcha.service');

        /** @var string $cabinetType */
        $cabinetType = $command->getField('cabinetType');

        if (
            $command->getField('email') &&
            $command->getField('password') &&
            !$recaptchaService->process(
                $command->getField('recaptcha'),
                $cabinetType
            )
        ) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Recaptcha verification failed');
            $result->setData(['recaptcha_error' => true]);

            return $result;
        }

        // If logged in as WP user that is connected with Amelia user
        if ($user && $user->getId() !== null && $user->getType() === $cabinetType) {
            return $userAS->getAuthenticatedUserResponse($user, true, false, LoginType::WP_USER, $cabinetType);
        }

        // If it's not WP user connected with Amelia user, and it should only check WP login (tokens not sent)
        if ($command->getField('checkIfWpUser')) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setData(['authentication_required' => true]);

            return $result;
        }

        // If token is sent return authenticated user
        if ($command->getField('token') ?: $command->getToken()) {
            /** @var Provider|Customer $user */
            $user = $userAS->getAuthenticatedUser(
                $command->getField('token') ?: $command->getToken(),
                $command->getField('token') !== null,
                $cabinetType . 'Cabinet'
            );

            if ($user === null) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not retrieve user');
                $result->setData(['reauthorize' => true]);

                return $result;
            }

            return $userAS->getAuthenticatedUserResponse($user, true, true, $user->getLoginType(), $cabinetType, $command->getField('changePass'));
        }

        // If token is not set, check if email and password are passed
        if (!$command->getField('email') || !$command->getField('password')) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve user');
            $result->setData(['invalid_credentials' => true]);

            return $result;
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var Provider|Customer $user */
        $user = $userRepository->getByEmail($command->getField('email'), true, false);

        /** @var Provider|Customer $ameliaUserFromWpLogin */
        $ameliaUserFromWpLogin = $userService->getAuthenticatedUser($command->getField('email'), $command->getField('password'));

        if ($ameliaUserFromWpLogin) {
            $userService->loginWordPressUser($command->getField('email'), $command->getField('password'));
        }

        // If user is retrieved by email and password is not set or it is not valid, check if it is WP login
        if (
            !($user instanceof AbstractUser) ||
            !$user->getPassword() ||
            !$user->getPassword()->checkValidity($command->getField('password'))
        ) {
            if ($ameliaUserFromWpLogin) {
                return $userAS->getAuthenticatedUserResponse(
                    $ameliaUserFromWpLogin,
                    true,
                    false,
                    LoginType::WP_CREDENTIALS,
                    $cabinetType
                );
            }

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve user');
            $result->setData(['invalid_credentials' => true]);

            return $result;
        }

        // Authenticate user with username and password
        return $userAS->getAuthenticatedUserResponse($user, true, true, LoginType::AMELIA_CREDENTIALS, $cabinetType);
    }
}
