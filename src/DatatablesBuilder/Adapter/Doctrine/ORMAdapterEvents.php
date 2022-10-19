<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Adapter\Doctrine;

/**
 * Available events.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
final class ORMAdapterEvents
{
    /**
     * The PRE_QUERY event is dispatched after the QueryBuilder
     * built the Query and before the iteration starts.
     *
     * It can be useful to configure the cache.
     *
     * @Event("Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent")
     */
    const PRE_QUERY = 'omines_datatables.ormadapter.pre_query';
}
