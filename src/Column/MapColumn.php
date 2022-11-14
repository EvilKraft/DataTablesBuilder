<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MapColumn.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class MapColumn extends TextColumn
{
    /**
     * {@inheritdoc}
     */
    public function normalize($value): string
    {
        return parent::normalize($this->options['map'][$value] ?? $this->options['default'] ?? $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): MapColumn
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'default' => null,
                'map' => null,
            ])
            ->setAllowedTypes('default', ['null', 'string'])
            ->setAllowedTypes('map', 'array')
            ->setRequired('map')
        ;

        return $this;
    }
}
