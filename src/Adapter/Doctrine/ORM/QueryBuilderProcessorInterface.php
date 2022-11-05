<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Adapter\Doctrine\ORM;

use EvilKraft\DataTablesBuilder\DataTableState;
use Doctrine\ORM\QueryBuilder;

/**
 * QueryBuilderProviderInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface QueryBuilderProcessorInterface
{
    public function process(QueryBuilder $queryBuilder, DataTableState $state);
}
