<?php

namespace app\controller;

class Paysafe
{
    public function webhook()
    {
        generateApiLog([
            'type' => 'psWebhook',
            'input' => file_get_contents('php://input')
        ]);

        $data = input();
        if (!isset($data['type'],$data['eventName'],$data['payload']['id'])) die('Illegal Access');

        $payload = $data['payload'];
        $merchantRefNum = $payload['merchantRefNum'];
        $transactionId = $data['payload']['id'];
        //$centerId = explode('-',$merchantRefNum)[1];
        $parts = explode('#', $merchantRefNum)[1];
        $centerId = substr($parts, 10);
        //$suffix = str_replace('uueos #'.date('ymd'),'',$merchantRefNum);
        //$centerId = intval(substr($suffix,3));
        $failedMsg = '';
        if ($data['type'] === 'PAYMENT')
        {
            if ($data['eventName'] === 'PAYMENT_COMPLETED' || $data['eventName'] === 'PAYMENT_FAILED' )
            {
                $status = $data['payload']['status'] === 'COMPLETED' ? 'success' : 'failed';

                if ($status == 'failed')
                {
                    $failedMsg = $data['payload']['error']['message'];
                }

                $sendResult = app('paysafe')->sendDataToCentral($status,$centerId,$transactionId,$failedMsg);
                if (!$sendResult) die('Internal Error!');
                die('Ok');
            }
        }

        if ($data['type'] == 'PAYMENT_HANDLE')
        {
            if ($data['eventName'] === 'PAYMENT_HANDLE_FAILED')
            {
                // 3ds失败
                $failedMsg = $data['payload']['error']['message'];
                $sendResult = app('paysafe')->sendDataToCentral('failed',$centerId,$transactionId,$failedMsg);
                if (!$sendResult) die('Internal Error!');
                die('Ok');
            }
        }
        die('Illegal Access!');
    }

    public function refund(): \think\response\Json
    {
        try{
            $settlementId = input('post.transaction_id');
            $centerId = input('post.center_id');


            if (empty($centerId) || empty($settlementId)) return apiError('Illegal Params!');
            $fileName = app()->getRootPath() . 'file'.DIRECTORY_SEPARATOR . $centerId . '.txt';
            if (!file_exists($fileName)) die('Data Not Exist');
            $data = file_get_contents($fileName);
            if (empty($data)) die('Data Not Exist');
            $fData = json_decode($data,true);
            $filePayData = $fData['payment_param'];
            $requestData = [
                'merchantRefNum' => $filePayData['merchantRefNum'],
                'amount' => $filePayData['amount'],
            ];
            $responseData = app('paysafe')->sendRequest("/settlements/$settlementId/refunds",$requestData);
            return apiSuccess($responseData);
            if (!isset($responseData['result']['resultCode'],$responseData['result']['resultMessage']))
            {
                return apiError('退款响应异常');
            }

            if ($responseData['result']['resultStatus'] == 'S' ||
                ($responseData['result']['resultStatus'] && $responseData['result']['resultCode'] == 'REFUND_IN_PROCESS'))
            {
                return apiSuccess('退款成功');
            }
            return apiError('退款失败:'.$responseData['result']['resultMessage']);
        }catch (\Exception $e)
        {
            generateApiLog('退款接口异常:'.$e->getMessage());
        }
        return apiError();
    }
    public function simulating()
    {
        try{
            $accountId = input('post.account_id');
            // $postData = input('post.post_data');

            if (empty($accountId)) return apiError('Illegal Access!');
            $referenceId = 'uueos #'.date('ymd') .mt_rand(100,999) .'1010';
            $postData = array (
                'merchantRefNum' => $referenceId,
                'amount' => 10098,
                'card' =>
                    array (
                        'cardNum' => '4111111111111111',
                        'cardExpiry' =>
                            array (
                                'month' => 1,
                                'year' => 2027,
                            ),
                        'cvv' => '123',
                    ),
                'profile' =>
                    array (
                        'firstName' => 'Adam',
                        'lastName' => 'Alock',
                    ),
                'billingDetails' =>
                    array (
                        'street' => '12',
                        'city' => 'Toronto',
                        'state' => 'ON',
                        'country' => 'CA',
                        'zip' => 'M5H 2N2',
                    ),
            );
            app('paysafe')->sendRequest("/accounts/$accountId/auths",$postData,'https://api.test.paysafe.com/cardpayments/v1');
        }catch (\Exception $e)
        {
            generateApiLog('Simulating接口异常:'.$e->getMessage());
        }
        return apiError();
    }

