<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder;

use EvilKraft\DatatablesBuilder\Adapter\AdapterInterface;
use EvilKraft\DatatablesBuilder\Column\AbstractColumn;
use EvilKraft\DatatablesBuilder\Column\ActionButtonsColumn;
use EvilKraft\DatatablesBuilder\Column\TwigColumn;
use EvilKraft\DatatablesBuilder\Column\TwigStringColumn;
use EvilKraft\DatatablesBuilder\Exporter\DataTableExporterManager;
use EvilKraft\DatatablesBuilder\Renderer\DatatableRendererInterface;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use function count;


class DataTable
{
    const DEFAULT_OPTIONS = [
        'pagingType'    => 'full_numbers',
        'lengthMenu'    => [[10, 25, 50, -1], [10, 25, 50, 'All']],
        'pageLength'    => 10,
        'displayStart'  => 0,
        'serverSide'    => true,
        'processing'    => true,

        'paging'        => true,
        'lengthChange'  => true,

        'ordering'      => true,
        'searching'     => true,
        'search'        => ['return' => true],  //require the end user to press the return key to trigger a search action
        'autoWidth'     => false,
        'order'         => [],  // [] - Disable initial sort; [[ 0, 'asc' ]] - sort by first column
        'searchDelay'   => 400,
    //    'dom'           => 'lftrip',
        'dom'           => "<'row'<'col-12 col-md'fr><'col-auto order-md-first'B><'col-auto ml-auto text-right'b>>"
                         . "<'row'<'col-sm-12't>>"
                         . "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        'orderCellsTop' => true,
        'stateSave'     => false,
        'fixedHeader'   => false,

        /* ColReorder extension for DataTables */
        'colReorder' => ['realtime' => false],
    ];

    const DEFAULT_TEMPLATE = '@DataTables/datatable.twig';
    const SORT_ASCENDING   = 'asc';
    const SORT_DESCENDING  = 'desc';

    /** @var string */
    protected string $name = 'dt';

    /** @var string */
    protected string $template = self::DEFAULT_TEMPLATE;

    /** @var array */
    protected array $templateParams = ['className' => 'table table-striped table-bordered table-hover table-sm'];

    protected ?AdapterInterface $adapter = null;
    protected array $columns       = [];
    protected array $columnsByName = [];

    protected array $options = [];

    protected string $method = 'POST';

    /** @var bool */
    protected bool $languageFromCDN = true;

    /** @var string */
    protected string $translationDomain = 'messages';

    private DataTableRendererInterface $renderer;
    private ?DataTableState $state = null;
    private EventDispatcherInterface $eventDispatcher;
    private TranslatorInterface      $translator;
    private DataTableExporterManager $exporterManager;

    /** @var callable */
    protected $transformer;


