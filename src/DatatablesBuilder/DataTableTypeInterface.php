<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder;

/**
 * DataTableTypeInterface.
 */
interface DataTableTypeInterface
{
    public function configure(DataTable $dataTable, array $options);
}
