<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionButtonsColumn extends TextColumn
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'className'        => 'action_btns_container',
                'title'            => 'Actions',
                'orderable'        => false,
                'searchable'       => false,
                'globalSearchable' => false,
            ])
        ;

        return $this;
    }
}