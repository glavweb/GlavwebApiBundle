<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Query;

use Glavweb\ApiBundle\Attribute\FilterBag;
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
    #[SystemRequestField('limit')]
    public ?int $limit = null;

    /**
     * Сдвиг.
     */
    #[FromRequest]
    #[SystemRequestField('offset')]
    public ?int $offset = null;

    /**
     * Сортировка.
     *
     * Пример: title или -createdAt,title. Минус — по убыванию.
     */
    #[FromRequest]
    #[SystemRequestField('sort')]
    public ?string $sort = null;

    /**
     * filter[поле]=значение или filter[поле][оператор]=значение.
     *
     * @var array<string, array<string, mixed>>
     */
    #[FilterBag]
    public array $filters = [];

    public function __construct(?string $scope = null)
    {
        $this->scope = $scope;
    }

    /**
     * @param list<string> $allowed
     */
    public function guardFilters(array $allowed): void
    {
        ApiFilter::assertFields(array_keys($this->filters), $allowed);
    }
}
