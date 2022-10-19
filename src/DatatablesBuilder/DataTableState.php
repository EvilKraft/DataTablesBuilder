<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder;


use EvilKraft\DataTablesBuilder\Column\AbstractColumn;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * DataTableState.
 *
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class DataTableState
{
    /** @var int */
    private int $draw = 0;

    /** @var int */
    private int $start = 0;

    /** @var int */
    private int $length = -1;

    /** @var string */
    private string $globalSearch = '';

    /** @var array */
    private array $searchColumns = [];

    /** @var array */
    private array $orderBy = [];

    /** @var bool */
    private bool $isInitial = false;

    /** @var bool */
    private bool $isCallback = false;

    /** @var null|string */
    private ?string $exporterName = null;

    private array $columns = [];
    private $transformer;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * DataTableState constructor.
     */
    public function __construct(DataTable $dataTable)
    {
        $this->columns         = $dataTable->getColumns();
        $this->transformer     = $dataTable->getTransformer();
        $this->eventDispatcher = $dataTable->getEventDispatcher();
    }

    /**
     * Constructs a state based on the default options.
     *
     * @param DataTable $dataTable
     * @return DataTableState
     */
    public static function fromDefaults(DataTable $dataTable): DataTableState
    {
        $state = new self($dataTable);
        $state->start = (int) $dataTable->getOption('start');
        $state->length = (int) $dataTable->getOption('pageLength');

        foreach ($dataTable->getOption('order') as $order) {
            $state->addOrderBy($dataTable->getColumn($order[0]), $order[1]);
        }

        return $state;
    }

    /**
     * Loads datatables state from a parameter bag on top of any existing settings.
     */
    public function applyParameters(array $parameters)
    {
        $this->draw   = (int) ($parameters['draw']   ?? $this->draw);

        $this->isCallback   = true;
        $this->isInitial    = (bool) ($parameters['start'] ?? $this->isInitial);
        $this->exporterName = $parameters['_exporter'] ?? null;

        $this->start  = (int) ($parameters['start']  ?? $this->start);
        $this->length = (int) ($parameters['length'] ?? $this->length);

        $search = $parameters['search'] ?? [];
        $this->setGlobalSearch($search['value'] ?? $this->globalSearch);

        $this->handleOrderBy($parameters);
        $this->handleSearch($parameters);
    }

    private function handleOrderBy(array $parameters)
    {
        if(array_key_exists('order', $parameters)){
            $this->orderBy = [];
            foreach ($parameters['order'] ?? [] as $order) {
                $column = $this->getColumn((int) $order['column']);
                $this->addOrderBy($column, $order['dir'] ?? 'asc');
            }
        }
    }

    private function handleSearch(array $parameters)
    {
        foreach ($parameters['columns'] ?? [] as $key => $search) {
            $column = $this->getColumn((int) $key);
            $value = $this->isInitial ? $search : $search['search']['value'];

            if ($column->isSearchable() && ('' !== trim($value))) {
                $this->setColumnSearch($column, $value);
            }
        }
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }

    public function isCallback(): bool
    {
        return $this->isCallback;
    }

    public function getDraw(): int
    {
        return $this->draw;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return $this
     */
    public function setStart(int $start): DataTableState
    {
        $this->start = $start;

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return $this
     */
    public function setLength(int $length): DataTableState
    {
        $this->length = $length;

        return $this;
    }

    public function getGlobalSearch(): string
    {
        return $this->globalSearch;
    }

    /**
     * @return $this
     */
    public function setGlobalSearch(string $globalSearch): DataTableState
    {
        $this->globalSearch = $globalSearch;

        return $this;
    }

    /**
     * @return $this
     */
    public function addOrderBy(AbstractColumn $column, string $direction = DataTable::SORT_ASCENDING): DataTableState
    {
        $this->orderBy[] = [$column, $direction];

        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return $this
     */
    public function setOrderBy(array $orderBy = []): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Returns an array of column-level searches.
     */
    public function getSearchColumns(): array
    {
        return $this->searchColumns;
    }

    /**
     * @return $this
     */
    public function setColumnSearch(AbstractColumn $column, string $search, bool $isRegex = false): self
    {
        $this->searchColumns[$column->getName()] = ['column' => $column, 'search' => $search, 'regex' => $isRegex];

        return $this;
    }

    public function getColumn(int $index): AbstractColumn
    {
        if ($index < 0 || $index >= count($this->columns)) {
            throw new InvalidArgumentException(sprintf('There is no column with index %d', $index));
        }

        return $this->columns[$index];
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getTransformer(): ?callable
    {
        return $this->transformer;
    }

    /**
     * @return string
     */
    public function getExporterName(): ?string
    {
        return $this->exporterName;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
