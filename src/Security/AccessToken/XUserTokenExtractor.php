<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Security\AccessToken;

use Glavweb\ApiBundle\Security\UserProviderTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

/**
 * API token from X-User-Token header.
 *
 * Unknown / expired tokens are reported as "no token" so the request stays anonymous
 * (public endpoints keep answering 200, protected ones get 401 from the entry point).
 *
 * @author nilov <nilov@glavweb.ru>
 */
final class XUserTokenExtractor implements AccessTokenExtractorInterface
{
    public const string HEADER = 'X-User-Token';

    public function __construct(private readonly UserProviderTokenInterface $userProvider)
    {
    }

    public function extractAccessToken(Request $request): ?string
    {
        $token = $request->headers->get(self::HEADER);
        if (null === $token || '' === $token) {
            return null;
        }

        try {
            $this->userProvider->loadUserByToken($token);
        } catch (UserNotFoundException) {
            return null;
        }

        return $token;
    }
}
