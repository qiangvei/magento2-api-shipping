<?php

namespace Qiangvei\ApiShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class ApiShipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = "apishipping";
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $resultFactory;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $methodFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $resultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $methodFactory,
        array $data = [])
    {
        $this->resultFactory = $resultFactory;
        $this->methodFactory = $methodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if(!$this->getConfigData('active')){
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->resultFactory->create();

        $endpoint = $this->getConfigData('api_endpoint');
        if(!trim($endpoint)){
            return false;
        }

        $req = [];
        $req['AllItems'] = $request->getAllItems();
        $req['BaseCurrency'] = $request->getBaseCurrency();
        $req['ConditionName'] = $request->getConditionName();
        $req['OrderSubtotal'] = $request->getOrderSubtotal();
        $req['OrderTotalQty'] = $request->getOrderTotalQty();
        $req['PackageWeight'] = $request->getPackageWeight();
        $req['PackageQty'] = $request->getPackageQty();
        $req['PackageCurrency'] = $request->getPackageCurrency();
        $req['PackageDepth'] = $request->getPackageDepth();
        $req['PackageHeight'] = $request->getPackageHeight();
        $req['PackageWidth'] = $request->getPackageWidth();
        $req['PackageValue'] = $request->getPackageValue();
        $req['PackageValueWithDiscount'] = $request->getPackageValueWithDiscount();
        $req['PackagePhysicalValue'] = $request->getPackagePhysicalValue();
        $req['DestCountryId'] = $request->getDestCountryId();
        $req['DestRegionId'] = $request->getDestRegionId();
        $req['DestRegionCode'] = $request->getDestRegionCode();
        $req['DestCity'] = $request->getDestCity();
        $req['DestPostcode'] = $request->getDestPostcode();
        $req['DestStreet'] = $request->getDestStreet();
        $req['OrigCountryId'] = $request->getOrigCountryId();
        $req['OrigRegionId'] = $request->getOrigRegionId();
        $req['OrigCity'] = $request->getOrigCity();
        $req['OrigPostcode'] = $request->getOrigPostcode();
        $ch = new \Curl\Curl();
        $res = [];

        $time = time();
        $token = md5(md5($this->getConfigData('username').$this->getConfigData('password').$time).$time);
        $ch->get($endpoint,[
            'data'=>json_encode($req),
            'token'=>$token,
            'time'=>$time]);
        if (!$ch->error) {
            $res = $ch->response;
        }

        $carriers = json_decode($res,true);

        if(is_array($carriers) && count($carriers)>0){
            foreach ($carriers as $item){
                $method = $this->methodFactory->create();
                //$method->setCarrier($item['CarrierCode']);
                $method->setCarrier($this->getCarrierCode());
                $method->setCarrierTitle($item['CarrierTitle']);
                $method->setMethod($item['MethodCode']);
                $method->setMethodTitle($item['MethodTitle']);
                $cost = $item['Price'];
                $shippingPrice = $this->getFinalPriceWithHandlingFee($cost);
                $method->setPrice($shippingPrice);
                $method->setCost($cost);
                $result->append($method);
            }
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        return ['apishipping'=>$this->getConfigData('name')];
    }
}