    /**
     * @throws Exception
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator, DataTableExporterManager $exporterManager, array $options = [])
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->translator      = $translator;
        $this->exporterManager = $exporterManager;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function add(string $name, string $type, array $options = []): DataTable
    {
        // Ensure name is unique
        if (isset($this->columnsByName[$name])) {
            throw new InvalidArgumentException(sprintf("There already is a column with name '%s'", $name));
        }

        if(!class_exists($type) || !is_subclass_of($type, AbstractColumn::class)){
            throw new InvalidArgumentException('Could not resolve type "'.$type.'"');
        }

        switch($type) {
            case TwigColumn::class:
            case TwigStringColumn::class: $column = new $type($this->renderer); break;
            default: $column = new $type();
        }

        $column->initialize($name, count($this->columns), $options, $this->getName());

        $this->columns[]            = $column;
        $this->columnsByName[$name] = $column;

        return $this;
    }

    public function isCallback(): bool
    {
        /** @noinspection PhpTernaryExpressionCanBeReplacedWithConditionInspection */
        return is_null($this->state) ? false : $this->state->isCallback();
    }

    /**
     * @throws Exception
     */
    public function handleRequest(Request $request): self
    {
        //$method = $this->getMethod();
        $method = $request->getMethod();
        switch ($method){
            case 'GET'  : $parameters = $request->getQueryParams(); break;
            case 'POST' : $parameters = $request->getParsedBody();  break;
            default:
                throw new Exception("Unknown request method '$method'");
        }


        //if ($this->getName() === ($parameters['_dt']??null)) {
            if (null === $this->state) {
                $this->state = DataTableState::fromDefaults($this);
            }

            $this->state->applyParameters($parameters);
        //}

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getResponse(): Response
    {
        if (null === $this->state) {
            throw new Exception('The DataTable does not know its state yet, did you call handleRequest?');
        }

        // Server side export
        if (null !== $this->state->getExporterName()) {
            return $this->exporterManager
                ->setDataTable($this)
                ->setExporterName($this->state->getExporterName())
                ->getResponse();
        }

        $resultSet = $this->getResultSet();
        $result = [
            'draw'            => $this->state->getDraw(),
            'data'            => iterator_to_array($resultSet->getData()),
            'recordsFiltered' => $resultSet->getTotalDisplayRecords(),
            'recordsTotal'    => $resultSet->getTotalRecords(),
        ];


//        if ($this->state->isInitial()) {
//            $response['options'] = $this->getInitialResponse();
//            $response['template'] = $this->renderer->renderDataTable($this, $this->template, $this->templateParams);
//        }

        /** @var \Slim\Http\Response $response */
        $response = AppFactory::determineResponseFactory()->createResponse();

        return $response->withJson($result);
    }

    public function getInitialResponse(): array
    {
        $options = array_merge($this->getOptions(), [
            'columns' => array_map(
                function (AbstractColumn $column) {
                    return [
                        'data'       => $column->getName(),
                        'name'       => $column->getName(),
                        'title'      => $this->translator->trans($column->getTitle(), [], $this->getTranslationDomain()),
                        'orderable'  => $column->isOrderable(),
                        'searchable' => $column->isSearchable(),
                        'visible'    => $column->isVisible(),
                        'className'  => $column->getClassName(),
                    ];
                }, $this->getColumns()
            ),
        ]);

        return [
            'name'     => $this->getName(),
            'options'  => $options,
            'template' => $this->renderer->renderDataTable($this, $this->template, $this->templateParams),

            'method'   => $this->getMethod(),
            //'state'  => 'fragment' # One of "none"; "query"; "fragment"; "local"; "session"
            //'options' => [
            //    'language' => $table->getLanguageSettings(),
            //],
        ];
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): self
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);

        return $this;
    }

    public function addOrderBy($column, string $direction = self::SORT_ASCENDING): self
    {
        if (!$column instanceof AbstractColumn) {
            $column = is_int($column) ? $this->getColumn($column) : $this->getColumnByName((string) $column);
        }
        $this->options['order'][] = [$column->getIndex(), $direction];

        return $this;
    }

    public function addActionButtons(array $options = []): self
    {
        return $this->add('actionBtns', ActionButtonsColumn::class, $options);
    }

    public function createAdapter(string $adapter, array $options = []): self
    {
        if(!class_exists($adapter) || !is_subclass_of($adapter, AdapterInterface::class)){
            throw new InvalidArgumentException('Could not resolve type "'.$adapter.'"');
        }

        return $this->setAdapter(new $adapter(), $options);
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function setAdapter(AdapterInterface $adapter, array $options = null): self
    {
        if (null !== $options) {
            $adapter->configure($options);
        }
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLanguageFromCDN(bool $languageFromCDN): self
    {
        $this->languageFromCDN = $languageFromCDN;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getTransformer(): ?callable
    {
        return $this->transformer;
    }

    /**
     * @return $this
     */
    public function setTransformer(callable $formatter): DataTable
    {
        $this->transformer = $formatter;

        return $this;
    }

    public function getColumn(int $index): AbstractColumn
    {
        if ($index < 0 || $index >= count($this->columns)) {
            throw new InvalidArgumentException(sprintf('There is no column with index %d', $index));
        }

        return $this->columns[$index];
    }

    public function getColumnByName(string $name): AbstractColumn
    {
        if (!isset($this->columnsByName[$name])) {
            throw new InvalidArgumentException(sprintf("There is no column named '%s'", $name));
        }

        return $this->columnsByName[$name];
    }

    /**
     * @return AbstractColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * The translated column names.
     *
     * @return string[]
     */
    public function getColumnNames(): array
    {
        $columns = [];

        foreach ($this->getColumns() as $column) {
            $columns[] = $this->trans($column->getTitle());
        }

        return $columns;
    }

    public function trans(?string $id): string
    {
        return $this->translator->trans($id, [], $this->getTranslationDomain());
    }

    public function isLanguageFromCDN(): bool
    {
        return $this->languageFromCDN;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return $this
     */
    public function setMethod(string $method): self
    {
        if(!in_array($method, ['GET', 'POST'])){
            throw new InvalidArgumentException('Method should be one of "GET", "POST".');
        }

        $this->method = $method;

        return $this;
    }

    /**
     * @return DataTableState|null
     */
    public function getState(): ?DataTableState
    {
        return $this->state;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    /**
     * @return $this
     */
    public function setRenderer(DataTableRendererInterface $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): self
    {
        if (empty($name)) {
            throw new InvalidArgumentException('DataTable name cannot be empty');
        }
        $this->name = $name;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return $this->options[$name] ?? null;
    }


    /**
     * @throws Exception
     */
    protected function getResultSet(): Adapter\ResultSetInterface
    {
        if (null === $this->adapter) {
            throw new Exception('No adapter was configured yet to retrieve data with. Call "createAdapter" or "setAdapter" before attempting to return data');
        }

        return $this->adapter->getData($this->state);
    }

    /**
     * @return $this
     */
    public function setTranslationDomain(string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver): self
    {
        $resolver->setDefaults(self::DEFAULT_OPTIONS);

        return $this;
    }
}