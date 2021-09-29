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

    protected $rateRequest;
    protected $apiShippingData = [];
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
        $this->rateRequest = $request;
        $carriers = $this->getApiShipping();

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
        $allows = [];
        if(count($this->apiShippingData)>0){
            foreach ($this->apiShippingData as $item){
                $allows[$item['MethodCode']] = $item['MethodTitle'];
            }
        }
        return $allows;
        //return ['apishipping'=>$this->getConfigData('name')];
    }

    /**在collectRates(RateRequest $request)比 getAllowedMethods() 先执行的情况下有效。
     * 如果数据正常的情况下，获取不到物流报价，则可能是此问题，即 $this->rateRequest 没有被赋值，接口服务器返回空数据。
     * @return array
     */
    public function getApiShipping(){
        if(count($this->apiShippingData)>0){ return $this->apiShippingData;}
        $endpoint = $this->getConfigData('api_endpoint');
        if(!trim($endpoint)){
            return $this->apiShippingData;
        }
        $request = $this->rateRequest;
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
        $req['WeightUnit'] = $this->getWeightUnit();
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
            $res = json_decode($res);
        }
        $carrier = [];
        $carriers = [];
        foreach ($res as $v){
            foreach ($v as $kk=>$vv){
                $carrier[$kk] = $vv;
            }
            $carriers[] = $carrier;
        }
        $this->apiShippingData = $carriers;
        return $this->apiShippingData;
    }

    public function getWeightUnit()
    {
        return $this->_scopeConfig->getValue(
            'general/locale/weight_unit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
