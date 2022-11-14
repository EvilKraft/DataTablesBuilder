<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter;

use EvilKraft\DatatablesBuilder\DataTableState;

/**
 * AdapterQuery encapsulates a single request to an adapter, used by the AbstractAdapter base class. It is generically
 * used as a context container allowing temporary data to be stored.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class AdapterQuery
{
    /** @var DataTableState */
    private DataTableState $state;

    /** @var int|null */
    private ?int $totalRows;

    /** @var int|null */
    private ?int $filteredRows;

    /** @var string|null */
    private ?string $identifierPropertyPath;

    /** @var array<string, mixed> */
    private array $data;

    /**
     * AdapterQuery constructor.
     */
    public function __construct(DataTableState $state)
    {
        $this->state = $state;
    }

    public function getState(): DataTableState
    {
        return $this->state;
    }

    /**
     * @return int|null
     */
    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    /**
     * @param int|null $totalRows
     * @return $this
     */
    public function setTotalRows(?int $totalRows): self
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFilteredRows(): ?int
    {
        return $this->filteredRows;
    }

    /**
     * @param int|null $filteredRows
     * @return $this
     */
    public function setFilteredRows(?int $filteredRows): self
    {
        $this->filteredRows = $filteredRows;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentifierPropertyPath(): ?string
    {
        return $this->identifierPropertyPath;
    }

    /**
     * @param string|null $identifierPropertyPath
     * @return $this
     */
    public function setIdentifierPropertyPath(?string $identifierPropertyPath): self
    {
        $this->identifierPropertyPath = $identifierPropertyPath;

        return $this;
    }

    /**
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }
}
