<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder;

/**
 * DataTableTypeInterface.
 */
interface DataTableTypeInterface
{
    public function configure(DataTable $dataTable, array $options);
}
