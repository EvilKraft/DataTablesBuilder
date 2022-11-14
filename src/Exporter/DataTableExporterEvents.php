<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Exporter;

/**
 * Available events.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
final class DataTableExporterEvents
{
    /**
     * The PRE_RESPONSE event is dispatched before sending
     * the BinaryFileResponse to the user.
     *
     * Note that the file is accessible through the Response object.
     * Both the file and the Response can be modified before being sent.
     *
     * @Event("App\Module\Core\Infrastructure\Domain\DatatablesBuilder\Exporter\Event\DataTableExporterResponseEvent")
     */
    const PRE_RESPONSE = 'datatables.exporter.pre_response';
}
