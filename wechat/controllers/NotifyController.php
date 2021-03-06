<?php
/**
 * Created by PhpStorm.
 * User: weilone
 * Date: 2019/1/1
 * Time: 22:41
 */

namespace wechat\controllers;

use common\enums\StatusEnum;
use common\helpers\FileHelper;
use common\helpers\PayHelper;
use common\models\bbb\BbbMypurchase;
use common\models\bbb\BbbParentsCash;
use common\models\bbb\BbbSetting;
use common\models\bbb\MemberVipInfos;
use common\models\bbb\Orders;
use common\models\common\PayLog;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

class NotifyController extends Controller
{

    public $enableCsrfValidation = false;
    protected $config;

    public function init()
    {
        $this->config = Yii::$app->debris->configAll();

        parent::init();
    }

    public function actionWechat()
    {
        Yii::$app->response->format = Response::FORMAT_XML;
        // 微信支付参数配置
        Yii::$app->params['wechatPaymentConfig'] = ArrayHelper::merge([
            'app_id' => $this->config['wechat_appid'],
            'mch_id' => $this->config['wechat_mchid'],
            'key' => $this->config['wechat_api_key'], // API 密钥
        ], Yii::$app->params['wechatPaymentConfig']);

        $response = Yii::$app->wechat->payment->handlePaidNotify(function ($message, $fail) {
            $message = ArrayHelper::toArray($message);
            if (empty($message)) {
                return $fail('通信失败，请稍后再通知我');
            }
            $logPath = Yii::getAlias('@runtime') . "/pay_log/" . date('Y-m') . '/' . date('Ymd') . '.log';
            FileHelper::writeLog($logPath, json_encode($message));
            // 如果订单不存在 或者 订单已经支付过了，如果成功返回订单的编号和类型
            if (!($orderInfo = PayHelper::notify($message['out_trade_no'], $message))) {
                // 告诉微信，我已经处理完了，订单没找到，别再通知我了
                return true;
            }
            /////////////  建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////
            //if ($info = \Yii::$app->wechat->payment->order->queryByOutTradeNumber($message['out_trade_no'])){
            //todo
            //}

            // 判断订单组别来源 比如课程、购物或者其他
            if ($orderInfo['order_group'] == PayLog::ORDER_GROUP) {
                /* @var $order \yii\db\ActiveRecord */
                if (!($order = Orders::findOne(['order_sn' => $orderInfo['order_sn']]))) {
                    return true;
                }
            }

            if ($message['result_code'] === 'SUCCESS') // 用户支付成功
            {
                $order->status = StatusEnum::WECHAT_SUCC;
            } elseif ($message['result_code'] === 'FAIL') // 用户支付失败
            {
                $order->status = StatusEnum::WECHAT_FAIL;
            }
            $order->trade_type = $orderInfo['trade_type'];
            $order->out_trade_no = $orderInfo['out_trade_no'];

            // 保存订单
            if ($order->save()) {
                $vip = MemberVipInfos::findOne(['openid' => $message['openid']]);
                $vip->vipage += $order->month_limit;
                $vip->vipend_at += strtotime("+ {$order->month_limit} month");
                $vip->vipstart_at = time();
                $vip->save();
            }

            if ($message['result_code'] === 'SUCCESS') {
                $this->pay_success($order, $message['openid']);
            }

            return true; // 返回处理完成
        });

        if ($response) {
            return PayHelper::notifyWechatSuccess();
        }
        return PayHelper::notifyWechatFail();
    }

    function pay_success($order, $openid)
    {
        $vip = MemberVipInfos::findOne(['member_id' => $order->member_id, 'openid' => $openid]);
        if ($order->goods == 'vips') {
            $vip->vipage += $order->month_limit;
            $vip->vipstart_at = time();
            $vip->vipend_at = strtotime("+" . $order->month_limit . ' month');
            $vip->save();
            $setting = BbbSetting::findOne(['key' => 'vip_point']);
        } else {
            $purchase = new BbbMypurchase();
            $purchase->sid = $order->goods;
            $purchase->uid = $order->member_id;
            $purchase->save();
            $setting = BbbSetting::findOne(['key' => 'special_point']);
        }

        //提成
        if ($vip->parent_id && !empty($setting->value)) {
            $tc = new BbbParentsCash();
            $tc->uid = $vip->parent_id;
            $tc->child_uid = $vip->member_id;
            $tc->goods = $order->goods;
            $tc->desc = $order->goods == 'vips' ? 'vip充值' : $order->desc;
            $tc->money = $order->money;
            $tc->get_money = round($order->money * $setting->value / 100, 2);
            $tc->save();
        }

    }
}