<?php
namespace wechat\controllers;

use Yii;
use common\components\WechatLoginTrait;
use common\controllers\BaseController;

/**
 * 微信基类
 *
 * Class WController
 * @package wechat\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class WController extends BaseController
{
    use WechatLoginTrait;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        // 修改微信授权方式为静默授权
        Yii::$app->params['wechatConfig']['oauth']['scopes'] = ['snsapi_base'];

        // 开启微信模拟数据
        Yii::$app->params['simulateUser']['switch'] = false;

        // 微信登录
        $this->login();
    }

    /**
     * @param $logs
     * @param string $tag
     * @param string $id 选择日志频道
     */
    function Udplog($logs,$tag = '', $id=''){
        Yii::$app->LogStation->set_db($id)->write($logs,$tag);
    }
}
