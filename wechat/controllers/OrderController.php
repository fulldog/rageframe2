<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23 0023
 * Time: 20:40
 */

namespace wechat\controllers;

use common\models\bbb\Orders;
use Yii;
use common\helpers\PayHelper;
use common\helpers\StringHelper;
use common\models\common\PayLog;
use common\helpers\UrlHelper;
use yii\base\Response;

class OrderController extends MyController
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    function actionPay2(){
        $totalFee = 1;// 支付金额单位：分
        $orderSn = time() . StringHelper::randomNum();// 订单号

        $orderData = [
            'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
            'body' => '支付简单说明',
            'detail' => '支付详情',
            'notify_url' => UrlHelper::toFront(['notify/easy-wechat']), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'out_trade_no' => PayHelper::getOutTradeNo($totalFee, $orderSn, 1, PayLog::PAY_TYPE_WECHAT, 'JSAPI'), // 支付
            'total_fee' => $totalFee,
            'openid' => Yii::$app->wechat->user->openid, // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];

//        $this->Udplog($orderData,'tag11','test');die;

        $payment = Yii::$app->wechat->payment;
        $result = $payment->order->unify($orderData);
        if ($result['return_code'] == 'SUCCESS')
        {
            $json = $payment->jssdk->bridgeConfig($result['prepay_id']);
        }
        else
        {
            p($result);die();
        }

        return $this->render('pay', [
            'json' => $json
        ]);
    }

    public function actionPay()
    {
        $orderSn = \Yii::$app->request->get('orderSn');
        $orderInfo = Orders::findOne(['order_sn'=>$orderSn]);
        if (!$orderInfo){
            $this->redirect(UrlHelper::to(['site/error']))->send();
        }
        $orderData = [
            'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
            'body' => $orderInfo['goods'],
            'detail' => $orderInfo['desc'],
            'notify_url' => UrlHelper::toFront(['notify/easy-wechat']), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'out_trade_no' => PayHelper::getOutTradeNo($orderInfo['money']*100, $orderSn, PayLog::PAY_TYPE_WECHAT), // 支付
            'total_fee' => $orderInfo['money']*100,
            'openid' => $this->openid, // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];

        $payment = \Yii::$app->wechat->payment;
        $result = $payment->order->unify($orderData);
        $json = '';
        if ($result['return_code'] == 'SUCCESS')
        {
            $json = $payment->jssdk->bridgeConfig($result['prepay_id']);
        }
        return $this->render('pay',[
            'json'=>$json,
            'orderInfo'=>$orderInfo->toArray()
        ]);
    }

    function actionRecharge(){
        if (Yii::$app->request->isAjax){
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $post = Yii::$app->request->post();
            $sn = 'BbB'.date('YmdHis').StringHelper::randomNum();
            $order = new Orders();
            $order->order_sn = $sn;
            $order->member_id = $this->memberId;
            $order->money = $post['vipMoney'];
            $order->month_limit = $post['vipLimit'];
            $order->goods = '帮宝帮会员续费';
            $order->desc = '帮宝帮会员续费';
            if ($order->save()){
                return [
                    'status'=>1,
                    'msg'=>'下单成功',
                    'data'=>[
                        'order_sn'=>$sn
                    ]
                ];
            }
            return [
                'status'=>0,
                'msg'=>'下单失败',
            ];
        }
        $orderInfo = Orders::find()->where(['member_id'=>$this->memberId])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        if ($orderInfo && $orderInfo->status==0){
            return $this->redirect(['order/pay','orderSn'=>$orderInfo->order_sn])->send();
        }

        return $this->render('recharge',[
            'orderInfo'=>$orderInfo
        ]);
    }

    function actionSucc(){

        return $this->render('succ');
    }
}