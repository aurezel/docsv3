<?php

namespace app\service;

class PlaceCheckoutBetaOrderService extends BaseService
{
    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError('Token Error');

        try{
            $centerId = $params['center_id'];
            $centralIdFile = app()->getRootPath() .DIRECTORY_SEPARATOR.'file'.DIRECTORY_SEPARATOR.$centerId .'.txt';
            if (!file_exists($centralIdFile)) die('文件不存在');
            $cid = customEncrypt($centerId);
            $baseUrl = request()->domain();
            $currency_dec = config('parameters.currency_dec');
            $currency = strtoupper($params['currency']);
            $amount = floatval($params['amount']);
            $scale = 1;
            for($i = 0; $i < $currency_dec[$currency]; $i++) {
                $scale*=10;
            }
            $amount = bcmul($amount,$scale);
            header('Content-Type: application/json');
            $stripe = new \Stripe\StripeClient([
                'api_key'=>env('stripe.private_key'),
                'stripe_version' => '2024-04-10'
            ]);
            $sPath = env('stripe.checkout_success_path');
            $cPath = env('stripe.checkout_cancel_path');
            $successPath = empty($sPath) ? '/' . basename(app()->getRootPath()) . '/pay/stckSuccess' : $sPath;
            $cancelPath = empty($cPath) ? '/' . basename(app()->getRootPath()) . '/pay/stckCancel' : $cPath;

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
                'success_url' => $baseUrl . $successPath .  '?cid='.$cid,
                'cancel_url' => $baseUrl . $cancelPath . '?cid='.$cid,
                'customer_email' => $params['email'],
            );
            $checkout_session = $stripe->checkout->sessions->create($requestData);
            if (!isset($checkout_session->id))
            {
                generateApiLog('checkout_beta session Error:'.$checkout_session);
                return apiError();
            }
            return apiSuccess([
                'url' => $checkout_session->url
            ]);
        }catch (\Exception $e)
        {
            generateApiLog('创建session接口异常:'.$e->getMessage() .',line:'.$e->getLine().',trace:'.$e->getTraceAsString());
            return apiError();
        }
    }

}