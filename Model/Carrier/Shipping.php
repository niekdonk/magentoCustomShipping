<?php

namespace Bas\CustomShipping\Model\Carrier;

use Bas\CustomShipping\Model\Order\Order;
use Bas\CustomShipping\Model\Order\Tyre;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Shipping extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'simpleshipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    protected $productRepository;


    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        ProductRepositoryInterface $productRepository,
        array $data = []
    )
    {
        $this->productRepository = $productRepository;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * get allowed methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);
        //TODO maybe set the carrier name here. Get this from api
        $method->setCarrierTitle("CARRIER NAME FROM API");

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $amount = $this->getShippingPrice($request);

        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        return $result;
    }

    /**
     * @return float
     */
    private function getShippingPrice(RateRequest $request)
    {
        $tyres = [];

        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                $product = $this->productRepository->getById($item->getProduct()->getId());
                array_push(
                    $tyres,
                    new Tyre(
                        $product->getData('sku'),
                        $product->getData('width'),
                        $product->getData('height'),
                        $product->getData('diameter'),
                        $product->getData('weight'),
                    ));//TODO find a better solution for this?
            }
        }

        $countryCode = $request->getDestCountryId();
        $postalCode = $request->getDestPostcode();

        $region = $countryCode . substr($postalCode, 0, 2);;

        if ($countryCode === "IE") {
            $region = $countryCode . $request->getDestCity();
        }

        if ($countryCode === "GB") {
            $pattern = '/(?=\d)/';
            $postalGB = preg_split($pattern, $postalCode, 2);
            $region = $countryCode . $postalGB[0];
        }

        $order = new Order($region, $tyres);

        //TODO sent this object to the api
        $json = json_encode($order);


        $shippingPrice = 123456789;

        return $shippingPrice;
    }
}
