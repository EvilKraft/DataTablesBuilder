<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Column;

use EvilKraft\DataTablesBuilder\Renderer\DatatableRendererInterface;
use Exception;
use Twig\Extension\StringLoaderExtension;

/**
 * TwigStringColumn.
 */
class TwigStringColumn extends TwigColumn
{
    /**
     * TwigStringColumn constructor.
     * @throws Exception
     */
    public function __construct(DataTableRendererInterface $renderer)
    {
        parent::__construct($renderer);

        if (!$this->twig->hasExtension(StringLoaderExtension::class)) {
            throw new Exception('You must have StringLoaderExtension enabled to use ' . self::class);
        }
    }

    protected function render($value, $context)
    {
        return $this->twig->render('@DataTables/Column/twig_string.html.twig', [
            'column_template' => $this->getTemplate(),
            'row' => $context,
            'value' => $value,
        ]);
    }
}
