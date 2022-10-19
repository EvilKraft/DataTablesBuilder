<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder;

use EvilKraft\DataTablesBuilder\Exporter\DataTableExporterManager;
use EvilKraft\DataTablesBuilder\Renderer\DatatableRendererInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class DataTableFactory
{
    /** @var array<string, DataTableTypeInterface> */
    protected array $resolvedTypes = [];

    private array $config;
    private DataTableRendererInterface $renderer;
    private EventDispatcherInterface   $eventDispatcher;
    private DataTableExporterManager   $exporterManager;
    private TranslatorInterface        $translator;

    public function __construct(array $config, TranslatorInterface $translator, DataTableRendererInterface $renderer, EventDispatcherInterface $eventDispatcher, DataTableExporterManager $exporterManager)
    {
        $this->config          = $config;
        $this->translator      = $translator;
        $this->renderer        = $renderer;
        $this->eventDispatcher = $eventDispatcher;
        $this->exporterManager = $exporterManager;
    }

    /**
     * @param array $options
     * @return DataTable
     * @throws Exception
     */
    public function create(array $options = []): DataTable
    {
        $config = $this->config;

        return (new DataTable($this->eventDispatcher, $this->translator, $this->exporterManager, array_merge($config['options'] ?? [], $options)))
            ->setRenderer($this->renderer)
            ->setMethod($config['method'] ?? 'POST')
            ->setTranslationDomain($config['translation_domain'] ?? 'messages')
            ->setLanguageFromCDN($config['language_from_cdn'] ?? true);
    }

    /**
     * @param string|DataTableTypeInterface $type
     * @param array $typeOptions
     * @param array $options
     * @return DataTable
     * @throws Exception
     */
    public function createFromType($type, array $typeOptions = [], array $options = []): DataTable
    {
        $dataTable = $this->create($options);

        if (is_string($type)) {
            $name = $type;
            if (isset($this->resolvedTypes[$name])) {
                $type = $this->resolvedTypes[$name];
            } else {
                if(!class_exists($type) || !is_subclass_of($type, DataTableTypeInterface::class)){
                    throw new InvalidArgumentException('Could not resolve type '.$name);
                }

                $this->resolvedTypes[$name] = $type = new $name();
            }
        }

        $type->configure($dataTable, $typeOptions);

        return $dataTable;
    }
}