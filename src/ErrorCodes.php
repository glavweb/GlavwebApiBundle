<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle;

/**
 * Shared API error codes (4001–4199).
 *
 * Projects add their own codes in a subclass (App\ErrorCodes, codes from 5000) and point
 * `glavweb_api.error_codes_class` at it.
 *
 * Resolution of a message:
 *  - `App\ErrorCodes::getMessage($code)` — late static binding, no runtime state needed;
 *  - `ErrorCodes::getMessage($code)` (bundle code: `AppException`, `ClientErrorException::byErrorCode()`)
 *    — reads the catalog class registered by `GlavwebApiBundle::boot()`. This is the only reason
 *    the static catalog exists: exception factories are static and cannot be injected.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ErrorCodes
{
    /** @var class-string<self> */
    private static string $catalogClass = self::class;

    public const array MESSAGES = [
        self::FORM_VALIDATION_FAILED => 'Ошибка валидации формы.',
        self::CLIENT_ERROR => 'Неизвестная ошибка.',
        self::ENTITY_NOT_FOUND => 'Объект не найден.',
        self::ENTITY_VALIDATION_FAILED => 'Ошибка валидации объекта.',
        self::PARAMETER_WRONG_FORMAT => 'Не верный формат входящего параметра.',
        self::PARAMETER_NOT_FOUND => 'Параметр не найден.',

        self::USER_NOT_FOUND => 'Пользователь не найден.',
        self::USER_WRONG_USERNAME_OR_PASSWORD => 'Логин или пароль неверный.',
        self::USER_WRONG_AUTH_TOKEN => 'Токен авторизации неверный или устарел.',
        self::USER_NOT_AUTHENTICATED => 'Пользователь не авторизован.',
    ];

    public const int FORM_VALIDATION_FAILED = 4001;
    public const int CLIENT_ERROR = 4002;
    public const int ENTITY_NOT_FOUND = 4003;
    public const int ENTITY_VALIDATION_FAILED = 4004;
    public const int PARAMETER_WRONG_FORMAT = 4005;
    public const int PARAMETER_NOT_FOUND = 4006;

    public const int USER_NOT_FOUND = 4101;
    public const int USER_WRONG_USERNAME_OR_PASSWORD = 4102;
    public const int USER_WRONG_AUTH_TOKEN = 4103;
    public const int USER_NOT_AUTHENTICATED = 4104;

    /**
     * @param class-string<self> $catalogClass
     */
    public static function setCatalogClass(string $catalogClass): void
    {
        if (!is_a($catalogClass, self::class, true)) {
            throw new \InvalidArgumentException(sprintf('Error codes class "%s" must extend %s.', $catalogClass, self::class));
        }

        self::$catalogClass = $catalogClass;
    }

    /**
     * Subclasses extend this: `array_replace(parent::getMessages(), [...])`.
     *
     * @return array<int, string>
     */
    public static function getMessages(): array
    {
        return self::MESSAGES;
    }

    /**
     * @return class-string<self>
     */
    public static function getCatalogClass(): string
    {
        return self::$catalogClass;
    }

    public static function getMessage(int $code, ?array $messageVars = null): string
    {
        // Called on a subclass → that subclass is the catalog. Called on the base → registered catalog.
        $catalog = static::class === self::class ? self::$catalogClass : static::class;
        $message = $catalog::getMessages()[$code] ?? 'Error.';
        if ($messageVars) {
            return strtr($message, $messageVars);
        }

        return $message;
    }
}
