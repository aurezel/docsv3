<?php
/**
 * Created by PhpStorm.
 * User: hjl
 * Date: 2023/6/27
 * Time: 11:17
 */

namespace app\service;

class PlaceVellaOrderService extends BaseService
{
    public function placeOrder(array $params = [])
    {
        if (!$this->checkToken($params)) return apiError();
        $reference_id = mt_rand(10000,99999).'_' . intval($params['center_id']);
        $amount = floatval($params['amount']);
        $key = env('stripe.public_key');
        $tags = env('stripe.private_key');
        $currency = 'NGN';// supported fiat NGNT,USDT,USDC
        $result = compact('reference_id','amount','key','tags','currency');
        return apiSuccess($result);
    }
}