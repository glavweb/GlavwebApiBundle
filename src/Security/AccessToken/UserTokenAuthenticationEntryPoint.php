<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Security\AccessToken;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * 401 for protected endpoints without a token.
 *
 * @author nilov <nilov@glavweb.ru>
 */
final class UserTokenAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly AuthenticationFailureHandlerInterface $failureHandler)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->failureHandler->onAuthenticationFailure(
            $request,
            $authException ?? new AuthenticationException()
        );
    }
}
