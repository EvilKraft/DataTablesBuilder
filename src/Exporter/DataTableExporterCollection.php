<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Exporter;

use ArrayIterator;
use InvalidArgumentException;
use IteratorIterator;

/**
 * Holds the available DataTable exporters.
 */
class DataTableExporterCollection extends IteratorIterator
{
    /**
     * DataTableExporterCollection constructor.
     */
    public function __construct(DataTableExporterInterface ...$exporters)
    {
        parent::__construct(new ArrayIterator($exporters));
    }

    /**
     * Finds a DataTable exporter that matches the given name.
     *
     * @throws InvalidArgumentException
     */
    public function getByName(string $name): DataTableExporterInterface
    {
        foreach ($this->getInnerIterator() as $exporter) {
            if ($exporter->getName() === $name) {
                return $exporter;
            }
        }

        throw new InvalidArgumentException(sprintf("Cannot find a DataTable exporter named '%s'.", $name));
    }
}
