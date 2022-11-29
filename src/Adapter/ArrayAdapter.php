<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter;

use EvilKraft\DatatablesBuilder\Column\AbstractColumn;
use EvilKraft\DatatablesBuilder\DataTableState;
use Generator;
use Throwable;
use function mb_strtolower;
use function usort;


/**
 * ArrayAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ArrayAdapter implements AdapterInterface
{
    /** @var array */
    private array $data = [];

    private string $identifier;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $this->data = $options;

        $this->identifier = array_key_first(current($this->data));
    }

    public function getData(DataTableState $state): ResultSetInterface
    {
        // very basic implementation of sorting
        try {
            $oc = $state->getOrderBy()[0][0]->getName();
            $oo = mb_strtolower($state->getOrderBy()[0][1]);

            usort($this->data, function ($a, $b) use ($oc, $oo) {
                if ('desc' === $oo) {
                    return $b[$oc] <=> $a[$oc];
                }

                return $a[$oc] <=> $b[$oc];
            });
        } catch (Throwable $exception) {
            // ignore exception
        }

        $map = [];
        /** @var AbstractColumn $column */
        foreach ($state->getColumns() as $column) {
            unset($propertyPath);
            if (empty($propertyPath = $column->getPropertyPath()) && !empty($field = $column->getField() ?? $column->getName())) {
                $propertyPath = $field;
            }

            if (null !== $propertyPath) {
                $map[$column->getName()] = $propertyPath;
            }
        }

        $data = iterator_to_array($this->processData($state, $this->data, $map));

        $length = $state->getLength();
        $page = $length > 0 ? array_slice($data, $state->getStart(), $state->getLength()) : $data;

        return new ArrayResultSet($page, count($this->data), count($data));
    }

    /**
     * @param DataTableState $state
     * @param array $data
     * @param array $map
     * @return Generator
     */
    protected function processData(DataTableState $state, array $data, array $map): Generator
    {
        $transformer = $state->getTransformer();
        $search = $state->getGlobalSearch() ?: '';
        foreach ($data as $result) {
            if ($row = $this->processRow($state, $result, $map, $search)) {
                if (null !== $transformer) {
                    $row = call_user_func($transformer, $row, $result);
                }

                yield $row;
            }
        }
    }

    /**
     * @param DataTableState $state
     * @param array $result
     * @param array $map
     * @param string $search
     * @return array|null
     */
    protected function processRow(DataTableState $state, array $result, array $map, string $search): ?array
    {
        $row = [];

        if(!empty($this->identifier)){
            $row['DT_RowId']   = 'row_'.$result[$this->identifier] ?? null;
            $row['DT_RowData'] = ['pkey' => $result[$this->identifier] ?? null];
        }

        $match = empty($search);
        foreach ($state->getColumns() as $column) {
            $propertyPath = $map[$column->getName()]??null;
            $value = (!empty($propertyPath) && array_key_exists($propertyPath, $result)) ? $result[$column->getName()] : null;
            $value = $column->transform($value, $result);
            if (!$match) {
                $match = (false !== mb_stripos($value, $search));
            }
            $row[$column->getName()] = $value;
        }

        foreach ($state->getSearchColumns() as $searchInfo) {
            /** @var AbstractColumn $column */
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];

            if (!$match) {
                $match = (false !== mb_stripos($row[$column->getName()], $search));
            }
        }

        return $match ? $row : null;
    }
}
