<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\controllers\BaseController;
use app\common\baofu\Tools;
use app\common\baofu\Log;
use app\common\baofu\BFRSA;
use app\common\baofu\HttpClient;
use app\modules\base\models\BaofuRequest;
use app\modules\base\models\BaofuResult;
use app\modules\base\models\BaofuSearch;
use app\modules\base\models\WorkUserWithhold;
use yii\base\Exception;
use app\common\NetWork;
use app\common\Str;

/**
 * Default controller for the `api` module
 */
class BaofuController extends BaseController {

    public $strTitle = '宝付代扣';

    public function actionEditStatus() {
        $strUserId = Yii::$app->request->post('strUserId'); //用户ID
        if (!empty($strUserId)) {
            $model = WorkUserWithhold::findOne(['strUserId' => $strUserId]);
            $arData['strStatus'] = "1";
            $arRes = $model->edit_data($model, $arData);
            $arReturn = NetWork::setMsg($this->strTitle, "修改状态", $arRes['ret'], []);
            Str::echoJson($arReturn);
        }
    }

    /**
     * 绑定用户银行卡
     */
    public function actionBindUserBank() {
        $strPhone = Yii::$app->request->post('phone'); //手机号
        $strName = Yii::$app->request->post('name'); //真实姓名
        $strBankCode = Yii::$app->request->post('bankCode'); //银行卡编码
        $strBankNum = Yii::$app->request->post('bankNum'); //银行卡号
        $strCardNum = Yii::$app->request->post('cardnum'); //身份证号
        $fMoney = empty(Yii::$app->request->post('money')) ? '0.01' : Yii::$app->request->post('money'); //身份证号 0.01;
        if (!empty($strPhone) && !empty($strName) && !empty($strBankNum) && !empty($strCardNum)) {
            $strJson = $this->setPayData($strName, $strCardNum, $strBankCode, $strBankNum, $strPhone, $fMoney);
            $arData = json_decode($strJson, true);
            $arReturn = NetWork::setMsg($this->strTitle, $arData['resp_msg'], $arData['resp_code'], []);
        } else {
            $arReturn = NetWork::setMsg($this->strTitle, '参数不能为空', '4001', []);
        }
        Str::echoJson($arReturn);
    }

    /**
     * 代扣金额
     * @param string $strPhone      //手机号
     * @param string $strName       //真实姓名
     * @param string $strBankCode   //银行卡编码
     * @param string $strBankNum    //银行卡号
     * @param string $strCardNum    //身份证号
     * @param float $fMoney         //代扣金额
     */
    public function actionWithholdUserMoney($strPhone, $strName, $strBankCode, $strBankNum, $strCardNum, $fMoney) {
        if (!empty($strPhone) && !empty($strName) && !empty($strBankNum) && !empty($strCardNum) && !empty($fMoney)) {
            $strJson = $this->setPayData($strName, $strCardNum, $strBankCode, $strBankNum, $strPhone, $fMoney);
            //$strJson = '{"additional_info":"附加字段","biz_type":"0000","data_type":"json","member_id":"1191123","req_reserved":"保留","resp_code":"0000","resp_msg":"交易成功","succ_amt":"1","terminal_id":"36452","trade_date":"20180220113639","trans_id":"TI15190977991129","trans_no":"201802200110001690104455","trans_serial_no":"TSN15190977998804","txn_sub_type":"13","txn_type":"0431","version":"4.0.0.0"}';
            $arData = json_decode($strJson, true);
            $arReturn = NetWork::setMsg($this->strTitle, $arData['resp_msg'], $arData['resp_code'], []);
        } else {
            $arReturn = NetWork::setMsg($this->strTitle, '参数不能为空', '4001', []);
        }
        return $arReturn;
    }

    /**
     * 查询
     */
    public function actionSearchUserBank() {
        $trans_id = $this->setTransId(); //商户订单号
        $orig_trans_id = Yii::$app->request->post('orig_trans_id'); //"TI15178879016149";
        $orig_trade_date = Yii::$app->request->post('orig_trade_date'); //"20180206113141";
        if (!empty($orig_trans_id) && !empty($orig_trade_date)) {
            $strJson = $this->setSearchData($trans_id, $orig_trans_id, $orig_trade_date);
            $arData = json_decode($strJson, true);
            $status = ('S' == trim($arData['order_stat'])) ? '0000' : $arData['order_stat'];
            $arReturn = NetWork::setMsg($this->strTitle, $arData['resp_msg'], $status, []);
        } else {
            $arReturn = NetWork::setMsg($this->strTitle, '参数不能为空', '4001', []);
        }
        Str::echoJson($arReturn);
    }

