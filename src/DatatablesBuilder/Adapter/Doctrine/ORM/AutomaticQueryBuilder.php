<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Adapter\Doctrine\ORM;

use Doctrine\Persistence\Mapping\MappingException;
use EvilKraft\DataTablesBuilder\Column\AbstractColumn;
use EvilKraft\DataTablesBuilder\DataTableState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

/**
 * AutomaticQueryBuilder.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class AutomaticQueryBuilder implements QueryBuilderProcessorInterface
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    /** @var ClassMetadata */
    private ClassMetadata $metadata;

    /** @var string */
    private string $entityName;

    /** @var string */
    private string $entityShortName;

    /** @var array */
    private array $selectColumns = [];

    /** @var array */
    private array $joins = [];

    /**
     * AutomaticQueryBuilder constructor.
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $metadata)
    {
        $this->em = $em;
        $this->metadata = $metadata;

        $this->entityName = $this->metadata->getName();
        $this->entityShortName = mb_strtolower($this->metadata->getReflectionClass()->getShortName());
    }


    public function process(QueryBuilder $queryBuilder, DataTableState $state)
    {
        if (empty($this->selectColumns) && empty($this->joins)) {
            foreach ($state->getColumns() as $column) {
                $this->processColumn($column);
            }
        }
        $queryBuilder->from($this->entityName, $this->entityShortName);
        $this->setSelectFrom($queryBuilder);
        $this->setJoins($queryBuilder);
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     */
    protected function processColumn(AbstractColumn $column)
    {
        $field = $column->getField();

        // Default to the column name if that corresponds to a field mapping
        if (!isset($field) && isset($this->metadata->fieldMappings[$column->getName()])) {
            $field = $column->getName();
        }
        if (null !== $field) {
            $this->addSelectColumns($column, $field);
        }
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     */
    private function addSelectColumns(AbstractColumn $column, string $field)
    {
        $currentPart = $this->entityShortName;
        $currentAlias = $currentPart;
        $metadata = $this->metadata;

        $parts = explode('.', $field);

        if (count($parts) > 1 && $parts[0] === $currentPart) {
            array_shift($parts);
        }

        if (sizeof($parts) > 1 && $metadata->hasField(implode('.', $parts))) {
            $this->addSelectColumn($currentAlias, implode('.', $parts));
        } else {
            while (count($parts) > 1) {
                $previousPart = $currentPart;
                $previousAlias = $currentAlias;
                $currentPart = array_shift($parts);
                $currentAlias = ($previousPart === $this->entityShortName ? '' : $previousPart . '_') . $currentPart;

                $this->joins[$previousAlias . '.' . $currentPart] = ['alias' => $currentAlias, 'type' => 'join'];

                $metadata = $this->setIdentifierFromAssociation($currentAlias, $currentPart, $metadata);
            }

            $this->addSelectColumn($currentAlias, $this->getIdentifier($metadata));
            $this->addSelectColumn($currentAlias, $parts[0]);
        }
    }

    private function addSelectColumn($columnTableName, $data): void
    {
        if (isset($this->selectColumns[$columnTableName])) {
            if (!in_array($data, $this->selectColumns[$columnTableName], true)) {
                $this->selectColumns[$columnTableName][] = $data;
            }
        } else {
            $this->selectColumns[$columnTableName][] = $data;
        }

    }

    private function getIdentifier(ClassMetadata $metadata): ?string
    {
        $identifiers = $metadata->getIdentifierFieldNames();

        return array_shift($identifiers);
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     */
    private function setIdentifierFromAssociation(string $association, string $key, ClassMetadata $metadata): ClassMetadata
    {
        $targetEntityClass = $metadata->getAssociationTargetClass($key);

        /** @var ClassMetadata $targetMetadata */
        $targetMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetEntityClass);
        $this->addSelectColumn($association, $this->getIdentifier($targetMetadata));

        return $targetMetadata;
    }

    private function setSelectFrom(QueryBuilder $qb): void
    {
        foreach ($this->selectColumns as $key => $value) {
            if (false === empty($key)) {
                $qb->addSelect('partial ' . $key . '.{' . implode(',', $value) . '}');
            } else {
                $qb->addSelect($value);
            }
        }

    }

    private function setJoins(QueryBuilder $qb): void
    {
        foreach ($this->joins as $key => $value) {
            $qb->{$value['type']}($key, $value['alias']);
        }
    }
}
