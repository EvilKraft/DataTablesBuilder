<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Adapter\MongoDB;

use EvilKraft\DataTablesBuilder\Adapter\AbstractAdapter;
use EvilKraft\DataTablesBuilder\Adapter\AdapterQuery;
use EvilKraft\DataTablesBuilder\Column\AbstractColumn;
use EvilKraft\DataTablesBuilder\DataTable;
use EvilKraft\DataTablesBuilder\DataTableState;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

/**
 * MongoDBAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class MongoDBAdapter extends AbstractAdapter
{
    const SORT_MAP = [
        DataTable::SORT_ASCENDING => 1,
        DataTable::SORT_DESCENDING => -1,
    ];

    /** @var Collection */
    private Collection $collection;

    /** @var array */
    private array $filters;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->collection = $options['collection'];
        $this->filters = $options['filters'];
    }

    protected function prepareQuery(AdapterQuery $query)
    {
        foreach ($query->getState()->getColumns() as $column) {
            if (null === $column->getField()) {
                $column->setOption('field', $column->getName());
            }
        }

        $query->setTotalRows($this->collection->countDocuments());
    }

    /**
     * {@inheritdoc}
     */
    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column): string
    {
        return '[' . implode('][', explode('.', $column->getField())) . ']';
    }

    protected function getResults(AdapterQuery $query): Traversable
    {
        $state = $query->getState();

        $filter = $this->buildFilter($state);
        $options = $this->buildOptions($state);

        $query->setFilteredRows($this->collection->countDocuments($filter));
        $cursor = $this->collection->find($filter, $options);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);

        /** @var BSONDocument $result */
        foreach ($cursor as $result) {
            array_walk_recursive($result, function (&$value) {
                if ($value instanceof UTCDateTime) {
                    $value = $value->toDateTime();
                }
            });

            yield $result;
        }
    }

    private function buildFilter(DataTableState $state): array
    {
        $filter = $this->filters;
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            foreach ($state->getColumns() as $column) {
                if ($column->isGlobalSearchable()) {
                    $filter[] = [$column->getField() => new Regex($globalSearch, 'i')];
                }
            }
            $filter = ['$or' => $filter];
        }

        return $filter;
    }

    private function buildOptions(DataTableState $state): array
    {
        $options = [
            'limit' => $state->getLength(),
            'skip' => $state->getStart(),
            'sort' => [],
        ];

        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable() && $orderField = $column->getOrderField()) {
                $options['sort'][$orderField] = self::SORT_MAP[$direction];
            }
        }

        return $options;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'filters' => [],
            ])
            ->setRequired(['collection'])
            ->setAllowedTypes('collection', Collection::class)
            ->setAllowedTypes('filters', 'array')
        ;
    }
}
