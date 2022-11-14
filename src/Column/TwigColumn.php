<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Column;

use EvilKraft\DatatablesBuilder\Renderer\DatatableRendererInterface;
use EvilKraft\DatatablesBuilder\Renderer\TwigRenderer;
use Exception;
use InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

/**
 * TwigColumn.
 *
 */
class TwigColumn extends AbstractColumn
{
    /** @var Environment */
    protected Environment $twig;

    /**
     * TwigColumn constructor.
     * @throws Exception
     */
    public function __construct(DataTableRendererInterface $renderer)
    {
        if(!($renderer instanceof TwigRenderer)){
            throw new InvalidArgumentException('You must use instanceof of '.TwigRenderer::class);
        }

        if (null === ($this->twig = $renderer->getTwig())) {
            throw new Exception('You must have Twig installed to use '.static::class);
        }
    }


    /**
     * {@inheritdoc}
     * @throws Exception
     */
    protected function render($value, $context)
    {
        return $this->twig->render($this->getTemplate(), [
            'row' => $context,
            'value' => $value,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): TwigColumn
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired('template')
            ->setAllowedTypes('template', 'string')
        ;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->options['template'];
    }
}
