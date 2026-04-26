<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\LoginType;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\Authentication\SocialAuthenticationService;
use Interop\Container\Exception\ContainerException;

class SocialLoginCommandHandler extends CommandHandler
{
    /**
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function handle(SocialLoginCommand $command)
    {
        $result = new CommandResult();

        $code = $command->getField('code');

        $cabinetType = $command->getField('cabinetType');

        $redirectUrl = $command->getField('redirectUri');

        $socialProvider = (string)$command->getArg('provider');

        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

        $userProfile = null;

        /** @var SocialAuthenticationService $socialAuthenticationService */
        $socialAuthenticationService = $this->container->get('infrastructure.social.authentication.service');

        if ($socialProvider === 'google') {
            $userProfile = $socialAuthenticationService->getGoogleUserProfile(
                $code
            );
        }

        if ($socialProvider === 'facebook') {
            $userProfile = $socialAuthenticationService->getFacebookUserProfile(
                $code,
                $redirectUrl
            );
        }

        if (!empty($userProfile['error'])) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($userProfile['error']);

            return $result;
        }

        if ($userProfile === null) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve user');

            return $result;
        }

        if ($cabinetType) {
            /** @var UserRepository $userRepository */
            $userRepository = $this->container->get('domain.users.repository');

            /** @var Provider|Customer $user */
            $user = $userRepository->getByEmail($userProfile['email'], true, false);

            if (!($user instanceof AbstractUser)) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not retrieve user');
                $result->setData(['invalid_credentials' => true]);

                return $result;
            }

            $wpUser = get_user_by('email', $userProfile['email']);

            if ($wpUser) {
                wp_set_current_user($wpUser->ID);
                wp_set_auth_cookie($wpUser->ID);
                do_action('wp_login', $wpUser->user_login, $wpUser);
            }

            return $userAS->getAuthenticatedUserResponse(
                $user,
                true,
                false,
                LoginType::AMELIA_SOCIAL_LOGIN,
                $cabinetType
            );
        }

        $result->setData(
            [
                'token' => !empty($userProfile['token']) ? $userProfile['token'] : '',
                'user'  => $userProfile,
            ]
        );

        return $result;
    }
}
