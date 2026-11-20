<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Security\AccessToken;

use Glavweb\ApiBundle\Security\UserProviderTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

/**
 * Непрозрачный токен из заголовка Authorization: Bearer.
 * Это не JWT: строка проверяется провайдером, подпись не разбирается.
 *
 * Неизвестный или просроченный токен считается отсутствующим, запрос остаётся анонимным
 * (публичные эндпоинты отвечают 200, защищённые получают 401 от entry point).
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
final class BearerTokenExtractor implements AccessTokenExtractorInterface
{
    public const string HEADER = 'Authorization';

    public function __construct(private readonly UserProviderTokenInterface $userProvider)
    {
    }

    public function extractAccessToken(Request $request): ?string
    {
        $token = self::tokenFromRequest($request);
        if (null === $token) {
            return null;
        }

        try {
            $this->userProvider->loadUserByToken($token);
        } catch (UserNotFoundException) {
            return null;
        }

        return $token;
    }

    /**
     * Сырая строка токена из Authorization: Bearer, без проверки в провайдере.
     */
    public static function tokenFromRequest(Request $request): ?string
    {
        $header = $request->headers->get(self::HEADER);
        if (!\is_string($header)) {
            return null;
        }

        if (1 !== preg_match('/^Bearer\s+(\S+)\s*$/i', $header, $matches)) {
            return null;
        }

        $token = $matches[1];

        return '' === $token ? null : $token;
    }
}
