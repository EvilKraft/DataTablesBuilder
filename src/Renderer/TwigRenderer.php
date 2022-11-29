<?php
declare(strict_types=1);
namespace EvilKraft\DatatablesBuilder\Renderer;

use EvilKraft\DatatablesBuilder\DataTable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class TwigRenderer implements DatatableRendererInterface
{
    /** @var Environment */
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        if($twig->getLoader() instanceof FilesystemLoader){
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $twig->getLoader()->addPath(__DIR__.'/../../templates/', 'DataTables');
        }

        $this->twig = $twig;
    }


    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function renderDataTable(DataTable $dataTable, string $template, array $parameters): string
    {
        $parameters['datatable'] = $dataTable;

        return $this->twig->render($template, $parameters);
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}