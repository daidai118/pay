<?php
/**
 * @brief Wxpay component
 *
 * @author tlanyan<tlanyan@hotmail.com>
 * @link http://tlanyan.me
 */
/* vim: set ts=4; set sw=4; set ss=4; set expandtab; */

namespace tlanyan;

use Httpful\Mime;
use Httpful\Request;

class Wxpay
{
    public $appid;
    public $appkey;

    /**
     * @var merchant id
     */
    public $mchid;

    /**
     * @var Wxpay callback url
     */
    public $notifyUrl;

    /**
     * @var sign generate algorithm
     */
//    public $signType = 'HMAC-SHA256';
    public $signType = 'MD5';

    public $logCategory = 'wxpay';

    /**
     * @const the gate way to get prepay id
     */
    const ORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const ORDER_CHECK = 'https://api.mch.weixin.qq.com/pay/orderquery';
    public function __construct($config)
    {
        $this->appid = $config['appid'];
        $this->appkey = $config['appkey'];
        $this->mchid = $config['mchid'];
        $this->notifyUrl = $config['notifyUrl'];
    }

    public function init()
    {
        parent::init();

        $this->signType = strtoupper($this->signType);
        if ($this->signType !== 'HMAC-SHA256') {
            $this->signType = 'MD5';
        }
    }
    /**
     * get the pay parameter for the client
     * @var int $orderId
     * @var float $amount
     * @var string $body brief of trade
     * @var string $ip the client ip to pay
     * @var string $detail detail of this trade
     * @var string $tradeType
     * @return array
     */
    public function getPayParameter( $orderId,  $amount,  $body,  $ip='',  $detail = '',$attach='',  $tradeType = 'APP')
    {
        $postData = [
            'appid' => $this->appid,
            'body' => $body,
            'attach' => $attach,
            'mch_id' => $this->mchid,
            'nonce_str' => $this->get_noncestr(32),
            'notify_url' => $this->notifyUrl,
            'out_trade_no' => $orderId,
            'spbill_create_ip' => $ip,
            'sign_type' => $this->signType,
            'total_fee' => $amount,
            'trade_type' => $tradeType,
        ];

        $postData['sign'] = $this->getSign($postData);
        $postData = json_encode($postData);
        $postData = json_decode($postData);
        $client = Request::post(self::ORDER_URL)->body($postData,Mime::XML)->send();
        if ($client->code == 200) {
            $xmlParser = new XmlParser();
            $data = $xmlParser->parse($client->raw_body,Mime::XML);
            if ($data['return_code'] === 'SUCCESS') {
                if ($data['result_code'] === 'SUCCESS') {
                    //2签
//                    appid，partnerid，prepayid，noncestr，timestamp，package
                    $res = [
                        'appid' => $this->appid,
                        'partnerid' =>  $this->mchid,
                        'prepayid' =>  $data['prepay_id'],
                        'nonce_str' => $this->get_noncestr(32),
                        'timestamp' => time(),
                        'package' => 'Sign=WXPay',
                    ];
                    $res['sign'] = $this->getSign($res);
                    return  array_merge(['code'=>0],$res);
                }
                return [
                    'code' => 1,
                    'message' => $data['err_code_des'],
                ];
            } else {
                return [
                    'code' => 1,
                    'message' => 'fail to communicate with wxpay server',
                ];
            }
        }
    }
//    public function checkOrder( $orderId)
//    {
//        $postData = [
//            'appid' => $this->appid,
//            'mch_id' => $this->mchid,
//            'nonce_str' => $this->get_noncestr(32),
//            'out_trade_no' => $orderId,
//            'sign_type' => $this->signType,
//        ];
//        $postData['sign'] = $this->getSign($postData);
//
//        $client = new Client();
//        $response = $client->createRequest()
//            ->setMethod('post')
//            ->setFormat(Client::FORMAT_XML)
//            ->setUrl(self::ORDER_CHECK)
//            ->setData($postData)
//            ->send()->setFormat(Client::FORMAT_XML);;
//        if ($response->isOk) {
//            $data = $response->data;
//            Yii::info($data, $this->logCategory);
//            if ($data['return_code'] === 'SUCCESS') {
//                if ($data['result_code'] === 'SUCCESS') {
//                    return $data;
//                }
//                return [
//                    'code' => 1,
//                    'message' => $data['err_code_des'],
//                ];
//            } else {
//                Yii::error($data, $this->logCategory);
//                return [
//                    'code' => 1,
//                    'message' => 'fail to communicate with wxpay server',
//                ];
//            }
//        }
//    }



    /**
     * @var array $data the array to generate sign
     * @return string
     */
    public function getSign($data)
    {
        $data = array_filter($data);
        ksort($data);
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= $key . '=' . $value . '&';
        }
        $stringSignTemp = $stringA . 'key=' . $this->appkey;

        if ($this->signType === 'HMAC-SHA256') {
            $sign = hash_hmac('sha256', $stringSignTemp, $this->appkey);
        } else {
            $sign = md5($stringSignTemp);
        }

        return strtoupper($sign);
    }

    /**
     * check the integrity of the callback post data
     * @var array $data the post data array
     * @return boolean
     */
    public function checkSign($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);

        if ($this->getSign($data) === $sign) {
            return true;
        }

        return false;
    }

    function get_noncestr($length=16){
        $str = '';
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;
        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

}
