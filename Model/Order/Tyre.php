<?php

namespace vendor\bastrucks\shipping\Model\Order;

class Tyre
{
    private String $sku;
    private float $width;
    private float $height;
    private float $diameter;
    private float $weight;

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * Tyre constructor.
     * @param string $sku
     * @param float $width
     * @param float $height
     * @param float $diameter
     */
    public function __construct(string $sku, float $width, float $height, float $diameter, float $weight)
    {
        $this->sku = $sku;
        $this->width = $width;
        $this->height = $height;
        $this->diameter = $diameter;
        $this->weight = $weight;
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * @param float $width
     */
    public function setWidth(float $width): void
    {
        $this->width = $width;
    }

    /**
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * @param float $height
     */
    public function setHeight(float $height): void
    {
        $this->height = $height;
    }

    /**
     * @return float
     */
    public function getDiameter(): float
    {
        return $this->diameter;
    }

    /**
     * @param float $diameter
     */
    public function setDiameter(float $diameter): void
    {
        $this->diameter = $diameter;
    }



}
