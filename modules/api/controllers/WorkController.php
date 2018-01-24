<?php

namespace app\modules\api\controllers;

use app\modules\api\controllers\BaseController;
use app\modules\base\models\WorkApply;
use app\common\Str;
use app\common\NetWork;

/**
 * Default controller for the `api` module
 */
class WorkController extends BaseController {

    public $strTitle = '车险申请';
    public $strUserId = '2018012400000001';

    /**
     * 流程申请第一，填写用户资料
     */
    public function actionWorkUserData() {
        $arPost = \Yii::$app->request->post();
        $model = new WorkApply();
        unset($arPost['state']);
        $arPost['strUserId'] = $this->strUserId;
        $arMsg = $model->add($arPost);
        $strMsg = ('0000' == $arMsg['ret']) ? '成功' : '失败';
        $arReturn = NetWork::setMsg($this->strTitle, $strMsg, $arMsg['ret'], $arMsg['content']);
        Str::echoJson($arReturn);
    }

    /**
     * 流程申请第二，选择险种
     */
    public function actionWorkInsuranceData() {
        $arPost = \Yii::$app->request->post();
        $model = new WorkApply();
        $arMsg = $model->edit($arPost['strWorkNum'], $arPost);
        $strMsg = ('0000' == $arMsg['ret']) ? '成功' : '失败';
        $arReturn = NetWork::setMsg($this->strTitle, $strMsg, $arMsg['ret'], $arMsg['content']);
        Str::echoJson($arReturn);
    }

    /**
     * 流程申请第三，选择保险公司
     */
    public function actionWorkOfficeData() {
        $arPost = \Yii::$app->request->post();
        $model = new WorkApply();
        $arMsg = $model->edit($arPost['strWorkNum'], $arPost);
        $strMsg = ('0000' == $arMsg['ret']) ? '成功' : '失败';
        $arReturn = NetWork::setMsg($this->strTitle, $strMsg, $arMsg['ret'], $arMsg['content']);
        Str::echoJson($arReturn);
    }
    /**
     * 流程申请第四，上传证件照片
     */
    public function actionWorkUserCard() {
        $arPost = \Yii::$app->request->post();
        $model = new WorkApply();
        $arMsg = $model->edit($arPost['strWorkNum'], $arPost);
        $strMsg = ('0000' == $arMsg['ret']) ? '成功' : '失败';
        $arReturn = NetWork::setMsg($this->strTitle, $strMsg, $arMsg['ret'], $arMsg['content']);
        Str::echoJson($arReturn);
    }

    /**
     * 证件图片上传
     */
    public function actionWorkUserImage() {
        $arPost = \Yii::$app->request->post();
        $key = 'file';
        if (isset($_FILES[$key])) {
            $url = '/upload/' . $_FILES[$key]['name'];
            $bStatus = move_uploaded_file($_FILES[$key]['tmp_name'], '.' . $url);
            if ($bStatus) {
                $ret = '0000';
                $strMsg = '成功';
            } else {
                $ret = '1000';
                $strMsg = '失败';
            }
            $arReturn = NetWork::setMsg($this->strTitle, $strMsg, $ret, ['url' => $url, 'name' => $arPost['name']]);
            Str::echoJson($arReturn);
        } else {
            $arReturn = NetWork::setMsg($this->strTitle, '失败', '1001', []);
            Str::echoJson($arReturn);
        }
    }

}