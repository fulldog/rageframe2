<?php
namespace common\components;

use Yii;
use common\models\member\MemberAuth;

/**
 * 微信授权登录
 *
 * Trait WechatLoginTrait
 * @package common\components
 * @author jianyan74 <751393839@qq.com>
 */
trait WechatLoginTrait
{
    /**
     * 是否获取微信用户信息
     *
     * @var bool
     */
    protected $openGetWechatUser = true;

    /**
     * 用户id
     *
     * @var string
     */
    protected $openid;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    protected function login()
    {
        /** 非微信网页打开时候开启模拟数据 **/
        if (empty(Yii::$app->params['wechatMember']) && Yii::$app->params['simulateUser']['switch'])
        {
            Yii::$app->params['wechatMember'] = Yii::$app->params['simulateUser']['userInfo'];
        }else{
            /** 检测到微信进入自动获取用户信息 **/
            if ($this->openGetWechatUser && !Yii::$app->wechat->isAuthorized())
            {
                return Yii::$app->wechat->authorizeRequired()->send();
            }

            /** 当前进入微信用户信息 **/
            Yii::$app->params['wechatMember'] = json_decode(Yii::$app->session->get('_wechatUser'), true);
        }

        $this->openid = Yii::$app->params['wechatMember']['id'];

        // 如果是静默登录则不写入数据库
        if (in_array('snsapi_base', Yii::$app->params['wechatConfig']['oauth']['scopes']))
        {
//            return false;
        }

        // 插入微信关联表
        if (!($memberAuthInfo = MemberAuth::findOauthClient(MemberAuth::CLIENT_WECHAT, $this->openid)))
        {
            $original = Yii::$app->params['wechatMember']['original'];
            $memberAuth = new MemberAuth();
            $memberAuth->add([
                'oauth_client' => MemberAuth::CLIENT_WECHAT,
                'oauth_client_user_id' => $original['openid'],
                'gender' => $original['sex'],
                'nickname' => $original['nickname'],
                'head_portrait' => $original['headimgurl'],
                'country' => $original['country'],
                'province' => $original['province'],
                'city' => $original['city'],
                'language' => $original['language'],
            ]);

            unset($original, $memberAuthInfo, $memberAuth);
        }
    }
}