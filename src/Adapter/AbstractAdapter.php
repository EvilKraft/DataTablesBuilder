<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter;

use EvilKraft\DatatablesBuilder\Column\AbstractColumn;
use EvilKraft\DatatablesBuilder\DataTableState;
use Traversable;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @inheritDoc
     */
    final public function getData(DataTableState $state): ResultSetInterface
    {
        $query = new AdapterQuery($state);

        $this->prepareQuery($query);
        $propertyMap = $this->getPropertyMap($query);

        $rows = [];
        $transformer = $state->getTransformer();
        $identifier = $query->getIdentifierPropertyPath();
        foreach ($this->getResults($query) as $result) {
            $row = [];
            if (!empty($identifier)) {
                $row['DT_RowId']   = 'row_'.$result[$identifier] ?? null;
                $row['DT_RowData'] = ['pkey' => $result[$identifier] ?? null];
            }

            /** @var AbstractColumn $column */
            foreach ($propertyMap as list($column, $mapping)) {
                $value = ($mapping && array_key_exists($mapping, $result)) ? $result[$mapping] : null;
                $row[$column->getName()] = $column->transform($value, $result);
            }
            if (null !== $transformer) {
                $row = call_user_func($transformer, $row, $result);
            }
            $rows[] = $row;
        }

        return new ArrayResultSet($rows, $query->getTotalRows(), $query->getFilteredRows());
    }

    protected function getPropertyMap(AdapterQuery $query): array
    {
        $propertyMap = [];
        foreach ($query->getState()->getColumns() as $column) {
            $propertyMap[] = [$column, $column->getPropertyPath() ?? (empty($column->getField()) ? null : $this->mapPropertyPath($query, $column))];
        }

        return $propertyMap;
    }

    abstract protected function prepareQuery(AdapterQuery $query);

    /**
     * @param AdapterQuery $query
     * @param AbstractColumn $column
     * @return string|null
     */
    abstract protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column): ?string;

    abstract protected function getResults(AdapterQuery $query): Traversable;
}