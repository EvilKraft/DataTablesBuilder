<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Adapter\Elasticsearch;

use EvilKraft\DataTablesBuilder\Adapter\AbstractAdapter;
use EvilKraft\DataTablesBuilder\Adapter\AdapterQuery;
use EvilKraft\DataTablesBuilder\Column\AbstractColumn;
use EvilKraft\DataTablesBuilder\DataTableState;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\MultiMatch;
use Elastica\Search;
use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ElasticaAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ElasticaAdapter extends AbstractAdapter
{
    /** @var array */
    private array $clientSettings = [];

    /** @var array */
    private array $indices = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->clientSettings = $options['client'];
        $this->indices = (array) $options['index'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareQuery(AdapterQuery $query)
    {
        if (!class_exists(Client::class)) {
            throw new Exception('Install ruflin/elastica to use the ElasticaAdapter');
        }
        $query->set('client', new Client($this->clientSettings));

        foreach ($query->getState()->getColumns() as $column) {
            if (null === $column->getField()) {
                $column->setOption('field', $column->getName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column): string
    {
        return "[{$column->getField()}]";
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults(AdapterQuery $query): \Traversable
    {
        $state = $query->getState();
        $search = new Search($query->get('client'));
        $search->addIndices($this->indices);

        $q = $this->buildQuery($state);
        if ($state->getLength() > 0) {
            $q->setFrom($state->getStart())->setSize($state->getLength());
        }
        $this->applyOrdering($q, $state);

        $resultSet = $search->search($q);
        $query->setTotalRows($resultSet->getTotalHits());
        $query->setFilteredRows($search->count());

        foreach ($resultSet->getResults() as $result) {
            yield $result->getData();
        }
    }

    protected function buildQuery(DataTableState $state): Query
    {
        $q = new Query();
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $fields = [];
            foreach ($state->getColumns() as $column) {
                if ($column->isGlobalSearchable()) {
                    $fields[] = $column->getField();
                }
            }
            $multimatch = (new MultiMatch())
                ->setQuery($globalSearch)
                ->setFields($fields)
            ;
            $q->setQuery($multimatch);
        }

        return $q;
    }

    protected function applyOrdering(Query $query, DataTableState $state)
    {
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable() && $orderField = $column->getOrderField()) {
                $query->addSort([$orderField => ['order' => $direction]]);
            }
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'client' => [],
                'index' => [],
            ])
            ->setRequired(['client', 'index'])
            ->setAllowedTypes('client', 'array')
            ->setAllowedTypes('index', ['string', 'array'])
        ;
    }
}
