<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Exporter;

/**
 * Defines a DataTable exporter.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
interface DataTableExporterInterface
{
    /**
     * Exports the data from the DataTable to a file.
     */
    public function export(array $columnNames, \Iterator $data): \SplFileInfo;

    /**
     * A unique name to identify the exporter.
     */
    public function getName(): string;
}
