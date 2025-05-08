<?php
/**
 * Created by PhpStorm.
 * User: hjl
 * Date: 2022/8/1
 * Time: 10:05
 */

namespace app\service;

class PlaceCycopayOrderService extends BaseService
{

    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError();
        $cid = customEncrypt($params['center_id']);
        $baseUrl = request()->domain() . '/' . basename(app()->getRootPath()) . '/pay/';
        $postData['apiKey'] = env('stripe.private_key');
        $postData['amount'] = $params['amount'];
        $postData['webhookURL'] = $baseUrl  . 'cyWebhook?cid='.$cid;
        $postData['successURL'] = $baseUrl  . 'cySuccess?cid='.$cid;
        $postData['failureURL'] = $baseUrl  . 'cyFailure?cid='.$cid;
        $postData['currency'] = $params['currency'];
        $postData['description'] = 'Cycopay Payment';
        $postData['email'] = $params['email'];
        $postData['fullName'] = $params['first_name'] . ' '.$params['last_name'];
        generateApiLog($postData);
        $result = $this->getCyLink('https://api.cycopay.com/api/public/payment/create',$postData);
        if (!$result) return apiError();
        $response = json_decode($result,true);
        if ($response['status'] != 'success')
        {
            generateApiLog("生成支付链接失败：".$result);
            return apiError();
        }
        generateApiLog(['payUrl' => $response['url']]);
        return apiSuccess($response['url']);
    }

    private function getCyLink($url = '', $post_data = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        $data = curl_exec($ch);
        if($data === false){
            generateApiLog("CURL ERROR:".curl_error($ch));
            return false;
        }else{
            curl_close($ch);
            return $data;
        }
    }
}