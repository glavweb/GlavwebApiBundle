<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Security\AccessToken;

use Glavweb\ApiBundle\Security\UserProviderTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves the user from X-User-Token via UserProviderTokenInterface.
 *
 * @author nilov <nilov@glavweb.ru>
 */
final class UserTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly UserProviderTokenInterface $userProvider,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->userProvider->loadUserByToken($accessToken);

        return new UserBadge($user->getUserIdentifier(), static fn (): UserInterface => $user);
    }
}
