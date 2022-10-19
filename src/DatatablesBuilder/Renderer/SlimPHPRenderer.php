<?php
declare(strict_types=1);
namespace EvilKraft\DataTablesBuilder\Renderer;

use EvilKraft\DataTablesBuilder\DataTable;
use Slim\Views\PhpRenderer;

class SlimPHPRenderer implements DatatableRendererInterface
{
    /** @var PhpRenderer */
    private PhpRenderer $renderer;

    public function __construct(PhpRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function renderDataTable(DataTable $dataTable, string $template, array $parameters): string
    {
        $parameters['datatable'] = $dataTable;

        return $this->renderer->render($template, $parameters);
    }
}