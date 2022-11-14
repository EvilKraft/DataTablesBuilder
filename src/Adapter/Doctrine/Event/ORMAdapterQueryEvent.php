<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Adapter\Doctrine\Event;

use Doctrine\ORM\Query;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ORMAdapterQueryEvent extends Event
{
    /** @var Query */
    protected Query $query;

    /**
     * ORMAdapterQueryEvent constructor.
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
