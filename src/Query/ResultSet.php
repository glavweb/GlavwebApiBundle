<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Query;

use Glavweb\DatagridBundle\Datagrid\DatagridInterface;

/**
 * Class ResultSet.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ResultSet
{
    private array $data;

    private ?int $offset;

    private ?int $limit;

    private ?int $total;

    /**
     * QueryListResponse constructor.
     */
    public function __construct(array $data, ?int $offset = null, ?int $limit = null, ?int $total = null)
    {
        $this->data = $data;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->total = $total;
    }

    public static function fromDatagrid(DatagridInterface $datagrid): self
    {
        return new self(
            $datagrid->getList(),
            $datagrid->getFirstResult(),
            $datagrid->getMaxResults(),
            $datagrid->getTotal()
        );
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(?int $total): self
    {
        $this->total = $total;

        return $this;
    }
}
