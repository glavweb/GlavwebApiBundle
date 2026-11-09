<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Security\AccessToken;

use Glavweb\ApiBundle\ErrorCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * 401 body aligned with ExceptionListener.
 *
 * @author nilov <nilov@glavweb.ru>
 */
final class UserTokenAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse([
            'code' => ErrorCodes::USER_NOT_AUTHENTICATED,
            'message' => ErrorCodes::getMessage(ErrorCodes::USER_NOT_AUTHENTICATED),
        ], Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'X-User-Token error="invalid_token"',
        ]);
    }
}
