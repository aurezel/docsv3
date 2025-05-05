<?php

namespace app\controller;

class Netsmax
{
    public function webhook()
    {
        // P0001:SUCCESS,P0002:FAILURE,Q0001:REQUEST
        generateApiLog([
            'type' => 'netsmaxWebhook',
            'input' => input(),
        ]);
        try{
            $postData = request()->post();
            if (!isset($postData['billNo'],$postData['md5Info'],$postData['status'],$postData['orderNo'])) die('Params Not Found!');
            $encryptData = app('netsmax')->createSign($postData,env('stripe.private_key'));
            if ($postData['md5Info'] !== $encryptData)
            {
                generateApiLog('Sign Error!');
                die('Sign Error!');
            }
            $centerId = intval(explode('-',$postData['billNo'])[0] ?? 0);
            if(empty($centerId)) die("Illegal Access");
            $responseStatus = $postData['status'];
            $status = 'failed';
            $transactionId = $postData['orderNo'];
            $failedMsg = '';
            if ($responseStatus == 'P0001') {
                $status = 'success';
            }elseif ($responseStatus == 'Q0001'){
                // 3ds request?
                return 'SUCCESS';
            }else{
                $failedMsg = $postData['info'];
            }
            app('netsmax')->sendDataToCentral($status,$centerId,$transactionId,$failedMsg);
        }catch (\Exception $e)
        {
            generateApiLog('Alipay Webhook 接口异常:'.$e->getMessage());
        }
        return 'SUCCESS';
    }

    public function redirect()
    {
        generateApiLog([
            'type' => 'netsmaxRedirect',
            'input' => input(),
        ]);
        $cid = input('get.cid',0);
        $type = input('get.status','');
        $centerId = (int) customDecrypt($cid);
        if (empty($type) || !$centerId) die('Illegal Access!');
        $fileName = app()->getRootPath() . 'file'.DIRECTORY_SEPARATOR . $centerId . '.txt';
        if (!file_exists($fileName)) die('Data Not Exist');
        $data = file_get_contents($fileName);
        if (empty($data)) die('Data Not Exist');
        $fData = json_decode($data,true);
        if (!isset($fData['html_data'])) die('Session Expired!');
        $transactionId = input('get.orderNo',0);
        $info = input('get.info','');
        if ($type !== 'P0001')
        {
            app('netsmax')->sendDataToCentral('failed',$centerId,$transactionId,$info);
            header("Referrer-Policy: no-referrer");
            header("Location:".request()->domain());
            exit('ok');
        }

        $orderData = $fData['html_data'];
        $orderInfo= 'Order No: <b>'.$orderData['order_no'].'</b><br>Amount:<b>'.$orderData['amount'].' '.$orderData['currency'].'</b>';
        $shippingInfo = $billingInfo = '<b>'.$orderData['first_name'] . ' '. $orderData['last_name'] .'<br>'.
            $orderData['address'].'<br>'.$orderData['telephone'].'<br>'.
            $orderData['city'] .','.$orderData['state'].' '.$orderData['zip_code'].'<br>'.
            $orderData['country'].'</b>';
        exit(sprintf(config('rapyd.success'),$orderInfo,$shippingInfo,$billingInfo,$orderData['email']));
    }

}