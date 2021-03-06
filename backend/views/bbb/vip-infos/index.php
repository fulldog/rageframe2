<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'VIP会员信息';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
  <div class="col-xs-12">
    <div class="box">
      <div class="box-header">
        <h3 class="box-title"><?= $this->title; ?></h3>
        <!--        <div class="box-tools">-->
        <!--            --><? //= Html::a('新增专栏', ['create'], ['class' => 'btn btn-success']) ?>
        <!--        </div>-->
      </div>
      <div class="box-body table-responsive">

          <?= GridView::widget([
              'dataProvider' => $dataProvider,
              'filterModel' => $searchModel,
              'columns' => [
//            ['class' => 'yii\grid\SerialColumn'],
                  'id',
                  [
                      'attribute' => 'member_id',
                      'value' => function ($model) {
                          $info = \common\models\member\MemberInfo::findOne(['id' => $model->member_id]);
                          return $info->username;
                      },
                      'label' => '用户名'
                  ],
                  [
                      'label' => '微信昵称',
                      'value' => function ($model) {
                          $info = \common\models\wechat\Fans::findOne(['openid' => $model->openid]);
                          return $info->nickname;
                      }
                  ],
                  'recommendCode',
                  [
                      'attribute' => 'parent_id',
                      'value' => function ($model) {
                          if ($model->parent_id) {
                              $info = \common\models\member\MemberInfo::findOne(['id' => $model->parent_id]);
                              return $info->username;
                          }
                      },
                      'filter'=>false
                  ],
//                  'openid',
                  [
                      'attribute' => 'vipage',
                      'filter'=>false
                  ],
                  [
                      'attribute' => 'vipstart_at',
                      'value' => function ($model) {
                          return Yii::$app->formatter->asDatetime($model->vipstart_at);
                      },
                      'filter'=>false
                  ],
                  [
                      'attribute' => 'vipend_at',
                      'value' => function ($model) {
                          return Yii::$app->formatter->asRelativeTime($model->vipend_at);
                      },
                      'filter'=>false
                  ],
//            'created_at',
//            'updated_at',

                  [
                      'class' => 'yii\grid\ActionColumn',
                      'template' => '{update} {delete}',
                      'buttons' => [
                          // 下面代码来自于 yii\grid\ActionColumn 简单修改了下
                          'view' => function ($url, $model, $key) {
                              $options = [
                                  'title' => Yii::t('yii', 'View'),
                                  'aria-label' => Yii::t('yii', 'View'),
                                  'data-pjax' => '0',
                              ];
                              return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, $options);
                          },
                          'update' => function ($url, $model, $key) {
                              $options = [
                                  'title' => Yii::t('yii', 'Update'),
                                  'aria-label' => Yii::t('yii', 'Update'),
                                  'data-pjax' => '0',
                                  'class' => 'btn btn-info btn-sm '
                              ];
                              return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, $options);
                          },
                          'delete' => function ($url, $model, $key) {
                              $options = [
                                  'title' => Yii::t('yii', 'Delete'),
                                  'aria-label' => Yii::t('yii', 'Delete'),
                                  'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                  'data-method' => 'post',
                                  'data-pjax' => '0',
                                  'class' => 'btn btn-danger btn-sm '
                              ];
                              return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, $options);
                          },
                      ]
                  ],
              ],
          ]); ?>
      </div>
    </div>
  </div>
