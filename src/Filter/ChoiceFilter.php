<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceFilter extends AbstractFilter
{
    /** @var string */
    protected string $placeholder;

    /** @var array */
    protected array $choices = [];

    /**
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'template_html' => '@DataTables/Filter/select.html.twig',
                'template_js' => '@DataTables/Filter/select.js.twig',
                'placeholder' => null,
                'choices' => [],
            ])
            ->setAllowedTypes('placeholder', ['null', 'string'])
            ->setAllowedTypes('choices', ['array']);

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
     * @return array
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function isValidValue($value): bool
    {
        return array_key_exists($value, $this->choices);
    }
}
