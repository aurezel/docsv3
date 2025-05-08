<?php
/**
 * Created by PhpStorm.
 * User: hjl
 * Date: 2023/1/5
 * Time: 8:43
 */

namespace app\service;

class PlaceAirwallexOrderService extends BaseService
{
    private $token = "";
    private $expire = "";


    private function getToken() {
        $tokenFile = app()->getRootPath() .DIRECTORY_SEPARATOR . "airwallexToken.txt";
        if(file_exists($tokenFile)) {
            $tokenObj = json_decode(file_get_contents($tokenFile), true);
            $this->expire = $tokenObj['expires_at'];
            if(time() < strtotime($this->expire)) {
                return $tokenObj['token'];
            }
        }
        $clientId = env('stripe.public_key');
        $apiKey = env('stripe.private_key');
        $url = env('local_env') ? "https://api-demo.airwallex.com/api/v1/authentication/login" : "https://pci-api.airwallex.com/api/v1/authentication/login";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,null);  //Post Fields
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'Content-Type: application/json',
            'x-api-version: 2020-04-30',
            "x-api-key: $apiKey",
            "x-client-id: $clientId"
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec ($ch);

        $tokenObj = json_decode($result, true);
        if(!isset($tokenObj['token'])) {
            generateApiLog(['TokenError' => $result]);
            curl_close ($ch);
            return null;
        }
        curl_close ($ch);
        $this->token = $tokenObj['token'];
        $this->expire = $tokenObj['expires_at'];
        file_put_contents($tokenFile, $result);
        return $this->token;
    }
    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError();

        //$baseUrl = request()->domain() . '/';
        //$cid = customEncrypt($params['center_id']);
        // $returnUrl = $baseUrl  . '/checkout/pay/airwallexSuccess?cid=' . $cid;
        $orderId = env('stripe.merchant_token');

        //替换订单号规则
        $orderId = preg_replace_callback("|random_int(\d+)|",array(&$this, 'next_rand1'),$orderId); //数字
        $orderId = preg_replace_callback("|random_char(\d+)|",array(&$this, 'next_rand3'),$orderId);//字符串
        $orderId = preg_replace_callback("|random_letter(\d+)|",array(&$this, 'next_rand2'),$orderId);//字母

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
                $productName = str_replace('product_desc',$orderId,$productName);
                if (empty($singleProduct['description']))
                {
                    $singleProduct['description'] = $productName;
                }
            }
        }

        $data = [
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'descriptor' => $orderId,
            'merchant_order_id' => $orderId,
            //'return_url' => $returnUrl,
            'order' => [
                'type' => 'physical_goods',
            ],
            'request_id' => uniqid(),
        ];


        // Set customer detail
        $customerAddress = [
            'city' => $params['city'],
            'country_code' => $params['country'],
            'postcode' => $params['zip'],
            'state' => $params['state'],
            'street' => $params['address1'],
        ];

        $customer = [
            'email' => $params['email'],
            'first_name' => $params['first_name'],
            'last_name' => $params['last_name'],
            'phone_number' => $params['phone'],
        ];

        $data['customer'] = $customer;
        $data['customer']['address'] = $customerAddress;



        // Set order details
        $orderData = [
            'type' => 'physical_goods',
            'products' => [
                [
                    'desc' => isset($singleProduct) ? $singleProduct['description'] : $productName,
                    'name' => $productName,
                    'quantity' => 1,
                    'sku' => uniqid(),
                    'type' => 'physical',
                    'unit_price' => $params['amount']
                ]
            ]
        ];

        $orderData['shipping'] = [
            'first_name' => $params['first_name'],
            'last_name' => $params['last_name'],
            'shipping_method' => 'Free Shipping',
        ];
        $orderData['shipping']['address'] = $customerAddress;

        $data['order'] = $orderData;
        generateApiLog($data);
        $intent = $this->createPaymentIntent($data);
        if(empty($intent)) {
            return apiError();
        }
        return apiSuccess(['id'=>$intent['id'], 'secret'=>$intent['client_secret']]);
    }

    private function createPaymentIntent($data) {
        $ch = curl_init();
        $url = env('local_env') ? "https://api-demo.airwallex.com/api/v1/pa/payment_intents/create" : "https://pci-api.airwallex.com/api/v1/pa/payment_intents/create";

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));  //Post Fields
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $token = $this->getToken();
        if(empty($token)) {
            return null;
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec ($ch);
        generateApiLog($result);

        $intentObj = json_decode($result, true);
        if(!isset($intentObj['id']) || !isset($intentObj['client_secret'])) {
            generateApiLog(['IntentError' => $token . "\r\n" . json_encode($data) . "\r\n" . $result]);
            curl_close ($ch);
            return null;
        }
        curl_close ($ch);
        return $intentObj;
    }

    public function getPaymentIntent($intent_id) {
        $ch = curl_init();
        $url = env('local_env') ? "https://api-demo.airwallex.com/api/v1/pa/payment_intents/" : "https://pci-api.airwallex.com/api/v1/pa/payment_intents/";
        $url .= $intent_id;

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $token = $this->getToken();
        if(empty($token)) {
            return null;
        }
        $headers = [
            'Authorization: Bearer ' . $token
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec ($ch);

        $intentObj = json_decode($result, true);
        if(!isset($intentObj['id'])) {
            generateApiLog(['IntentError' => $token . "\r\n" . $intent_id . "\r\n" . $result]);
            curl_close ($ch);
            return null;
        }
        curl_close ($ch);
        return $intentObj;
    }

    public function capturePaymentIntent($intent_id, $amount) {
        $ch = curl_init();
        $url = env('local_env') ? "https://api-demo.airwallex.com/api/v1/pa/payment_intents/" : "https://pci-api.airwallex.com/api/v1/pa/payment_intents/";
        $url .= $intent_id;

        $data = array("amount"=>$amount, "request_id" => uniqid());

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));  //Post Fields
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $token = $this->getToken();
        if(empty($token)) {
            return null;
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec ($ch);

        $intentObj = json_decode($result, true);
        if(!isset($intentObj['status'])) {
            generateApiLog(['IntentError' => $token . "\r\n" . $intent_id . "\r\n" . $result]);
            curl_close ($ch);
            return null;
        }
        curl_close ($ch);
        return $intentObj;
    }

}