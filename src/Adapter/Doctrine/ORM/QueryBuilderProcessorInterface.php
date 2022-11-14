<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter\Doctrine\ORM;

use EvilKraft\DatatablesBuilder\DataTableState;
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
