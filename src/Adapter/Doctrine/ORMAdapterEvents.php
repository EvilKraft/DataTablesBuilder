<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter\Doctrine;

/**
 * Available events.
 */
final class ORMAdapterEvents
{
    /**
     * The PRE_QUERY event is dispatched after the QueryBuilder
     * built the Query and before the iteration starts.
     *
     * It can be useful to configure the cache.
     *
     * @Event("EvilKraft\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent")
     */
    const PRE_QUERY = 'datatables.ormadapter.pre_query';
}
