<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter\Doctrine;

use EvilKraft\DatatablesBuilder\Adapter\AbstractAdapter;
use EvilKraft\DatatablesBuilder\Adapter\AdapterQuery;
use EvilKraft\DatatablesBuilder\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use EvilKraft\DatatablesBuilder\Adapter\Doctrine\ORM\AutomaticQueryBuilder;
use EvilKraft\DatatablesBuilder\Adapter\Doctrine\ORM\QueryBuilderProcessorInterface;
use EvilKraft\DatatablesBuilder\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use EvilKraft\DatatablesBuilder\Column\AbstractColumn;
use EvilKraft\DatatablesBuilder\DataTableState;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

/**
 * ORMAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class ORMAdapter extends AbstractAdapter
{
    /** @var EntityManager */
    protected EntityManager $manager;

    /** @var ClassMetadata */
    protected ClassMetadata $metadata;

    /** @var int */
    private int $hydrationMode;

    /** @var QueryBuilderProcessorInterface[] */
    private array $queryBuilderProcessors;

    /** @var QueryBuilderProcessorInterface[] */
    protected array $criteriaProcessors;

    /**
     * DoctrineAdapter constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->afterConfiguration($options);
    }

    /**
     * @param mixed $processor
     */
    public function addCriteriaProcessor($processor)
    {
        $this->criteriaProcessors[] = $this->normalizeProcessor($processor);
    }

    /**
     * @throws MappingException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     * @throws \ReflectionException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function prepareQuery(AdapterQuery $query)
    {
        $state = $query->getState();

        $query->set('qb', $builder = $this->createQueryBuilder($state));
        $query->set('rootAlias', $rootAlias = $builder->getDQLPart('from')[0]->getAlias());

        // Provide default field mappings if needed
        foreach ($state->getColumns() as $column) {
            if (null === $column->getField() && isset($this->metadata->fieldMappings[$name = $column->getName()])) {
                $column->setOption('field', "$rootAlias.$name");
            }
        }

        /** @var Query\Expr\From $fromClause */
        $fromClause = $builder->getDQLPart('from')[0];
        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierFieldName()}";
        $query->setTotalRows($this->getCount($builder, $identifier));

        // Get record count after filtering
        $this->buildCriteria($builder, $state);
        $query->setFilteredRows($this->getCount($builder, $identifier));

        // Perform mapping of all referred fields and implied fields
        $aliases = $this->getAliases($query);
        $query->set('aliases', $aliases);
        $query->setIdentifierPropertyPath($this->mapFieldToPropertyPath($identifier, $aliases));
    }

    /**
     * @throws \ReflectionException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     */
    protected function getAliases(AdapterQuery $query): array
    {
        /** @var QueryBuilder $builder */
        $builder = $query->get('qb');
        $aliases = [];

        /** @var Query\Expr\From $from */
        foreach ($builder->getDQLPart('from') as $from) {
            $aliases[$from->getAlias()] = [null, $this->manager->getMetadataFactory()->getMetadataFor($from->getFrom())];
        }

        // Alias all joins
        foreach ($builder->getDQLPart('join') as $joins) {
            /** @var Query\Expr\Join $join */
            foreach ($joins as $join) {
                if (false === mb_strstr($join->getJoin(), '.')) {
                    continue;
                }

                list($origin, $target) = explode('.', $join->getJoin());

                $mapping = $aliases[$origin][1]->getAssociationMapping($target);
                $aliases[$join->getAlias()] = [$join->getJoin(), $this->manager->getMetadataFactory()->getMetadataFor($mapping['targetEntity'])];
            }
        }

        return $aliases;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column): string
    {
        return $this->mapFieldToPropertyPath($column->getField(), $query->get('aliases'));
    }

    protected function getResults(AdapterQuery $query): Traversable
    {
        /** @var QueryBuilder $builder */
        $builder = $query->get('qb');
        $state = $query->getState();

        // Apply definitive view state for current 'page' of the table
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable()) {
                $builder->addOrderBy($column->getOrderField(), $direction);
            }
        }
        if ($state->getLength() > 0) {
            $builder
                ->setFirstResult($state->getStart())
                ->setMaxResults($state->getLength())
            ;
        }

        $query = $builder->getQuery();
        $event = new ORMAdapterQueryEvent($query);
        $state->getEventDispatcher()->dispatch($event, ORMAdapterEvents::PRE_QUERY);

        foreach ($query->toIterable([], $this->hydrationMode) as $result) {
            yield $entity = array_values($result)[0];
            if (AbstractQuery::HYDRATE_OBJECT === $this->hydrationMode) {
                $this->manager->detach($entity);
            }
        }
    }

    protected function buildCriteria(QueryBuilder $queryBuilder, DataTableState $state)
    {
        foreach ($this->criteriaProcessors as $provider) {
            $provider->process($queryBuilder, $state);
        }
    }

    protected function createQueryBuilder(DataTableState $state): QueryBuilder
    {
        $queryBuilder = $this->manager->createQueryBuilder();

        // Run all query builder processors in order
        foreach ($this->queryBuilderProcessors as $processor) {
            $processor->process($queryBuilder, $state);
        }

        return $queryBuilder;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function getCount(QueryBuilder $queryBuilder, $identifier): int
    {
        $qb = clone $queryBuilder;

        $qb->resetDQLPart('orderBy');
        $gb = $qb->getDQLPart('groupBy');
        if (empty($gb) || !$this->hasGroupByPart($identifier, $gb)) {
            $qb->select($qb->expr()->count($identifier));
        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select($qb->expr()->countDistinct($identifier));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $identifier
     * @param Query\Expr\GroupBy[] $gbList
     *
     * @return bool
     */
    protected function hasGroupByPart($identifier, array $gbList): bool
    {
        foreach ($gbList as $gb) {
            if (in_array($identifier, $gb->getParts(), true)) {
                return true;
            }
        }

        return false;
    }

    protected function mapFieldToPropertyPath(string $field, array $aliases = []): string
    {
        $parts = explode('.', $field);
        if (count($parts) < 2) {
            throw new InvalidArgumentException(sprintf("Field name '%s' must consist at least of an alias and a field separated with a period", $field));
        }

        $origin = $parts[0];
        array_shift($parts);
        $target = array_reverse($parts);
        $path = $target;

        $current = isset($aliases[$origin]) ? $aliases[$origin][0] : null;

        while (null !== $current) {
            list($origin, $target) = explode('.', $current);
            $path[] = $target;
            $current = $aliases[$origin][0];
        }

        if (AbstractQuery::HYDRATE_ARRAY === $this->hydrationMode) {
            return '[' . implode('][', array_reverse($path)) . ']';
        } else {
            return implode('.', array_reverse($path));
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $providerNormalizer = function (Options $options, $value) {
            return array_map([$this, 'normalizeProcessor'], (array) $value);
        };

        $resolver
            ->setDefaults([
                'hydrate' => AbstractQuery::HYDRATE_OBJECT,
                'query' => [],
                'criteria' => function (Options $options) {
                    return [new SearchCriteriaProvider()];
                },
            ])
            ->setRequired('entity')
            ->setAllowedTypes('entity', ['string'])
            ->setAllowedTypes('hydrate', 'int')
            ->setAllowedTypes('query', [QueryBuilderProcessorInterface::class, 'array', 'callable'])
            ->setAllowedTypes('criteria', [QueryBuilderProcessorInterface::class, 'array', 'callable', 'null'])
            ->setNormalizer('query', $providerNormalizer)
            ->setNormalizer('criteria', $providerNormalizer)
        ;
    }

    /**
     * @throws Exception
     */
    protected function afterConfiguration(array $options): void
    {
        $this->metadata = $this->manager->getClassMetadata($options['entity']);

        if (empty($options['query'])) {
            $options['query'] = [new AutomaticQueryBuilder($this->manager, $this->metadata)];
        }

        // Set options
        $this->hydrationMode = $options['hydrate'];
        $this->queryBuilderProcessors = $options['query'];
        $this->criteriaProcessors = $options['criteria'];
    }

    /**
     * @param callable|QueryBuilderProcessorInterface $provider
     *
     * @return QueryBuilderProcessorInterface
     */
    private function normalizeProcessor($provider): QueryBuilderProcessorInterface
    {
        if ($provider instanceof QueryBuilderProcessorInterface) {
            return $provider;
        } elseif (is_callable($provider)) {
            return new class($provider) implements QueryBuilderProcessorInterface {
                private $callable;

                public function __construct(callable $value)
                {
                    $this->callable = $value;
                }

                public function process(QueryBuilder $queryBuilder, DataTableState $state)
                {
                    return call_user_func($this->callable, $queryBuilder, $state);
                }
            };
        }

        throw new InvalidArgumentException('Provider must be a callable or implement QueryBuilderProcessorInterface');
    }
}
