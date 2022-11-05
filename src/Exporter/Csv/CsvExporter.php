<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Exporter\Csv;

use EvilKraft\DataTablesBuilder\Exporter\DataTableExporterInterface;
use Iterator;
use SplFileInfo;

/**
 * Exports DataTable data to a CSV file.
 *
 * @author Maxime Pinot <maxime.pinot@gbh.fr>
 */
class CsvExporter implements DataTableExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export(array $columnNames, Iterator $data): SplFileInfo
    {
        $filePath = sys_get_temp_dir() . 'CsvExporter.php/' . uniqid('dt') . '.csv';

        $file = fopen($filePath, 'w');

        fputcsv($file, $columnNames);

        foreach ($data as $row) {
            fputcsv($file, array_map('strip_tags', $row));
        }

        fclose($file);

        return new SplFileInfo($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'csv';
    }
}
