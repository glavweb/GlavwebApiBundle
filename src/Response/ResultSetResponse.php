<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Response;

use Glavweb\ApiBundle\Query\ResultSet;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Список API: 200 и тело { list, total, offset, limit }.
 *
 * offset и limit — фактически применённые значения выборки, не эхо запроса.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ResultSetResponse extends JsonResponse
{
    /**
     * @param list<mixed> $data
     */
    public function __construct(array $data, ?int $offset = null, ?int $limit = null, ?int $total = null)
    {
        parent::__construct([
            'list' => $data,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    public static function fromResultSet(ResultSet $resultSet): self
    {
        return new self(
            $resultSet->getData(),
            $resultSet->getOffset(),
            $resultSet->getLimit(),
            $resultSet->getTotal()
        );
    }
}
