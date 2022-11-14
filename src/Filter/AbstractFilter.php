<?php
declare(strict_types=1);

namespace EvilKraft\DatatablesBuilder\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFilter
{
    /** @var string */
    protected string $template_html;

    /** @var string */
    protected string $template_js;

    /** @var string */
    protected string $operator;

    public function set(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        foreach ($resolver->resolve($options) as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver): self
    {
        $resolver->setDefaults([
            'template_html' => null,
            'template_js' => null,
            'operator' => 'CONTAINS',
        ]);

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateHtml():string
    {
        return $this->template_html;
    }

    /**
     * @return string
     */
    public function getTemplateJs():string
    {
        return $this->template_js;
    }

    /**
     * @return string
     */
    public function getOperator():string
    {
        return $this->operator;
    }

    /**
     * @param mixed $value
     */
    abstract public function isValidValue($value): bool;
}
