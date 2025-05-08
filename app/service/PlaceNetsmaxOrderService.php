<?php

namespace app\service;

class PlaceNetsmaxOrderService extends BaseService
{
    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError();
        $baseUrl = request()->domain();
        //$baseUrl = 'https://aba6-45-62-172-111.ngrok-free.app';
        $centerId = intval($params['center_id']);
        $cid = customEncrypt($centerId);
        $referenceId = $centerId . '-' . date('YmdHis').mt_rand(10000,99999);

        try {
            $cardNumber = self::processInput(str_replace(' ', '', $params['card_number']));
            $firstName = str_replace(['-', "'", '.', '_', ','], ['', '', '', '', ''], $params['first_name']);
            $lastName = str_replace(['-', "'", '.', '_', ','], ['', '', '', '', ''], $params['last_name']);
            //$currency_dec = config('parameters.currency_dec');
            $amount = floatval($params['amount']);
            $currency = strtoupper($params['currency']);
//            $scale = 1;
//            for($i = 0; $i < $currency_dec[$currency]; $i++) {
//                $scale*=10;
//            }
            //$amount = bcmul($amount,$scale);
            $email = $params['email'];
            $merNo      = env('stripe.public_key');
            $md5Key     = env('stripe.private_key');
            $apiDomain = env('local_env') ? 'https://api.transend.app' : 'https://api.netsmax.com';
            //$apiDomain = 'https://api.moneycham.com';
            $apiUrl     = $apiDomain.'/payment/pay';
            $sPath = env('stripe.checkout_success_path');
            $nPath = env('stripe.checkout_notify_path');
            $successPath = empty($sPath) ? '/' . basename(app()->getRootPath()) . '/pay/nxRedirect' : $sPath;
            $notifyPath = empty($nPath) ? '/' . basename(app()->getRootPath()) . '/pay/nxNotify' : $nPath;
            $redirectCheckoutUrl = $baseUrl . $successPath . "?cid=$cid&r_type=s";
            $notifyCheckoutUrl = $baseUrl . $notifyPath;

            $request = [
                'merNo'         => $merNo,
                'billNo'        => $referenceId,
                'amount'        => $amount,
                'currency'      =>  $currency,
                'productInfo'       => json_encode([]),
                'returnUrl'     => $redirectCheckoutUrl,
                'notifyUrl'     => $notifyCheckoutUrl,
                'ip'            => $params['client_ip'],
                "dataTime"      => date("YmdHis"),
                'cardNum'       => $cardNumber,
                'cvv2'          => self::processInput($params['cvc']),
                'year'          => '20'.self::processInput($params['expiry_year']),
                'month'         => str_pad(self::processInput($params['expiry_month']), '2', '0', STR_PAD_LEFT),
                'firstName'     => $firstName,
                'lastName'      => $lastName,
                'phone'         => $params['phone'],
                'email'         => $email,
                'address'       => $params['address1'],
                'city'          => $params['city'],
                'state'         => $params['state'],
                'country'       => $params['country'],
                'zipCode'       => $params['zip'],
            ];

            $request['md5Info'] = $this->createSign($request,$md5Key);
            $returnData = $this->requestCurl($apiUrl,$request);
            $responseObj = json_decode($returnData);
            generateApiLog(['Netsmax responseObj:' => $responseObj]);
            if ($responseObj->status == 'P0001')
            {
                $this->validateCard(true);
                return apiSuccess();
            }elseif ($responseObj->status == 'Q0001')
            {
                $this->validateCard(true);
                if (!empty($responseObj->auth3DUrl))
                {
                    return apiSuccess([
                        'url' => $responseObj->auth3DUrl
                    ]);
                }
            }
            $failedMsg = $responseObj->info;
            $result = app('netsmax')->sendDataToCentral('failed', $centerId, 0,$failedMsg);
            if (!$result)
            {
                generateApiLog('发送中控失败:'.json_encode(['failed',$centerId,0,$failedMsg]));
            }
            $this->validateCard();
            return apiError($responseObj->info);
        } catch (\Exception $e) {
            generateApiLog('Netsmax接口异常:' . $e->getMessage() . ',line:' . $e->getLine() . ',trace:' . $e->getTraceAsString());
        }
        return apiError();
    }


    public function createSign($requestField, $signKey = '') {

        ksort($requestField);
        $requestString = '';
        foreach ($requestField as $key=>$value)
        {
            if($key == 'md5Info')
                continue;
            $requestString .= $key . '=' . $value . '&';
        }
        return md5($requestString . 'key=' . $signKey);
    }


    public function requestCurl($url, $request, $header='')
    {
        $curl = curl_init();

        curl_setopt($curl,CURLOPT_TIMEOUT,30);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $post_data = http_build_query($request);
        curl_setopt($curl,CURLOPT_HTTPHEADER,[
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'Content-Length:'.strlen($post_data)
        ]);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$post_data);
        $return = curl_exec($curl);
        $responseCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($return == NULL) {
            throw new \Exception('call http error info :'.curl_errno($curl) . '-'.curl_error($curl));
        }else if($responseCode != 200) {
            throw new \Exception('call http error httpcode :'.$responseCode);
        }
        return $return;
    }
}