    /**
     * 需处理的json字符串
     * @param string $txn_sub_type	提交类型
     * @param string $Encrypted_string
     * @throws Exception
     */
    public function postData($txn_sub_type, $Encrypted_string) {
        $BFRsa = new BFRSA(\Yii::$app->params['baofu']["pfx_file_name"], \Yii::$app->params['baofu']["cer_file_name"], \Yii::$app->params['baofu']["private_key_password"]); //实例化加密类。
        $Encrypted = $BFRsa->encryptedByPrivateKey($Encrypted_string); //先BASE64进行编码再RSA加密
        $PostArry = array("version" => \Yii::$app->params['baofu']['version'],
            "terminal_id" => \Yii::$app->params['baofu']["terminal_id"],
            "txn_type" => \Yii::$app->params['baofu']["txn_type"],
            "txn_sub_type" => $txn_sub_type,
            "member_id" => \Yii::$app->params['baofu']["member_id"],
            "data_type" => \Yii::$app->params['baofu']["data_type"],
            "data_content" => $Encrypted);
        $return = HttpClient::Post($PostArry, \Yii::$app->params['baofu']['url']);  //发送请求到宝付服务器，并输出返回结果。
        Log::LogWirte("请求返回参数：" . $return);
        if (empty($return)) {
            throw new Exception("返回为空，确认是否网络原因！");
        }
        $return_decode = $BFRsa->decryptByPublicKey($return); //解密返回的报文
        Log::LogWirte("解密结果：" . $return_decode);
        $endata_content = array();
        if (!empty($return_decode)) {//解析XML、JSON
            $endata_content = json_decode($return_decode, TRUE);
        }
        if ('13' == $txn_sub_type) {
            (new BaofuResult())->add($endata_content);
        } else {
            (new BaofuSearch())->add($endata_content);
        }
        if (is_array($endata_content) && (count($endata_content) > 0)) {
            if (array_key_exists("resp_code", $endata_content)) {
                if ($endata_content["resp_code"] == "0000") {
                    $return_decode = json_encode($endata_content, JSON_UNESCAPED_UNICODE);
                    //$return_decode = "订单状态码：" . $endata_content["resp_code"] . ", 商户订单号：" . $endata_content["trans_id"] . ", 返回消息：" . $endata_content["resp_msg"] . json_encode($endata_content, JSON_UNESCAPED_UNICODE);
                } else {
                    //错误或失败其他状态
                    $return_decode = json_encode($endata_content, JSON_UNESCAPED_UNICODE);
                    //$return_decode = "订单状态码：" . $endata_content["resp_code"] . ", 商户订单号：" . $endata_content["trans_id"] . ", 返回消息：" . $endata_content["resp_msg"] . json_encode($endata_content, JSON_UNESCAPED_UNICODE);
                }
                return $return_decode; //输出
            } else {
                throw new Exception("[resp_code]返回码不存在!");
            }
        }
    }

