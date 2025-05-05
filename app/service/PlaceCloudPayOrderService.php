<?php

namespace app\service;

class PlaceCloudPayOrderService extends BaseService
{
    const GATEWAY_HOST = "https://apiv2.think-cloudpay.com";

    public function placeOrder(array $params = [])
    {
        try{
            $centerId = $params['center_id'];
            $amount = floatval($params['amount']);
            if (!$this->checkToken([
                'center_id' => $centerId,
                'amount' => $amount,
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'token' => $params['token']
            ]))
            {
                return apiError('Illegal Token');
            }
            $customerEmail = [
                'email' => $params['email'],
            ];
            $customParams = [
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'phone' => $params['phone'],
                'country' => $params['country'],
                'city' => $params['city'],
                'state' => $params['state'],
                'zipcode' => $params['zip'],
                'address' => $params['address1']
            ];

            $baseUrl = request()->domain() . '/' . basename(app()->getRootPath());
            //$sPath = env('stripe.checkout_success_path');
            //$cPath = env('stripe.checkout_cancel_path');
            //$successPath = empty($sPath) ? '/checkout/pay/stckSuccess' : $sPath;
            //$cancelPath = empty($cPath) ? '/checkout/pay/stckCancel' : $cPath;
            $device_token = $params['direct_device_token'];
            $forter_token = $params['direct_forter_token'];
            $encrypt = $params['encrypt'];
            $bin = $params['bin'];
            $last4 = $params['last4'];
            $expiry_year = $params['expiry_year'];
            $expiry_month = $params['expiry_month'];

            if (!$encrypt)
            {
                generateApiLog('加密错误');
                return apiError('card info error');
            }

            $productsFile = app()->getRootPath() . 'product.csv';
            $productName = 'Your items in cart';
            $orderId = 'random_char8';
            //替换订单号规则
            $orderId = preg_replace_callback("|random_char(\d+)|",array(&$this, 'next_rand3'),$orderId);//字符串
            if (file_exists($productsFile))
            {
                $productNameData = array();
                if (($handle = fopen($productsFile, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $productNameData[] = [
                            'product_name' => $data[0],
                            'description' => $data[1] ?? ''
                        ];
                    }
                    fclose($handle);
                }
                $productNameCount = count($productNameData);
                if ($productNameCount > 0)
                {
                    $singleProduct = $productNameData[mt_rand(0,$productNameCount -1)];
                    $productName = $singleProduct['product_name'];
                    $productName = preg_replace_callback("|random_int(\d+)|",array(&$this, 'next_rand1'),$productName); //数字
                    $productName = preg_replace_callback("|random_char(\d+)|",array(&$this, 'next_rand3'),$productName);//字符串
                    $productName = preg_replace_callback("|random_letter(\d+)|",array(&$this, 'next_rand2'),$productName);//字母
                    $productName = str_replace('product_desc',$orderId,$productName);
                }
            }

            $cid = customEncrypt($centerId);
            $requestPath = "/api/v1/create/order/payment";
            $notifyPath = '/pay/cpWebhook';
            $randomId = mt_rand(10,99);
            $custOrderId = 'woo' . substr(env('stripe.public_key'), 0 ,5) . date("YmdHis",time()) . $randomId;
            $threeDReturnPath = "/pay/cp3DReturn?cid=$cid&csid=$custOrderId";
            $requestData = array(
                'currency' => $params['currency'],
                'amount' => (string)($amount),
                'cust_order_id' => $custOrderId,
                'customer' => array_merge($customerEmail,$customParams),
                'payment_method' => 'creditcard',
                'return_url' => $baseUrl.$threeDReturnPath,
                'notification_url' => $baseUrl.$notifyPath,
                'delivery_recipient' => $customParams,
                'cart_items' => [
                    [
                        'id' => (int) $params['order_no'],
                        'name' => $productName,
                        'quantity' => 1,
                        'unitPrice' => array(
                            'currency' => $params['currency'],
                            'value' => (string) $amount
                        )
                    ]
                ],
                'network' => '',
                'website'  => request()->host(),
                'memo' => $randomId,
                'ip' => get_real_ip(),
                'encrypt' => $encrypt,
                'device_token' => $device_token,
                'forter_token' => $forter_token,
                'bin' => $bin,
                'last4' => $last4,
                'expiry_month' => $expiry_month,
                'expiry_year' => $expiry_year,
            );

            $timeStamp = round(microtime(true) * 1000);
            $publicKey = env('stripe.public_key');
            $signatureData = $publicKey .
                "&" . $requestData['cust_order_id'] .
                "&" . $requestData['amount'] .
                "&" . $requestData['currency'] .
                "&" . env('stripe.private_key') .
                "&" . $timeStamp;

            $responseData = $this->cloudPayHttp($requestPath, $requestData, $signatureData, $timeStamp);
            generateApiLog('CloudPay payment response:'.json_encode($responseData));
            $errorMsg = $responseData['msg'];
            if ($responseData['code'] == 0)
            {
                $result = $responseData['result'];
                $cloudPayOrderId = $result['order_id'];
                file_put_contents(app()->getRootPath() . 'file' . DIRECTORY_SEPARATOR .$cloudPayOrderId . '.txt',$centerId);
                $url = '';
                switch ($result['status'])
                {
                    case 0:
                        $url = $result['acs_url'];
                    case 1:
                        return apiSuccess([
                            'redirect_url' => $url
                        ]);
                    default:
                        $this->sendDataToCentral('failed',$centerId,0,$errorMsg);
                        return apiError($errorMsg);
                }
            }else
            {
                $this->sendDataToCentral('failed',$centerId,0,$errorMsg);
                return apiError($errorMsg);
            }
        }catch (\Exception $e)
        {
            generateApiLog(['请求异常' => $e->getMessage()]);
        }
        return apiError();
    }


    public function cloudPayHttp($requestPath, $requestData, $signatureData = '', $timeStamp = '',$isPost = true)
    {
        $ch = curl_init();
        $requestUrl = self::GATEWAY_HOST . $requestPath;
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authorization = [];
        if (!empty($signatureData))
        {
            $token = env('stripe.public_key') .':'.$timeStamp.':'.hash('sha256',$signatureData);
            $authorization  = ['Authorization:' . $token];
        }

        if ($isPost)
        {
            $headers = [
                'Content-Type:application/json',
            ];
            if (!empty($authorization))
            {
                $headers = array_merge($authorization,$headers);
            }
            generateApiLog('CloudPay request data:'.json_encode([
                'url' => $requestUrl,
                'header' => $headers,
                'body' => $requestData
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, $authorization);
            curl_setopt($ch,CURLOPT_URL,$requestUrl . http_build_query($requestData));
            generateApiLog('CloudPay GET request data:'.json_encode([
                    'url' => $requestUrl,
                    'header' => $authorization,
                    'body' => $requestData
                ]));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        if ($curlErrno) {
            $errMsg = curl_error($ch);
            throw new \ErrorException("Error:$errMsg");
        }
        return json_decode($result,true);
    }
}