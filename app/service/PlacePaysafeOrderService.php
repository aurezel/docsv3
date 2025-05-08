<?php

namespace app\service;

class PlacePaysafeOrderService extends BaseService
{
    private $username;
    private $password;
    private $gatewayBaseUrl;

    public function __construct()
    {
        $this->username = env('stripe.public_key');
        $this->password = env('stripe.private_key');
        $this->gatewayBaseUrl = env('local_env') ? 'https://api.test.paysafe.com/paymenthub/v1' : 'https://api.paysafe.com/paymenthub/v1';
    }

    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError();
        $centralIdFile = app()->getRootPath() . DIRECTORY_SEPARATOR . 'file' . DIRECTORY_SEPARATOR . $params['center_id'] . '.txt';
        if (!file_exists($centralIdFile)) return apiError();
        $cid = customEncrypt($params['center_id']);
        $domain = request()->domain();
        $baseUrl = $domain;
        $statement = env('stripe.COMPANY');
        if (empty($statement)) $statement = explode('.', request()->rootDomain())[0];
        $centerId = intval($params['center_id']);
        $referenceId = $statement .' #'. date("ymd") . mt_rand(1000,9999) . $centerId;
        $sPath = env('stripe.checkout_success_path');
        $successPath = empty($sPath) ? '/' . basename(app()->getRootPath()) . '/pay/psRedirect' : $sPath;
        $returnLinkUrl = $baseUrl . $successPath;

