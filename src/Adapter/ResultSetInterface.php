<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter;

use Iterator;

interface ResultSetInterface
{
    /**
     * Retrieves the total number of accessible records in the original data.
     */
    public function getTotalRecords(): int;

    /**
     * Retrieves the number of records available after applying filters.
     */
    public function getTotalDisplayRecords(): int;

    /**
     * Returns the raw data in the result set.
     */
    public function getData(): Iterator;
}