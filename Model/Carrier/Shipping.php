<?php

namespace bastrucks\shipping\Model\Carrier;

use bastrucks\shipping\Model\Order\Order;
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
        \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory,
        \Psr\Log\LoggerInterface                                    $logger,
        \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        ProductRepositoryInterface                                  $productRepository,
        array                                                       $data = []
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
                /* TODO wait to uncomment for the attributes names
                array_push(
                    $tyres,
                    new Tyre(
                        $product->getData('sku'),
                        $product->getData('width'),
                        $product->getData('height'),
                        $product->getData('diameter'),
                        $product->getData('weight'),
                        $product->getData('size'),
                    ));//TODO find a better solution for this?
                */
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

        //TODO set this url
        $url = 'http://1.2.3.4:8888/url/here';

        //TODO get price and carrier from this response
        $response = $this->makeCall($url, $json);

        //TODO get this from the response
        $shippingPrice = 123456789;

        /* TODO remove this */
        if ($countryCode === "NL") {
            return 69;
        }
        /* TODO remove this */

        return $shippingPrice;
    }

    private function makeCall($url, $json)
    {
        // Setup cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => $json
        ));

        // Send the request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === FALSE) {
            print_r("Hier gaat het fout ofzo");
            die(curl_error($ch));
        }

        // Decode the response
        $responseData = json_decode($response, TRUE);

        // Close the cURL handler
        curl_close($ch);

        // return the response TODO maybe return responsedata. Check this later
        return $response;
    }
}
