<?php
declare(strict_types=1);
namespace EvilKraft\DataTablesBuilder\Renderer;

use EvilKraft\DataTablesBuilder\DataTable;
use Slim\Views\PhpRenderer;
use Throwable;

class SlimPHPRenderer implements DatatableRendererInterface
{
    /** @var PhpRenderer */
    private PhpRenderer $renderer;

    public function __construct(PhpRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @throws Throwable
     */
    public function renderDataTable(DataTable $dataTable, string $template, array $parameters): string
    {
        $parameters['datatable'] = $dataTable;

        return $this->renderer->fetch($template, $parameters);
    }
}