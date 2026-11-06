<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Response;

use Glavweb\ApiBundle\Query\ResultSet;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ResultSetResponse.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ResultSetResponse extends JsonResponse
{
    /**
     * ListResponse constructor.
     */
    public function __construct(array $data, ?int $offset = null, ?int $limit = null, ?int $total = null)
    {
        parent::__construct($data, 200, []);

        $this->setContentRangeHeader($offset, $limit, $total);
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

    protected function setContentRangeHeader(?int $offset, ?int $limit, ?int $total): void
    {
        $offset = (int) $offset;
        $total = (int) $total;
        $range = '*/0';

        if ($total > 0) {
            if (null === $limit) {
                $end = $total - 1;
            } else {
                $end = min($offset + $limit - 1, max($offset, $total - 1));
            }
            if (null !== $limit && 0 === (int) $limit) {
                $offset = '';
                $end = '';
            }

            $range = "$offset-$end/$total";
        }

        $this->headers->set('Content-Range', "items $range");

        $needPartialContentStatus = $offset > 0 || (null !== $limit && $total > $limit);

        if ($needPartialContentStatus) {
            $this->setStatusCode(self::HTTP_PARTIAL_CONTENT);
        }
    }
}
