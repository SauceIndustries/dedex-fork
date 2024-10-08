<?php

namespace DedexBundle\Entity\Ern43;

/**
 * Class representing ExtentType
 *
 * A Composite containing an Extent and a UnitOfMeasure.
 * XSD Type: Extent
 */
class ExtentType
{
    /**
     * @var float $__value
     */
    private $__value = null;

    /**
     * The UnitOfMeasure of the Extent. This is represented in an XML schema as an XML Attribute.
     *
     * @var string $unitOfMeasure
     */
    private $unitOfMeasure = null;

    /**
     * Construct
     *
     * @param float $value
     */
    public function __construct($value)
    {
        $this->value($value);
    }

    /**
     * Gets or sets the inner value
     *
     * @param float $value
     * @return float
     */
    public function value()
    {
        if ($args = func_get_args()) {
            $this->__value = $args[0];
        }
        return $this->__value;
    }

    /**
     * Gets a string value
     *
     * @return string
     */
    public function __toString()
    {
        return strval($this->__value);
    }

    /**
     * Gets as unitOfMeasure
     *
     * The UnitOfMeasure of the Extent. This is represented in an XML schema as an XML Attribute.
     *
     * @return string
     */
    public function getUnitOfMeasure()
    {
        return $this->unitOfMeasure;
    }

    /**
     * Sets a new unitOfMeasure
     *
     * The UnitOfMeasure of the Extent. This is represented in an XML schema as an XML Attribute.
     *
     * @param string $unitOfMeasure
     * @return self
     */
    public function setUnitOfMeasure($unitOfMeasure)
    {
        $this->unitOfMeasure = $unitOfMeasure;
        return $this;
    }
}

