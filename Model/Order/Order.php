<?php


namespace customShipping\Model\Order;


class Order
{
    private string $region;

    /** @var Tyre[] */
    private array $tyres;

    /**
     * Order constructor.
     * @param string $region
     * @param array $tyres
     */
    public function __construct(string $region, array $tyres)
    {
        $this->region = $region;
        $this->tyres = $tyres;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    /**
     * @return Tyre[]
     */
    public function getTyres(): array
    {
        return $this->tyres;
    }

    /**
     * @param Tyre[]
     */
    public function setTyres(array $tyres): void
    {
        $this->tyres = $tyres;
    }


}