        try {
            $cardNumber = self::processInput(str_replace(' ', '', $params['card_number']));
            if(in_array(substr($cardNumber,0,6), array("401993", "414846", "499121", "426558","433178","414049","460953","546258","543267","512440","516645","453865","498651","520803","520832","456542","537572","497019","531876","520942","526737","498708","548417","410669","474819"))) {
                generateApiLog('Paysafe Api收到被禁用的卡头');
                return apiError("Card Declined");
            }
            $firstName = str_replace(['-', "'", '.', '_', ','], ['', '', '', '', ''], $params['first_name']);
            $lastName = str_replace(['-', "'", '.', '_', ','], ['', '', '', '', ''], $params['last_name']);
            $fullName = $firstName . ' ' . $lastName;            
            if(isset($params['card_holder'])) {
                $fullName = $params['card_holder'];
            }
            if(!preg_match('/^[a-zA-Z\s\'\-]+$/', $fullName)) {
                generateApiLog('Paysafe Api非法持卡人名字');
                return apiError("Card Holder must contain only Latin characters (English Alphabet),Space, Apostrophe('), Dot(.) or Hyphen(-)");
            }
            
            $currency_dec = config('parameters.currency_dec');
            $amount = floatval($params['amount']);
            $currency = strtoupper($params['currency']);
            $scale = 1;
            for ($i = 0; $i < $currency_dec[$currency]; $i++) {
                $scale *= 10;
            }
            $amount = bcmul($amount, $scale);
            $state = $params['state'];
            $country = $params['country'];
            if ($country == 'US')
            {
                $zip = substr(str_replace(' ', '', $params['zip']), 0, 5);
                $usZips = include(app()->getRootPath() . "US_zip.php");
                if(isset($usZips[$zip])) {
                    $state = $usZips[$zip];
                } else {
                    $state = config('parameters.us_state_iso2')[$state] ?? $state;
                }
            }

            if ($country == 'CA')
            {
                $zip = strtoupper(substr(str_replace(' ', '', $params['zip']), 0, 6));
                $caZips = include(app()->getRootPath() . "CA_zip.php");
                if(isset($caZips[$zip])) {
                    $state = $caZips[$zip];
                } else {
                    $state = config('parameters.canada_province_iso2')[$state] ?? $state;
                }
            }
            $colorDepthBits = $params['colorDepthBits'];
            if(!in_array($colorDepthBits, [1, 4, 5, 15, 16, 24, 32, 48])) {
                $colorDepthBits = 32;
            }

            $requestData = [
                'merchantRefNum' => $referenceId,
                'transactionType' => 'PAYMENT',
                'threeDs' => [
                    'merchantUrl' => $domain,
                    'deviceChannel' => 'BROWSER',
                    'messageCategory' => 'PAYMENT',
                    'authenticationPurpose' => 'PAYMENT_TRANSACTION',
                    'browserDetails' => [
                        'acceptHeader' => $_SERVER['HTTP_ACCEPT'],
                        'colorDepthBits' => $colorDepthBits,
                        'customerIp' => $params['client_ip'],
                        'javaEnabled' => $params['javaEnabled'],
                        'javascriptEnabled' => true,
                        'language' => $params['language'],
                        'screenHeight' => $params['screenHeight'],
                        'screenWidth' => $params['screenWidth'],
                        'timezoneOffset' => $params['timezoneOffset'],
                        'userAgent' => substr($_SERVER['HTTP_USER_AGENT'],0,256)
                    ]
                ],
                'card' => [
                    'cardNum' => $cardNumber,
                    'cardExpiry' => [
                        'month' => str_pad(self::processInput($params['expiry_month']), '2', '0', STR_PAD_LEFT),
                        'year' => '20' . self::processInput($params['expiry_year'])
                    ],
                    'cvv' => self::processInput($params['cvc']),
                    'holderName' => $fullName,
                ],
                'settleWithAuth' => true,
                'paymentType' => 'CARD',
                'amount' => $amount,
                'currencyCode' => $currency,
                'billingDetails' => [
                    'city' => $params['city'],
                    'state' => $state,
                    'country' => $country,
                    'zip' => $params['zip']
                ],
                'profile' => [
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                    'email' => $params['email'],
                    'phone' => $params['phone']
                ],
                'returnLinks' => [
                    [
                        'rel' => 'default',
                        'href' => $returnLinkUrl . "?r_type=r&cid=$cid",
                        'method' => 'GET'
                    ],
                    [
                        'rel' => 'on_completed',
                        'href' => $returnLinkUrl . "?r_type=s&cid=$cid",
                        'method' => 'GET'
                    ],
                    [
                        'rel' => 'on_failed',
                        'href' => $returnLinkUrl . "?r_type=f&cid=$cid",
                        'method' => 'GET'
                    ]
                ]
            ];
            if(strlen($params['address1']) <= 50) {
                $requestData['billingDetails']['street'] = $params['address1'];
            } else {
                $breakPoint = strrpos(substr($params['address1'], 0, 50), ' ');
                if ($breakPoint === false) {
                    $breakPoint = strrpos(substr($params['address1'], 0, 50), ',');
                }
                if ($breakPoint === false) {
                    $breakPoint = 50;
                }
                $requestData['billingDetails']['street'] = trim(substr($params['address1'], 0, $breakPoint));
                $requestData['billingDetails']['street2'] = trim(substr($params['address1'], $breakPoint));
            }

            $accountIds = array(
                'USD' => '1002890660',
                'GBP' => '1002890650',
                'EUR' => '1002890670'
            );
            if(isset($accountIds[$currency])) {
                $requestData['accountId'] = $accountIds[$currency];
            }

            $responseData = $this->sendRequest('/paymenthandles', $requestData);
            if (isset($responseData['paymentHandleToken'],$responseData['status']))
            {
                if ($responseData['status'] == 'PAYABLE')
                {
                    // pay now
                    $paymentRequestData = [
                        'merchantRefNum' => $referenceId,
                        'amount' => $amount,
                        'currencyCode' => $currency,
                        'dupCheck' => true,
                        'settleWithAuth' => true,
                        'paymentHandleToken' => $responseData['paymentHandleToken'],
                        'customerIp' => $params['client_ip'],
                        'description' => 'Your cart in item',
                        'keywords' => [
                            'SILVER'
                        ]
                    ];
                    $paymentResp = $this->sendRequest('/payments',$paymentRequestData);
                    if (isset($paymentResp['id'],$paymentResp['status']))
                    {
                        $this->validateCard(true);
                        return apiSuccess();
                    }
                    $failedMsg = $paymentResp['error']['message'];
                    $result = $this->sendDataToCentral('failed', $centerId, 0, $failedMsg);
                    if (!$result) {
                        generateApiLog('发送中控失败:' . json_encode(['failed', $centerId, 0, $failedMsg]));
                    }
                    $this->validateCard();
                    return apiError($failedMsg);
                }elseif ($responseData['status'] === 'INITIATED' && isset($responseData['action']) && $responseData['action'] === 'REDIRECT')
                {
                    $fileName = app()->getRootPath() . 'file'.DIRECTORY_SEPARATOR . $centerId . '.txt';
                    if (!file_exists($fileName)) return apiError('Data Not Exist');
                    $data = file_get_contents($fileName);
                    if (empty($data)) return apiError('Data Not Exist');
                    $fData = json_decode($data,true);
                    $fData['payment_param'] = [
                        'amount' => $responseData['amount'],
                        'merchantRefNum' => $responseData['merchantRefNum'],
                        'currencyCode' => $responseData['currencyCode'],
                        'paymentHandleToken' => $responseData['paymentHandleToken'],
                        'customerIp' => $responseData['customerIp']
                    ];
                    file_put_contents($fileName,json_encode($fData));
                    $url = $responseData['links'][0]['href'];
                    $this->validateCard();
                    return apiSuccess(['url' => $url]);
                }
            }elseif (isset($responseData['error']))
            {
                $failedMsg = isset($responseData['error']['fieldErrors']) ? json_encode($responseData['error']['fieldErrors']) : $responseData['error']['message'];
                $result = $this->sendDataToCentral('failed', $centerId, 0, $failedMsg);
                if (!$result) {
                    generateApiLog('发送中控失败:' . json_encode(['failed', $centerId, 0, $failedMsg]));
                }
                $this->validateCard();
                return apiError("Card Declined");
            }
        } catch (\Exception $e) {
            generateApiLog('PaySafe接口异常:' . $e->getMessage() . ',line:' . $e->getLine() . ',trace:' . $e->getTraceAsString());
        }
        return apiError();
    }

    public function sendRequest($requestPath, $reqBody,$tmpGatewayBaseUrl = '',$method = 'POST')
    {
        $requestUrl = (empty($tmpGatewayBaseUrl) ? $this->gatewayBaseUrl : $tmpGatewayBaseUrl) . $requestPath;
        $headers = [
            "Accept: application/json",
            "Authorization: Basic " . base64_encode($this->username . ':' . $this->password),
            "Content-Type: application/json"
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($reqBody),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $responseData = json_decode($response,true);
        generateApiLog('Request Data:' . json_encode(
                [
                    'url' => $requestUrl,
                    'headers' => $headers,
                    'request_data' => $reqBody,
                    'response' => $responseData,
                ], JSON_UNESCAPED_SLASHES));
        if ($err) {
            throw new \Exception("Response data error,result field is null,rspBody:" . $err);
        }
        return $responseData;
    }

}