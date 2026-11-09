# glavweb/api-bundle

CQRS/REST: attributes, Populator, ParamFetcher, ScopeFetcher, ResultSet, Nelmio describers (включая upload-роуты UploaderBundle, если он установлен), `make:api-controller`.

RequestBag — из `glavweb/extra-bundle`; доменные исключения — из `glavweb/framework-bundle`.

## X-User-Token (access_token firewall)

Вся схема в `Glavweb\ApiBundle\Security`: `UserProviderTokenInterface`, `AccessToken\XUserTokenExtractor`,
`AccessToken\UserTokenHandler`, `AccessToken\UserTokenAuthenticationFailureHandler` / `UserTokenAuthenticationEntryPoint` (401 JSON).
Хранение токенов — в проекте: `UserManager` реализует `UserProviderTokenInterface::loadUserByToken()`.

```yaml
# config/services.yaml
Glavweb\ApiBundle\Security\UserProviderTokenInterface: '@App\Model\User\UserManager'

# config/packages/security.yaml
api:
    stateless: true
    entry_point: Glavweb\ApiBundle\Security\AccessToken\UserTokenAuthenticationEntryPoint
    access_token:
        token_extractors:
            - Glavweb\ApiBundle\Security\AccessToken\XUserTokenExtractor
        token_handler: Glavweb\ApiBundle\Security\AccessToken\UserTokenHandler
        failure_handler: Glavweb\ApiBundle\Security\AccessToken\UserTokenAuthenticationFailureHandler
```

Невалидный/протухший токен экстрактор приравнивает к отсутствию: запрос идёт анонимно, 401 отдаёт entry point
только на защищённых эндпоинтах. Другая авторизация (JWT, сессия) — свои extractor/handler в `security.yaml`.
