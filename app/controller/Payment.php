<?php

namespace app\controller;


use app\BaseController;

class Payment extends BaseController
{
    public function index()
    {
        $orderId = input('get.order_id');
        $type = input('get.type');
        if (!empty($type) && in_array($type,['s','c']))  return $type === 's' ? view('payment/success') : view('payment/cancel');
        if (empty($orderId)) return apiError('Illegal Access!');
        $requestResponse = json_decode(sendCurlData(GET_CUSTOMER_INFO_URL,[
            'b_domain' => request()->host(),
            'order_id' => $orderId
        ],CURL_HEADER_DATA),true);



        if (!isset($requestResponse['status']) or $requestResponse['status'] == 0)
        {
            generateApiLog(REFERER_URL .'获取客户信息失败：' . json_encode($requestResponse));
            return apiError();
        }
        $baseUrl = request()->domain() . '/' . basename(app()->getRootPath());
        $responseData = $requestResponse['data'];
        $centerId = intval($responseData['center_id']);
        $cid = customEncrypt($centerId);

        $currency_dec = config('parameters.currency_dec');
        $currency = strtoupper($responseData['currency']);
        $amount = floatval($responseData['amount']);
        $scale = 1;
        for($i = 0; $i < $currency_dec[$currency]; $i++) {
            $scale*=10;
        }
        $amount = bcmul($amount,$scale);
        $stripe = new \Stripe\StripeClient([
            'api_key'=>env('stripe.private_key'),
            'stripe_version' => '2024-04-10'
        ]);
        $successPath ='/payment?type=s';
        $cancelPath = '/payment?type=c';

        $requestData = array (
            'expires_at' => time() + 1800,
            'mode' => 'payment',
            'line_items' =>
                array (
                    0 =>
                        array (
                            'price_data' =>
                                array (
                                    'unit_amount' => $amount,
                                    'currency' => $currency,
                                    'product_data' =>
                                        array (
                                            'name' => 'Total',
                                        ),
                                ),
                            'quantity' => '1',
                        ),
                ),
            'payment_intent_data' => ['metadata' => ['order_id' => $cid]],
            'success_url' => $baseUrl . $successPath .  '&cid='.$cid,
            'cancel_url' => $baseUrl . $cancelPath . '&cid='.$cid
        );
        $checkout_session = $stripe->checkout->sessions->create($requestData);
        if (!isset($checkout_session->id))
        {
            generateApiLog('checkout_beta session Error:'.$checkout_session);
            return apiError();
        }
        return view('payment/index', [
            'name' => $responseData['first_name'],
            'total' => $responseData['amount'] . ' ' .$currency,
            'payment_url' => $checkout_session->url
        ]);
    }
}