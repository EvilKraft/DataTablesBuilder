<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Adapter;

use EvilKraft\DataTablesBuilder\DataTableState;

interface AdapterInterface
{
    /**
     * Provides initial configuration to the adapter.
     */
    public function configure(array $options);

    /**
     * Processes a datatable's state into a result set fit for further processing.
     */
    public function getData(DataTableState $state): ResultSetInterface;
}