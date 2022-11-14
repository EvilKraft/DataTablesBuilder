<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextFilter extends AbstractFilter
{
    /** @var string */
    protected string $placeholder;

    protected function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'template_html' => '@DataTables/Filter/text.html.twig',
                'template_js' => '@DataTables/Filter/text.js.twig',
                'placeholder' => null,
            ])
            ->setAllowedTypes('placeholder', ['null', 'string']);

        return $this;
    }

    /**
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isValidValue($value): bool
    {
        return true;
    }
}
