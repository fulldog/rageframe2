<?php
/**
 * Created by PhpStorm.
 * User: weilone
 * Date: 2018/12/25
 * Time: 21:51
 */

namespace wechat\controllers;


class MyController extends WController
{

    function init()
    {
        $this->view->params['description'] = '';
        $this->view->params['title'] = '';
        return parent::init(); // TODO: Change the autogenerated stub
    }
}