<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Renderer;

use EvilKraft\DataTablesBuilder\DataTable;

interface DatatableRendererInterface
{
    /**
     * Provides the HTML layout of the configured datatable.
     */
    public function renderDataTable(DataTable $dataTable, string $template, array $parameters): string;
}