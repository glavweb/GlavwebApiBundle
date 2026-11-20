# glavweb/api-bundle

CQRS/REST: attributes, Populator, ParamFetcher, ScopeFetcher, ResultSet, Nelmio describers (включая upload-роуты UploaderBundle, если он установлен), `make:api-controller`.

RequestBag — из `glavweb/extra-bundle`; доменные исключения — из `glavweb/framework-bundle`.

## Authorization: Bearer (access_token firewall)

Токен — непрозрачная строка, не JWT. Клиент передаёт `Authorization: Bearer <token>`.

Вся схема в `Glavweb\ApiBundle\Security`: `UserProviderTokenInterface`, `AccessToken\BearerTokenExtractor`,
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
            - Glavweb\ApiBundle\Security\AccessToken\BearerTokenExtractor
        token_handler: Glavweb\ApiBundle\Security\AccessToken\UserTokenHandler
        failure_handler: Glavweb\ApiBundle\Security\AccessToken\UserTokenAuthenticationFailureHandler
```

Невалидный/протухший токен экстрактор приравнивает к отсутствию: запрос идёт анонимно, 401 отдаёт entry point
только на защищённых эндпоинтах. Другая авторизация (JWT, сессия) — свои extractor/handler в `security.yaml`.

## Список: filter и sort

`ApiFilter` читает query. `limit`, `offset`, `sort`, `scope` остаются на верхнем уровне.

```
filter[title]=Hello
filter[price][gte]=10&filter[price][lt]=100
filter[status][in][]=draft&filter[status][in][]=published
sort=-createdAt,title
```

Голое значение — `eq`. Несколько условий — И. Имена операторов: `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `contains`, `not_contains`, `in`, `nin`.
Кривой фильтр или сортировка — `ClientErrorException` с кодом `PARAMETER_WRONG_FORMAT` (HTTP 400).
`ListQuery::$filters` заполняет `Populator` по атрибуту `FilterBag`. В datagrid именованная карта уходит в `build()` как есть.