    public function  cancelSettlement()
    {
        try{
            $accountId = input('post.account_id');
            $settlementId = input('post.settlement_id');

            if (empty($accountId) || empty($settlementId)) return apiError('Illegal Params!');
            $requestData = ['status' => 'CANCELLED'];
            $responseData = app('paysafe')->sendRequest("/accounts/$accountId/settlements/$settlementId",$requestData,'https://api.test.paysafe.com/cardpayments/v1','PUT');
            return apiSuccess($responseData);
            if (!isset($responseData['result']['resultCode'],$responseData['result']['resultMessage']))
            {
                return apiError('退款响应异常');
            }

            if ($responseData['result']['resultStatus'] == 'S' ||
                ($responseData['result']['resultStatus'] && $responseData['result']['resultCode'] == 'REFUND_IN_PROCESS'))
            {
                return apiSuccess('退款成功');
            }
            return apiError('退款失败:'.$responseData['result']['resultMessage']);
        }catch (\Exception $e)
        {
            generateApiLog('退款接口异常:'.$e->getMessage());
        }
        return apiError();
    }


    public function redirect()
    {
        generateApiLog([
            'type' => 'psRedirect',
            'input' => input(),
        ]);

        $type = input('get.r_type','');
        $cid = input('get.cid',0);
        $centerId = (int) customDecrypt($cid);
        if (empty($type) || !$centerId || !in_array($type,['s','f','r'])) die('Illegal Access!');
        $fileName = app()->getRootPath() . 'file'.DIRECTORY_SEPARATOR . $centerId . '.txt';
        if (!file_exists($fileName)) die('Data Not Exist');
        $data = file_get_contents($fileName);
        if (empty($data)) die('Data Not Exist');
        $fData = json_decode($data,true);
        if (!isset($fData['html_data'])) die('Session Expired!');
        if ($type == 'f')
        {
            header("Referrer-Policy: no-referrer");
            if(isset($fData['s_url']) && strpos($fData['s_url'], request()->domain()) !== false) {
                header("Location:".request()->domain() . "/wc-api/secure-payment/");
            } else {
                header("Location:".request()->domain());
            }
            exit('ok');
        }

        $filePayData = $fData['payment_param'];
        // 3ds再支付
        $paymentRequestData = [
            'merchantRefNum' => $filePayData['merchantRefNum'],
            'amount' => $filePayData['amount'],
            'currencyCode' => $filePayData['currencyCode'],
            'dupCheck' => true,
            'settleWithAuth' => true,
            'paymentHandleToken' => $filePayData['paymentHandleToken'],
            'customerIp' => $filePayData['customerIp'],
            'description' => 'Your cart in item',
            'keywords' => [
                'SILVER'
            ]
        ];
        if(empty($fData['requestd'])) {
            $paymentResp = app('paysafe')->sendRequest('/payments',$paymentRequestData);
            $fData['requestd'] = true;
            file_put_contents($fileName, json_encode($fData));
            if (!isset($paymentResp['id'],$paymentResp['status']))
            {
                $failedMsg = $paymentResp['error']['message'];
                $result = app('paysafe')->sendDataToCentral('failed', $centerId, 0, $failedMsg);
                if (!$result) {
                    generateApiLog('回调发送中控失败:' . json_encode(['failed', $centerId, 0, $failedMsg]));
                }
                header("Referrer-Policy: no-referrer");
                if(isset($fData['s_url']) && strpos($fData['s_url'], request()->domain()) !== false) {
                    header("Location:".request()->domain() . "/wc-api/secure-payment/");
                } else {
                    header("Location:".request()->domain());
                }

                exit('ok');
            }
        }
        if($type == 's' && isset($fData['s_url']) && strpos($fData['s_url'], request()->domain()) !== false) {
            header("Referrer-Policy: no-referrer");
            header("Location:".$fData['s_url']);
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