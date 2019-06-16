<?php
/**
 * Created by PhpStorm.
 * User: weilone
 * Date: 2019/1/5
 * Time: 22:00
 */

namespace wechat\controllers;


use common\models\bbb\BbbDocash;
use common\models\bbb\BbbMessages;
use common\models\bbb\BbbMypurchase;
use common\models\bbb\BbbParentsCash;
use common\models\bbb\BbbSpecialDetails;
use common\models\bbb\BbbSpecials;
use common\models\bbb\Orders;
use yii\helpers\Url;

class MineController extends MyController
{

    function actionIndex(){

        return $this->render('index',[
            'vip_ending' => (\Yii::$app->params['vipEndTime']-time()),
            'user'=>\Yii::$app->params['wechatMember']
        ]);
    }

    function actionInfos(){
        return $this->render('infos',[

        ]);
    }

    //我的已购
    function actionPurchase(){
        $this->view->params['title'] = '我的已购';
        $mine = BbbMypurchase::findAll(['uid'=>$this->memberId,'status'=>1]);
        return $this->render('purchase',[
            'specials'=>$mine
        ]);
    }

    function actionIncome(){
        $this->view->params['title'] = '我的收益';
        //今日：
        $start_date = strtotime(date('Y-m-d'));
        $end_date = $start_date + 1*24*3600;

        $has_cash = BbbDocash::find()->where(['uid'=>$this->memberId])->andWhere(['!=','status',2])->select(['cash'])->sum('cash');
        $cash = BbbParentsCash::find()->where(['uid'=>$this->memberId,'status'=>1])->sum('get_money');
        return $this->render('income',[
            'today'=>BbbParentsCash::find()->where(['uid'=>$this->memberId])
                ->andFilterWhere(['between','created_at',$start_date, $end_date])
                ->sum('get_money'),
            'all'=>BbbParentsCash::find()->where(['uid'=>$this->memberId])->sum('get_money'),
            'cash'=>$cash-$has_cash,
            'data'=>BbbParentsCash::find()->where(['uid'=>$this->memberId])->limit(20)->orderBy(['id'=>'desc'])->all(),
        ]);
    }

    function actionDocash(){
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost){
            $cash = new BbbDocash();
            $cash->uid = $this->memberId;
            $cash->cash = \Yii::$app->request->post('money');
            $cash->save();
            return [
                'status'=>1,
            ];
        }
        return $this->render('docash',[
            'cash'=>BbbParentsCash::find()->where(['uid'=>$this->memberId,'status'=>1])->sum('get_money'),
        ]);
    }

    function actionMessage(){
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost){
            BbbMessages::updateAll(['status'=>1],['id'=>$this->request_post('id')]);
            return [
                'status'=>1,
            ];
        }

//        BbbMessages::updateAll(['status'=>1],['uid'=>$this->memberId]);
        $messages = BbbMessages::find()->select(['message','id','created_at','status'])->where(['uid'=>$this->memberId])->orderBy(['id'=>SORT_DESC])->limit(20)->asArray()->all();
        return $this->render('message',[
            'messages'=>$messages
        ]);
    }

    function actionSubscribe(){

        return $this->render('subscribe',[
            'info'=>''
        ]);
    }
}