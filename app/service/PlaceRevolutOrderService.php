<?php
/**
 * Created by PhpStorm.
 * User: hjl
 * Date: 2023/6/26
 * Time: 9:41
 */

namespace app\service;

class PlaceRevolutOrderService extends BaseService
{
    public function placeOrder(array $params = [])
    {
        try{
            if (!$this->checkToken($params)) return apiError();
            $baseUrl = env('local_env') ? 'https://sandbox-merchant.revolut.com' : 'https://merchant.revolut.com';
            $createOrderUrl = $baseUrl . '/api/1.0/orders';
            $headers = array(
                'Authorization: Bearer ' . env('stripe.private_key'),
                'Content-Type: application/json',
                'Accept: application/json'
            );

            $requestParams = [
                'amount' => floatval($params['amount']) * 100,
                'merchant_order_ext_ref' => $params['center_id'] . '_' . mt_rand(100000,999999),
                'email' => $params['email'],
                'description' => 'Pay for revolut',
                'currency' => $params['currency_code'],
                'shipping_address' => [
                    'street_line_1' => $params['address1'],
                    'street_line_2' => $params['address2'],
                    'region' => $params['state'],
                    'city' => $params['city'],
                    'country_code' => $params['country'],
                    'postcode' => $params['zip']
                ]
            ];

            $createOrderResponse = $this->curl_request($createOrderUrl,$requestParams,$headers);
            if ($createOrderResponse['state'] == 'PENDING')
            {
                // TODO:: order_id可能有用到
                return apiSuccess([
                    'public_id' => $createOrderResponse['public_id']
                ]);
            }

            return apiError();

        }catch (\Exception $ex)
        {
            $orderNo = $params['order_no'] ?? 0;
            $centerId = $params['center_id'] ?? 0;
            generateApiLog([
                '创建订单异常',
                "订单ID：{$orderNo}",
                "中控ID：{$centerId}",
                '错误信息：' => [
                    'msg' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                    'line' => $ex->getLine(),
                    'trace' => $ex->getTraceAsString()
                ]
            ]);
        }

        return apiError();
    }

    private function curl_request($url, $params,$requestHeader)
    {
        $ch = curl_init();
        curl_setopt_array($ch,[
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => $requestHeader,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ]);

        $result = curl_exec($ch);
        $curlErrNO = curl_errno($ch);
        if ($curlErrNO) {
            $curlErrMsg = curl_error($ch);
            throw new \Exception("[请求错误]URL:{$url},Params:" . http_build_query($params) . "ErrMsg:{$curlErrMsg}");
        }
        curl_close($ch);
        $result = json_decode($result, true);
        if (isset($result['error'])) {
            throw new \Exception("[URL请求失败] ErrRes:" . json_encode($result) . ",URL:{$url},Params:" . json_encode($params));
        }
        return $result;
    }

}