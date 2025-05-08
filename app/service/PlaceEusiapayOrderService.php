<?php

namespace app\service;

class PlaceEusiapayOrderService extends BaseService
{
    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError('Token Error');

        try{
            $cid = customEncrypt($params['center_id']);
            $baseUrl = request()->domain();
            $randomNumber = mt_rand(10000,99999);
            $gatewayUrl = 'https://app.eusiapay.com/gateway/MultipleInterface';
            $amount = floatval($params['amount']);
            $currency = strtoupper($params['currency']);

            $merchantNo = env('stripe.merchant_token');
            $gatewayNo = env('stripe.public_key');
            $firstName = $params['first_name'];
            $lastName = $params['last_name'];
            $country = $params['country'];
            $state = $params['state'];
            $city = $params['city'];
            $zipCode = $params['zip_code'];
            $phone = $params['phone'];
            $address = empty($params['address2']) ? $params['address1'] : $params['address1'] . ' ' . $params['address2'];
            $email = $params['email'];

            $orderNo = $params['order_no'];
            $sPath = env('stripe.checkout_success_path');
            $nPath = env('stripe.checkout_notify_path');
            $returnUrl = $baseUrl . (empty($sPath) ? '/' . basename(app()->getRootPath()) . '/pay/eusReturn' : $sPath). '?cid='.$cid;
            $notifyUrl = $baseUrl . (empty($nPath) ? '/' . basename(app()->getRootPath()) . '/pay/eusNotify' : $nPath) . '?cid='.$cid;

            $productsFile = app()->getRootPath() . 'product.csv';
            $productName = 'Your items in cart';
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
                    $productName = str_replace('product_desc',$orderNo,$productName);
                    if (empty($singleProduct['description']))
                    {
                        $singleProduct['description'] = $productName;
                    }
                }
            }

            $privateKy = env('stripe.private_key');
            $signData = $merchantNo.$gatewayNo.$orderNo.$currency.$amount.$returnUrl.$privateKy;
            $requestData = [
                'merNo' => $merchantNo,
                'gatewayNo' => $gatewayNo,
                'orderNo' => $orderNo,
                'orderAmount' => $amount,
                'orderCurrency' => $currency,
                'signInfo' => strtoupper(hash('sha256',$signData)),
                'paymentMethod' => 'Credit Card',
                'returnUrl' => $returnUrl,
                'notifyUrl' => $notifyUrl,
                'website' => $baseUrl,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'goodsInfo' => (isset($singleProduct) ? $singleProduct['description'] : $productName) . '#,#' .$randomNumber . '#,#' . $amount . '#,#'.'1',
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'address' => $address,
                'zip' => $zipCode,
                'shipFirstName' => $firstName,
                'shipLastName' => $lastName,
                'shipEmail' => $email,
                'shipPhone' => $phone,
                'shipCountry' => $country,
                'shipState' => $state,
                'shipCity' => $city,
                'shipAddress' =>$address,
                'shipZip' => $zipCode,
                'remark' => $cid
            ];

            $responseObj = $this->requestApi($gatewayUrl,$requestData);
            $url = $responseObj->paymentUrl;
            if (false === strpos($url,'https://')) $url = 'https://'.$url;
            return apiSuccess([
                'url' =>$url
            ]);
        }catch (\Exception $e)
        {
            generateApiLog('创建Eusipay支付接口异常:'.$e->getMessage() .',line:'.$e->getLine().',trace:'.$e->getTraceAsString());
            return apiError();
        }
    }

    private function requestApi($url,$requestData)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($requestData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_TIMEOUT,45);
        $headers = [
            'Content-Type:application/x-www-form-urlencoded',
            "user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        generateApiLog('response:'.$response);
        if (curl_errno($ch))
        {
            throw new \Exception('CURL异常:'.curl_error($ch));
        }
        $responseObj = json_decode($response);
        if (!$responseObj || '' == $responseObj->paymentUrl)
        {
            throw new \Exception('结果响应失败:'.json_encode($responseObj));
        }
        curl_close ($ch);
        return $responseObj;
    }
}