<?php

namespace app\modules\base\models;

use Yii;
use app\modules\base\models\WorkConfig;
use app\modules\base\models\WorkUser;

/**
 * This is the model class for table "{{%work_odd}}".
 *
 */
class BaseModel extends \yii\db\ActiveRecord {

    /**
     * 根据类型列出配置列表
     * @param string $type
     * @return type
     */
    public function getSysConfigInfoType($type) {
        $array = [];
        $arrObj = WorkConfig::find()
                ->select(['strKey', 'strValue'])
                ->where(['strType' => $type])
                ->all();
        foreach ($arrObj as $v) {
            $array[$v->strKey] = $v->strValue;
        }
        return $array;
    }

    /**
     * 获取具体配置信息
     * @param type $type
     * @param type $key
     * @return string
     */
    public function getSysConfigInfoTypeValue($type, $key) {
        $arData = $this->getSysConfigInfoType($type);
        if (empty($arData[$key])) {
            return "未选择";
        } else {
            return $arData[$key];
        }
    }

    /**
     * 获取用户真实姓名
     * @param type $strUserId
     * @return type
     */
    public function getUserInfo($strUserId) {
        $objUser = WorkUser::findOne(['strUserId' => $strUserId]);
        $nickName = empty($objUser) ? "" : $objUser->nickName;
        return $nickName;
    }

    /**
     * 获取微信token值
     * @return type
     */
    public function getToken() {
        $arRow = WorkConfig::findOne(['strKey' => 'strToken']);
        if (empty($arRow)) {
            return false;
        } else {
            return $arRow->strValue;
        }
    }

    /**
     * 新增资料
     * @param model $model          数据模型
     * @param array $arrData        工作信息资料内容
     * @return array
     */
    public function create_data($model, $arData) {
        try {
            foreach ($arData as $k => $v) {
                if (array_key_exists($k, $model->attributeLabels())) {
                    $model->$k = $v;
                }
            }
            $model->tCreateTime = date("Y-m-d H:i:s");
            $model->tUpdateTime = date("Y-m-d H:i:s");
            $bStatus = $model->save();
            if ($bStatus) {
                $arMsg = $this->setReturnMsg('0000', $model->primaryKey);
            } else {
                $arMsg = $this->setReturnMsg('1002', json_encode($model->getErrors()));
            }
            return $arMsg;
        } catch (\Exception $ex) {
            exit($ex->getTraceAsString());
        }
    }

    /**
     * 编辑资料
     * @param model $model      数据模型
     * @param array $arrData    工作信息资料内容
     * @param array $unArray    需要忽略的键值
     * @return array
     */
    public function edit_data($model, $arData, $unArray = []) {
        try {
            //$model = SysUserOffice::findOne(['id' => $id]);
            if (empty($model)) {
                $arMsg = $this->setReturnMsg('1004');
            } else {
                foreach ($arData as $k => $v) {
                    if (!in_array($k, $unArray)) { //忽略键值
                        if (array_key_exists($k, $model->attributeLabels())) {
                            $model->$k = $v;
                        }
                    }
                }
                $model->tUpdateTime = date("Y-m-d H:i:s");
                $bStatus = $model->save();
                if ($bStatus) {
                    $arMsg = $this->setReturnMsg('0000', $model->primaryKey);
                } else {
                    $arMsg = $this->setReturnMsg('1002', json_encode($model->getErrors()));
                }
            }
            return $arMsg;
        } catch (\Exception $ex) {
            exit($ex->getTraceAsString());
        }
    }

    /**
     * 设置返回数据格式
     * @param string $strStatus 返回的状态码
     * @param array $arrContent 回调数据
     * @return type
     */
    public function setReturnMsg($strStatus, $arrContent = []) {
        $msg['ret'] = $strStatus;
        $msg['content'] = $arrContent;
        return $msg;
    }

}
