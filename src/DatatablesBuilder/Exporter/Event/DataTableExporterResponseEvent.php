<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Exporter\Event;

use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * DataTableExporterResponseEvent.
 */
class DataTableExporterResponseEvent extends Event
{
    /** @var ResponseInterface */
    private ResponseInterface $response;

    /**
     * DataTableExporterResponseEvent constructor.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
