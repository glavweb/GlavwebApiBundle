<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User provider that can resolve a user by API access token (X-User-Token).
 *
 * Implemented by the project UserManager; alias it in services.yaml:
 * `Glavweb\ApiBundle\Security\UserProviderTokenInterface: '@App\Model\User\UserManager'`.
 *
 * @extends UserProviderInterface<UserInterface>
 *
 * @author nilov <nilov@glavweb.ru>
 */
interface UserProviderTokenInterface extends UserProviderInterface
{
    /**
     * @throws UserNotFoundException if the token is unknown, expired or the user is not found
     */
    public function loadUserByToken(string $token): UserInterface;
}
