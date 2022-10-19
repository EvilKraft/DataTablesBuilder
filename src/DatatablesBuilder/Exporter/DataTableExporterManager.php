<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Exporter;

use EvilKraft\DataTablesBuilder\DataTable;
use EvilKraft\DataTablesBuilder\Exporter\Event\DataTableExporterResponseEvent;
use Iterator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DataTableExporterManager.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterManager
{
    /** @var DataTable */
    private DataTable $dataTable;

    /** @var DataTableExporterCollection */
    private DataTableExporterCollection $exporterCollection;

    /** @var string */
    private string $exporterName;

    /**
     * DataTableExporterManager constructor.
     *
     * @param DataTableExporterCollection $exporterCollection
     */
    public function __construct(DataTableExporterCollection $exporterCollection)
    {
        $this->exporterCollection = $exporterCollection;
    }

    /**
     * @param string $exporterName
     * @return DataTableExporterManager
     */
    public function setExporterName(string $exporterName): self
    {
        $this->exporterName = $exporterName;

        return $this;
    }

    /**
     * @param DataTable $dataTable
     * @return DataTableExporterManager
     */
    public function setDataTable(DataTable $dataTable): self
    {
        $this->dataTable = $dataTable;

        return $this;
    }

    public function getResponse(): Response
    {
        $exporter = $this->exporterCollection->getByName($this->exporterName);
        $file = $exporter->export($this->getColumnNames(), $this->getAllData());



        /** @var \Slim\Http\Response $response */
        $response = AppFactory::determineResponseFactory()->createResponse();
        $response = $response->withFileDownload($file->getRealPath(), 'download.'.$file->getExtension());

        $this->dataTable->getEventDispatcher()->dispatch(new DataTableExporterResponseEvent($response), DataTableExporterEvents::PRE_RESPONSE);

        unlink($file->getRealPath());

        return $response;
    }

    /**
     * The translated column names.
     *
     * @return string[]
     */
    private function getColumnNames(): array
    {
        $columns = [];

        foreach ($this->dataTable->getColumns() as $column) {
            if($column->getName() != 'actionBtns'){
                $columns[] = $this->dataTable->trans($column->getTitle());
            }
        }

        return $columns;
    }

    /**
     * Browse the entire DataTable (all pages).
     *
     * A Generator is created in order to remove the 'DT_RowId' key
     * which is created by some adapters (e.g. ORMAdapter).
     */
    private function getAllData(): Iterator
    {
        $data = $this->dataTable
            ->getAdapter()
            ->getData($this->dataTable->getState()->setStart(0)->setLength(-1))
            ->getData();

        foreach ($data as $row) {
            unset($row['DT_RowId'], $row['DT_RowData'], $row['actionBtns']);

            yield $row;
        }
    }
}
