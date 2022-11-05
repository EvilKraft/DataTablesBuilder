<?php
declare(strict_types=1);

namespace EvilKraft\DataTablesBuilder\Column;

use EvilKraft\DataTablesBuilder\Filter\AbstractFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractColumn
{
    /** @var array<string, OptionsResolver> */
    private static array $resolversByClass = [];

    /** @var string */
    private string $name;

    /** @var int */
    private int $index;

    /** @var string */
    private string $dataTableName;

    /** @var array<string, mixed> */
    protected array $options;

    public function initialize(string $name, int $index, array $options, string $dataTableName)
    {
        $this->name = $name;
        $this->index = $index;
        $this->dataTableName = $dataTableName;

        $class = get_class($this);
        if (!isset(self::$resolversByClass[$class])) {
            self::$resolversByClass[$class] = new OptionsResolver();
            $this->configureOptions(self::$resolversByClass[$class]);
        }
        $this->options = self::$resolversByClass[$class]->resolve($options);
    }

    /**
     * The transform function is responsible for converting column-appropriate input to a datatables-usable type.
     *
     * @param mixed|null $value The single value of the column, if mapping makes it possible to derive one
     * @param mixed|null $context All relevant data of the entire row
     * @return mixed
     */
    public function transform($value = null, $context = null)
    {
        $data = $this->getData();
        if (is_callable($data)) {
            $value = call_user_func($data, $context, $value);
        } elseif (null === $value) {
            $value = $data;
        }

        return $this->render($this->normalize($value), $context);
    }

    /**
     * Apply final modifications before rendering to result.
     *
     * @param mixed $value
     * @param mixed $context All relevant data of the entire row
     * @return mixed|string
     */
    protected function render($value, $context)
    {
        if (is_string($render = $this->options['render'])) {
            return sprintf($render, $value);
        } elseif (is_callable($render)) {
            return call_user_func($render, $value, $context);
        }

        return $value;
    }

    /**
     * The normalize function is responsible for converting parsed and processed data to a datatables-appropriate type.
     *
     * @param mixed $value The single value of the column
     * @return mixed
     */
    abstract public function normalize($value);

    /**
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver): self
    {
        $resolver
            ->setDefaults([
                'title'            => null,
                'data'             => null,
                'field'            => null,
                'propertyPath'     => null,
                'visible'          => true,
                'orderable'        => true,
                'orderField'       => null,
                'searchable'       => null,
                'globalSearchable' => null,
                'filter'           => null,
                'className'        => null,
                'render'           => null,
                'leftExpr'         => null,
                'operator'         => '=',
                'rightExpr'        => null,
            ])
            ->setAllowedTypes('title', ['null', 'string'])
            ->setAllowedTypes('data', ['null', 'string', 'callable'])
            ->setAllowedTypes('field', ['null', 'string'])
            ->setAllowedTypes('propertyPath', ['null', 'string'])
            ->setAllowedTypes('visible', 'boolean')
            ->setAllowedTypes('orderable', ['null', 'boolean'])
            ->setAllowedTypes('orderField', ['null', 'string'])
            ->setAllowedTypes('searchable', ['null', 'boolean'])
            ->setAllowedTypes('globalSearchable', ['null', 'boolean'])
            ->setAllowedTypes('filter', ['null', AbstractFilter::class])
            ->setAllowedTypes('className', ['null', 'string'])
            ->setAllowedTypes('render', ['null', 'string', 'callable'])
            ->setAllowedTypes('operator', ['string'])
            ->setAllowedTypes('leftExpr', ['null', 'string', 'callable'])
            ->setAllowedTypes('rightExpr', ['null', 'string', 'callable'])
        ;

        return $this;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->options['title'] ?? "$this->dataTableName.columns.{$this->getName()}";
    }

    /**
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->options['field'];
    }

    /**
     * @return string|null
     */
    public function getPropertyPath(): ?string
    {
        return $this->options['propertyPath'];
    }

    /**
     * @return callable|string|null
     */
    public function getData()
    {
        return $this->options['data'];
    }

    public function isVisible(): bool
    {
        return $this->options['visible'];
    }

    public function isSearchable(): bool
    {
        return $this->options['searchable'] ?? !empty($this->getField());
    }

    public function isOrderable(): bool
    {
        return $this->options['orderable'] ?? !empty($this->getOrderField());
    }

    /**
     * @return AbstractFilter
     */
    public function getFilter(): AbstractFilter
    {
        return $this->options['filter'];
    }

    /**
     * @return string|null
     */
    public function getOrderField(): ?string
    {
        return $this->options['orderField'] ?? $this->getField();
    }

    public function isGlobalSearchable(): bool
    {
        return $this->options['globalSearchable'] ?? $this->isSearchable();
    }

    /**
     * @return string
     */
    public function getLeftExpr(): ?string
    {
        $leftExpr = $this->options['leftExpr'];
        if (null === $leftExpr) {
            return $this->getField();
        }
        if (is_callable($leftExpr)) {
            return call_user_func($leftExpr, $this->getField());
        }

        return $leftExpr;
    }

    /**
     * @return mixed
     */
    public function getRightExpr($value)
    {
        $rightExpr = $this->options['rightExpr'];
        if (null === $rightExpr) {
            return $value;
        }
        if (is_callable($rightExpr)) {
            return call_user_func($rightExpr, $value);
        }

        return $rightExpr;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->options['operator'];
    }

    /**
     * @return string
     */
    public function getClassName(): ?string
    {
        return $this->options['className'];
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isValidForSearch(string $value): bool
    {
        return true;
    }
}