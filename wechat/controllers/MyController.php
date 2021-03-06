<?php
/**
 * Created by PhpStorm.
 * User: weilone
 * Date: 2018/12/25
 * Time: 21:51
 */

namespace wechat\controllers;


use common\models\bbb\BbbSetting;
use common\models\bbb\MemberVipInfos;
use common\models\member\MemberInfo;
use common\models\wechat\Fans;
use yii\helpers\Url;

class MyController extends WController
{
    public $openid;
    public $memberId;
    public $isVip = false;
    public $vipEnable = false;

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    function init()
    {
        parent::init();
        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
//            $this->enableCsrfValidation = false;
        }
        $this->view->params['description'] = '宝帮宝';
        $this->view->params['title'] = '宝帮宝';
        $this->_saveWechatUser();

        //vip价格和说明
        $setting = BbbSetting::find()->all();
        foreach ($setting as $k => $v) {
            \Yii::$app->params[$v->key] = $v->value;
        }
        return true;// TODO: Change the autogenerated stub
    }

    function _saveWechatUser()
    {
        //&& $this->openGetWechatUser && \Yii::$app->wechat->isWechat
        if (!empty(\Yii::$app->params['wechatMember'])) {
            $this->openid = \Yii::$app->params['wechatMember']['id'];
            $fan = Fans::find()->select('member_id')->where(['openid' => $this->openid])->one();
            if (!$fan) {
                $fan = new Fans();
                $fan->openid = $this->openid;
                $fan->nickname = \Yii::$app->params['wechatMember']['nickname'];
                $fan->head_portrait = \Yii::$app->params['wechatMember']['avatar'];
                $fan->sex = \Yii::$app->params['wechatMember']['original']['sex'];
                $fan->country = \Yii::$app->params['wechatMember']['original']['country'];
                $fan->province = \Yii::$app->params['wechatMember']['original']['province'];
                $fan->city = \Yii::$app->params['wechatMember']['original']['city'];
                $fan->save();
            } elseif ($fan->member_id) {
                $this->memberId = $fan->member_id;
                $user = MemberInfo::findOne(['id' => $fan->member_id]);
                if ($user) {
                    \Yii::$app->session->set('user_info', $user->toArray());
                }
                $vips = MemberVipInfos::findOne(['member_id' => $fan->member_id]);
                if ($vips) {
                    $time = time();
                    $this->isVip = true;
                    \Yii::$app->params['vipEndTime'] = $vips->vipend_at;
                    if ($time > $vips->vipstart_at && $time < $vips->vipend_at) {
                        $this->vipEnable = true;
                    }
                }
            }
        }
    }

    function request_get($name)
    {
        return \Yii::$app->request->get($name);
    }

    function request_post($name)
    {
        return \Yii::$app->request->post($name);
    }

    function to_404()
    {
        return $this->render('404');
    }
}