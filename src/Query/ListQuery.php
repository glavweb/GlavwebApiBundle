<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Query;

use Glavweb\ApiBundle\Attribute\FromRequest;
use Glavweb\ApiBundle\Attribute\SystemRequestField;

/**
 * Class ListQuery.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class ListQuery
{
    /**
     * Область видимости данных.
     */
    public ?string $scope = null;

    /**
     * Лимит.
     */
    #[FromRequest]
    #[SystemRequestField('$limit')]
    public ?int $limit = null;

    /**
     * Сдвиг.
     */
    #[FromRequest]
    #[SystemRequestField('$offset')]
    public ?int $offset = null;

    /**
     * Сортировка.
     *
     * Пример: {"id":"asc"} или {"id":"desc"}
     */
    #[FromRequest]
    #[SystemRequestField('$sort')]
    public ?string $sort = null;

    public function __construct(?string $scope = null)
    {
        $this->scope = $scope;
    }
}