    /**
     * 构造代扣数据
     * @param type $strName     真实姓名
     * @param type $strCardNum  身份证号码
     * @param type $strBankCode 银行CODE
     * @param type $strBankNum  银行卡号
     * @param type $strPhone    绑定手机
     * @param real $fMoney      代扣金额
     * @return type
     */
    public function setPayData($strName, $strCardNum, $strBankCode, $strBankNum, $strPhone, $fMoney = 0.01) {
        $txn_sub_type = 13;
        $trans_id = $this->setTransId(); //商户订单号
        $biz_type = "0000"; //接入类型
        $id_card_type = "01"; //证件类型固定01（身份证） 
        $acc_pwd = ""; //银行卡密码（传空）
        $valid_date = ""; //卡有效期 （传空）
        $valid_no = ""; //卡安全码（传空）
        $additional_info = "附加字段"; //附加字段
        $req_reserved = "保留"; //保留
        $pay_cm = "2"; //1:不进行信息严格验证,2:对四要素
//====================系统动态生成值=======================================
        $trans_serial_no = "TSN" . Tools::getTransid() . Tools::getRand4(); //商户流水号
        $trade_date = Tools::getTime(); //订单日期
        $data_content_parms = array('txn_sub_type' => $txn_sub_type,
            'biz_type' => $biz_type,
            'terminal_id' => \Yii::$app->params['baofu']['terminal_id'],
            'member_id' => \Yii::$app->params['baofu']['member_id'],
            'trans_serial_no' => $trans_serial_no,
            'trade_date' => $trade_date,
            'additional_info' => $additional_info,
            'req_reserved' => $req_reserved);
        $txn_amt = isset($fMoney) ? trim($fMoney) : ""; //交易金额额
        $txn_amt *= 100; //金额以分为单位（把元转成分）
        $data_content_parms["pay_code"] = $strBankCode;
        $data_content_parms['pay_cm'] = $pay_cm;
        $data_content_parms["acc_no"] = $strBankNum;
        $data_content_parms["id_card_type"] = $id_card_type;
        $data_content_parms["id_card"] = $strCardNum;
        $data_content_parms["id_holder"] = $strName;
        $data_content_parms["mobile"] = $strPhone;
        $data_content_parms["valid_date"] = $valid_date;
        $data_content_parms["valid_no"] = $valid_no;
        $data_content_parms["trans_id"] = $trans_id;
        $data_content_parms["txn_amt"] = $txn_amt;
        (new BaofuRequest())->add($data_content_parms);
        $Encrypted_string = str_replace("\\/", "/", json_encode($data_content_parms)); //转JSON
        Log::LogWirte("序列化结果：" . $Encrypted_string);
        $strMsg = $this->postData($txn_sub_type, $Encrypted_string);
        return $strMsg;
    }

    /**
     * 构造查询数据
     * @param string $trans_id          商户订单号
     * @param string $orig_trans_id     原始商户订单号
     * @param string $orig_trade_date   原始订单日期
     * @return string
     */
    public function setSearchData($trans_id, $orig_trans_id, $orig_trade_date) {
        $txn_sub_type = 31;
        $biz_type = "0000"; //接入类型
        $additional_info = "附加字段"; //附加字段
        $req_reserved = "保留"; //保留
//====================系统动态生成值=======================================
        $trans_serial_no = "TSN" . Tools::getTransid() . Tools::getRand4(); //商户流水号
        $trade_date = Tools::getTime(); //订单日期

        $data_content_parms = array('txn_sub_type' => $txn_sub_type,
            'biz_type' => $biz_type,
            'terminal_id' => \Yii::$app->params['baofu']['terminal_id'],
            'member_id' => \Yii::$app->params['baofu']['member_id'],
            'trans_serial_no' => $trans_serial_no,
            'trade_date' => $trade_date,
            'additional_info' => $additional_info,
            'req_reserved' => $req_reserved);

        $orig_trans_id = isset($orig_trans_id) ? trim($orig_trans_id) : ""; //原始商户订单号
        $orig_trade_date = isset($orig_trade_date) ? trim($orig_trade_date) : ""; //订单日期
        $data_content_parms["orig_trans_id"] = $orig_trans_id;
        $data_content_parms["orig_trade_date"] = $orig_trade_date;
        $Encrypted_string = str_replace("\\/", "/", json_encode($data_content_parms)); //转JSON
        Log::LogWirte("序列化结果：" . $Encrypted_string);
        $strMsg = $this->postData($txn_sub_type, $Encrypted_string);
        return $strMsg;
    }

    /**
     * 生成商户订单号
     * @param type $trans_id
     * @return type
     */
    public function setTransId($trans_id = "") {
        return empty($trans_id) ? "TI" . Tools::getTransid() . Tools::getRand4() : trim($trans_id); //商户订单号
    }

    public function actionTxt() {
        $money = 200000;
        $rate = 0.09;
        $a = \app\common\MathPayment::PayInterest($money, $rate, 2, 'month');
        print_r($a);
    }

}
