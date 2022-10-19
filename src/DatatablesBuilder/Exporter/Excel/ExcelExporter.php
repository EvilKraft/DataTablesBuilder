<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Exporter\Excel;

use EvilKraft\DataTablesBuilder\Exporter\DataTableExporterInterface;
use Iterator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Helper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SplFileInfo;

/**
 * Exports DataTable data to Excel.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ExcelExporter implements DataTableExporterInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function export(array $columnNames, Iterator $data): SplFileInfo
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getSheet(0);

        $sheet->fromArray($columnNames);
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        $rowIndex = 2;
        $htmlHelper = new Helper\Html();
        foreach ($data as $row) {
            $colIndex = 1;
            foreach ($row as $value) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $htmlHelper->toRichTextObject($value));
            }
            ++$rowIndex;
        }

        $this->autoSizeColumnWidth($sheet);

        $filePath = sys_get_temp_dir() . '/' . uniqid('dt') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return new SplFileInfo($filePath);
    }

    /**
     * Sets the columns width to automatically fit the contents.
     *
     * @throws Exception
     */
    private function autoSizeColumnWidth(Worksheet $sheet)
    {
        foreach (range(1, Coordinate::columnIndexFromString($sheet->getHighestColumn(1))) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'excel';
    }
}
