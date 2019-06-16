<?php
/**
 * Created by PhpStorm.
 * User: weilone
 * Date: 2018/12/24
 * Time: 23:45
 */

namespace wechat\controllers;

use addons\RfArticle\common\models\Article;
use common\helpers\PayHelper;
use common\helpers\StringHelper;
use common\helpers\UrlHelper;
use common\models\bbb\MemberVipInfos;
use common\models\bbb\Notice;
use common\models\bbb\Orders;
use common\models\bbb\SmsLog;
use common\models\common\PayLog;
use common\models\member\MemberInfo;
use common\models\wechat\Fans;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Response;

class IndexController extends MyController
{

    function behaviors()
    {
        return parent::behaviors(); // TODO: Change the autogenerated stub
    }

    function beforeAction($action)
    {
        if (!$this->vipEnable && \Yii::$app->controller->action->id == 'detail') {
            return $this->redirect(Url::to(['index/index']))->send();
        }
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }

    function actions()
    {
        return parent::actions(); // TODO: Change the autogenerated stub
    }

    function actionIndex()
    {
        $data = [
            'isVip' => $this->isVip,
            'vipEnable' => $this->vipEnable,
            'list' => Article::find()->where(['position' => 1, 'status' => 1])->limit(20)->select('*')->orderBy(['sort' => SORT_DESC, 'created_at' => SORT_DESC])->all(),
        ];
        return $this->render('index', $data);
    }

    function actionRegister()
    {
        $sql = "select * from " . \Yii::$app->db->tablePrefix . "bbb_setting";
        $res = \Yii::$app->db->createCommand($sql)->queryAll();
        $data = [];
        if (!empty($res)) {
            foreach ($res as $v) {
                $data[$v['key']] = $v['value'];
            }
        }

        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $vipMoney = \Yii::$app->request->post('vipMoney');
            $phone = \Yii::$app->request->post('phone');
            $phoneCode = \Yii::$app->request->post('phoneCode');
            $recommendCode = \Yii::$app->request->post('recommendCode');
            $vipLimit = \Yii::$app->request->post('vipLimit');
            $vipMoney_2 = $data['vip_price'] ?? \Yii::$app->params['vip_price'];
            if ($vipMoney != $vipMoney_2) {
                return [
                    'msg' => '金额错误，购买VIP需要￥' . $vipMoney_2,
                    'status' => 0
                ];
            }

            if (!preg_match('/^1[345789]\d{9}$/', $phone)) {
                return [
                    'msg' => '手机号码有误',
                    'status' => 0
                ];
            }

            if (!$this->checkPhoneCode($phone, $phoneCode)) {
                return [
                    'msg' => '手机验证码有误',
                    'status' => 0
                ];
            }
            if (MemberInfo::find()->where(['username' => $phone])->exists()) {
                return [
                    'msg' => '抱歉，该手机号已被注册',
                    'status' => 0
                ];
            } else {
                $sn = 'BbB' . date('YmdHis') . StringHelper::randomNum();
                $flag = \Yii::$app->db->transaction(function () use ($phone, $vipMoney, $recommendCode, $sn, $vipLimit) {
                    $user = new MemberInfo();
                    $user->username = $user->mobile = $phone;
                    $user->nickname = \Yii::$app->params['wechatMember']['nickname'];
                    $user->head_portrait = \Yii::$app->params['wechatMember']['avatar'];
                    $user->gender = \Yii::$app->params['wechatMember']['original']['sex'];
//                    $user->area = \Yii::$app->params['wechatMember']['original']['country'];
//                    $user->provinces = \Yii::$app->params['wechatMember']['original']['province'];
//                    $user->city = \Yii::$app->params['wechatMember']['original']['city'];
                    $u = $user->save();

                    Fans::updateAll(['member_id' => $user->id], ['openid' => $this->openid]);

                    $order = new Orders();
                    $order->order_sn = $sn;
                    $order->member_id = $user->id;
                    $order->money = $vipMoney;
                    $order->month_limit = $vipLimit;
                    $order->goods = 'vip';
                    $order->desc = '帮宝帮会员购买';
                    $f = $order->save();
                    $v = true;
                    if (!($vip = MemberVipInfos::findOne(['member_id' => $user->id]))) {
                        $vip = new MemberVipInfos();
                        $vip->member_id = $order->member_id;
                        $vip->recommendCode = MemberVipInfos::getCode();
                        $vip->parent_id = MemberVipInfos::getPidByCode($recommendCode);
                        $vip->openid = $this->openid;
                        $v = $vip->save();
                    }

                    if ($u && $f && $v) {
                        \Yii::$app->session->set('user_info', $user->toArray());
                        return true;
                    }
                    return false;

                });
                if ($flag) {
                    return [
                        'status' => 1,
                        'msg' => '下单成功',
                        'data' => [
                            'order_sn' => $sn
                        ]
                    ];
                }
            }
            return [
                'msg' => '抱歉，注册失败',
                'status' => 0
            ];
        }
        if ($this->memberId > 0) {
            $this->redirect(['order/recharge']);
        }

        return $this->render('register', ['data' => $data]);
    }

    function actionSms()
    {
        if (\Yii::$app->request->isAjax && ($phone = \Yii::$app->request->get('phone'))) {
            if (preg_match('/^1(3|4|5|8|7)[0-9]{9}$/', $phone)) {
                if (MemberInfo::find()->where(['username' => $phone])->exists()) {
                    exit(Json::encode([
                        'msg' => '抱歉，该手机号已被注册',
                        'status' => 0
                    ]));
                }
                //todo sms
                $sms = new SmsLog();
                if ($has = $sms::find()->where(['phone' => $phone])->orderBy(['id' => SORT_DESC])->one()) {
                    if ((time() - $has['created_at']) < 60 * 10) {
                        exit(Json::encode([
                            'msg' => '短信验证码获取时间不能超过10Min',
                            'status' => 0
                        ]));
                    }
                }
                if (YII_ENV != 'prod') {
                    $code = 123456;
                } else {
                    $code = random_int(1000, 999999);
                }
                $sms->phone = $phone;
                $sms->code = $code;
                if ($sms->save()) {
                    exit(Json::encode([
                        'data' => [
                            'code' => $code
                        ],
                        'msg' => '',
                        'status' => 1
                    ]));
                }
            }
        }
        exit(Json::encode([
            'data' => [],
            'msg' => '请输入正确的手机号',
            'status' => 0
        ]));
    }

    function checkPhoneCode($phone, $code)
    {
        return SmsLog::find()->where(['phone' => $phone, 'code' => $code])->orderBy(['id' => SORT_DESC])->one();
    }

    function actionDetail()
    {
        $id = \Yii::$app->request->get('id');
        if (!$id) {
            return $this->to_404();
        }
        \Yii::$app->db->createCommand('update ' . Article::tableName() . ' set view=view+1 where id=' . intval($id))->execute();
        return $this->render('detail', [
            'data' => Article::findOne(['id' => $id])
        ]);
    }

    function actionSearch($key)
    {

    }
}