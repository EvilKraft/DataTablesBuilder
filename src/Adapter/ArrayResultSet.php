<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter;

use ArrayIterator;
use Iterator;

/**
 * ArrayResultSet.
 */
class ArrayResultSet implements ResultSetInterface
{
    /** @var array */
    private array $data;

    /** @var int */
    private int $totalRows;

    /** @var int */
    private int $totalFilteredRows;

    /**
     * ArrayResultSet constructor.
     */
    public function __construct(array $data, int $totalRows = null, int $totalFilteredRows = null)
    {
        $this->data = $data;
        $this->totalRows = $totalRows ?? count($data);
        $this->totalFilteredRows = $totalFilteredRows ?? $this->totalRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalRecords(): int
    {
        return $this->totalRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalDisplayRecords(): int
    {
        return $this->totalFilteredRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): Iterator
    {
        return new ArrayIterator($this->data);
    }
}
