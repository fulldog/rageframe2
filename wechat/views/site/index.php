<?php

$this->title = 'Rageframe For Wechat';
?>

<style>
    html, body {
        background-color: #fff;
        color: #636b6f;
        font-family: 'Raleway', sans-serif;
        font-weight: 100;
        height: 100vh;
        margin: 0;
    }

    .full-height {
        height: 100vh;
    }

    .flex-center {
        align-items: center;
        display: flex;
        justify-content: center;
    }

    .position-ref {
        position: relative;
    }

    .top-right {
        position: absolute;
        right: 10px;
        top: 18px;
    }

    .content {
        text-align: center;
    }

    .title {
        font-size: 84px;
    }

    .links > a {
        color: #636b6f;
        padding: 0 25px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .1rem;
        text-decoration: none;
        text-transform: uppercase;
    }

    .m-b-md {
        margin-bottom: 30px;
    }
</style>

<div class="flex-center position-ref full-height">
    <div class="content">
        <div class="title m-b-md">
            Wechat
        </div>
        <div class="links">
            <a href="#">欢迎进入微信应用</a>
        </div>
      <div class="links">
        <a href="<?php echo Yii::$app->urlManager->createUrl(['site/jssdk']);?>">jssdk-H5支付</a>
      </div>
      <div class="links">
        <a href="<?php echo Yii::$app->urlManager->createUrl(['site/invoke']);?>">invoke支付</a>
      </div>
    </div>
</div>