<?php

namespace bastrucks\shipping\Model\Carrier;

use bastrucks\shipping\Model\Order\Order;
use bastrucks\shipping\Model\Order\Tyre;
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

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $shipping = $this->getShipping($request);
        if (!isset($shipping) || !$shipping) {
            //when server error or no shipping prices available
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setErrorMessage(__(
                    'Shipping is not available at this moment. To use shipping, please contact us.'
                )
            );
            return $error;
        }

        $amount = $shipping['price'];
        $method->setCarrierTitle($shipping['carrier']);

        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        return $result;
    }

    /**
     * @return float
     */
    private function getShipping(RateRequest $request)
    {
        $tyres = [];

        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                $product = $this->productRepository->getById($item->getProduct()->getId());

                if ($product->getData($this->getConfigData('width')) === null ||
                    $product->getData($this->getConfigData('height')) === null ||
                    $product->getData($this->getConfigData('diameter')) === null ||
                    $product->getData($this->getConfigData('weight')) === null ||
                    $product->getData($this->getConfigData('size')) === null) {

                    return false;
                }

                array_push(
                    $tyres,
                    new Tyre(
                        $product->getData('sku'),
                        $product->getData($this->getConfigData('width')),
                        $product->getData($this->getConfigData('height')),
                        $product->getData($this->getConfigData('diameter')),
                        $product->getData($this->getConfigData('weight')),
                        $product->getData($this->getConfigData('size')),
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
            //gets the postalcode before the numbers start
            $pattern = '/(?=\d)/';
            $postalGB = preg_split($pattern, $postalCode, 2);
            $region = $countryCode . $postalGB[0];
        }

        $order = new Order($region, $tyres);

        //TODO sent this object to the api
        $json = json_encode($order);

        $url = $this->getConfigData('shipping_url');

        $response = $this->makeCall($url, $json);
        return $response;
    }

    private function makeCall($url, $json)
    {
        $authorization = "Authorization: Bearer ".$this->getConfigData('shipping_url_key');

        // Setup cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                $authorization
            ),
            CURLOPT_POSTFIELDS => $json
        ));

        // Send the request
        $response = curl_exec($ch);

        // Check for errors. If there is an error the server probably isn't available
        if ($response === FALSE) {
            return false;
        }

        // Decode the response
        $responseData = json_decode($response, TRUE);

        // Close the cURL handler
        curl_close($ch);

        // return the response
        return $responseData;
    }
}
