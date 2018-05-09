<?php

namespace Accesto\Component\Payum\PayU\Model;

/**
 * Class Product
 * @package Accesto\Component\Payum\PayU\Model
 */
class Product
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var float
     */
    protected $unitPrice;

    /**
     * @var int
     */
    protected $quantity = 1;

    public function __construct($name = null, $unitPrice = null, $quantity = null)
    {
        $this->name = $name;
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * @param float $unitPrice
     */
    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function toArray()
    {
        return array(
            'name' => $this->getName(),
            'unitPrice' => $this->getUnitPrice(),
            'quantity' => $this->getQuantity()
        );
    }
}
