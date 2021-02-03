<?php

namespace app\controllers;


use app\components\ClickData;
use app\models\ClickTransactions;
use app\models\Orders;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

class ClickController extends Controller
{

    private $reqData = [];
    private $user; // init into validateData()
    private $userID = 5;


    public function beforeAction($action)
    {
        if ($action->id == "prepare" || $action->id == "complete") {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * @return string|int|array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionPrepare()
    {
        Yii::$app->responce->format = Response::FORMAT_JSON;
        $this->reqData = Yii::$app->request->post();
        $this->validateData();
        $checkExists = ClickTransactions::find()
            ->where(['click_trans_id' => $this->reqData['click_trans_id']])->one();

        if ($checkExists !== NULL) {
            if ($checkExists->status == ClickTransactions::STATUS_CANCEL) {
                //Transaction cancelled
                return ClickData::getMessage(ClickData::ERROR_TRANSACTION_CANCELLED);
            } else {
                // Already paid
                return ClickData::getMessage(ClickData::ERROR_ALREADY_PAID);
            }
        }

        //Error in request from click
        if (!$this->reqData['error'] == 0) {
            return ClickData::getMessage(ClickData::ERROR_ERROR_REQUEST_CLICK);
        }
        $newTransaction = new ClickTransactions;
        $newTransaction->user_id = $this->reqData['merchant_trans_id'];
        $newTransaction->click_trans_id = $this->reqData['click_trans_id'];
        $newTransaction->service_id = $this->reqData['service_id'];
        $newTransaction->amount = $this->reqData['amount'];
        $newTransaction->sign_time = $this->reqData['sign_time'];
        $newTransaction->click_paydoc_id = $this->reqData['click_paydoc_id'];
        $newTransaction->create_time = time();
        $newTransaction->status = ClickTransactions::STATUS_INACTIVE;

        if ($newTransaction->save(false)) {

            $merchant_prepare_id = $newTransaction->id;
            $return_array = array(
                'click_trans_id' => $this->reqData['click_trans_id'],        // ID Click Trans
                'merchant_trans_id' => $this->reqData['merchant_trans_id'],  // ID платежа в биллинге Поставщика
                'merchant_prepare_id' => $merchant_prepare_id                // ID платежа для подтверждения
            );

            $result = array_merge(ClickData::getMessage(ClickData::ERROR_SUCCESS), $return_array);
            return $result;
        }
        // other case report: Unknown Error
        return 1;
    }

    private function validateData()
    {
        Yii::$app->responce->format = Response::FORMAT_JSON;
        //check complete parameters: Unknown Error
        if ((!isset($this->reqData['click_trans_id'])) ||
            (!isset($this->reqData['service_id'])) ||
            (!isset($this->reqData['click_paydoc_id'])) ||
            (!isset($this->reqData['merchant_trans_id'])) ||
            (!isset($this->reqData['amount'])) ||
            (!isset($this->reqData['action'])) ||
            (!isset($this->reqData['sign_time'])) ||
            (!isset($this->reqData['sign_string'])) ||
            (!isset($this->reqData['error']))
        ) {

            return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
        }

        // Формирование ХЭШ подписи
        $sign_string_veryfied = md5(
            $this->reqData['click_trans_id'] .
            $this->reqData['service_id'] .
            ClickData::$secretKey .
            $this->reqData['merchant_trans_id'] .
            (($this->reqData['action'] == 1) ? $this->reqData['merchant_prepare_id'] : '') . // TODO 1 is hard code
            $this->reqData['amount'] .
            $this->reqData['action'] .
            $this->reqData['sign_time']
        );

        if ($this->reqData['sign_string'] != $sign_string_veryfied) {
            return ClickData::getMessage(ClickData::ERROR_FAILED_SIGN);
        }

        // Check Actions: Action not found
        if (!in_array($this->reqData['action'], [0, 1])) { // TODO 0 and 1 is hard code
            return ClickData::getMessage(ClickData::ERROR_ACTION_NOT_FOUND);
        }

        // Check sum: Incorrect parameter amount
        if (($this->reqData['amount'] < ClickData::$minAmount) || ($this->reqData['amount'] > ClickData::$maxAmount)) {
            return ClickData::getMessage(ClickData::ERROR_INCORRECT_AMOUNT);
        }

        $this->user = User::findOne($this->reqData['merchant_trans_id']);
        if ($this->user === NULL) {
            // User does not exist
            return ClickData::getMessage(ClickData::ERROR_USER_NOT_FOUND);
        }
    }

    public function actionComplete()
    {
        Yii::$app->responce->format = Response::FORMAT_JSON;
        $this->reqData = Yii::$app->request->post();

        //if not validated it is end point
        //-------------------------------------------
        $this->validateData();

        //-------------------------------------------
        //Error in request from click

        if (empty($this->reqData['merchant_prepare_id'])) {
            return ClickData::getMessage(ClickData::ERROR_ERROR_REQUEST_CLICK);
        }

        // --------------------------------------------------------------------------- Start trasaction DB
        $transaction = ClickTransactions::findOne(
            [
                'id' => $this->reqData['merchant_prepare_id'],
                'user_id' => $this->reqData['merchant_trans_id'],
                'click_trans_id' => $this->reqData['click_trans_id'],
                'click_paydoc_id' => $this->reqData['click_paydoc_id'],
                'service_id' => $this->reqData['service_id'],
            ]
        );

        if ($transaction !== NULL) {
            if ($this->reqData['error'] == 0) { // TODO 0 is hard code
                if ($this->reqData['amount'] == $transaction->amount) {
                    if ($transaction->status == ClickTransactions::STATUS_INACTIVE) {
                        $db = Yii::$app->db;
                        $db_transaction = $db->beginTransaction();
                        $transaction->status = ClickTransactions::STATUS_ACTIVE;

                        if (!$transaction->save(false)) {
                            $db_transaction->rollback();
                            return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
                        }
                        $db_transaction->commit();

                        $order = Orders::findOne($transaction->user_id);
                        // if pay success -> Change Order status to 2
                        if (!empty($order)) {
                            $order->state = 2; // TODO this [2] change your order payy
                            $order->save(false);
                        }
                        $return_array = [
                            'click_trans_id' => $transaction->click_trans_id,
                            'merchant_trans_id' => $transaction->user_id,
                            'merchant_confirm_id' => $transaction->id,
                        ];
                        $result = array_merge(ClickData::getMessage(ClickData::ERROR_SUCCESS), $return_array);
                        return $result;
                    } elseif ($transaction->status == ClickTransactions::STATUS_CANCEL) {
                        //"Transaction cancelled"
                        return ClickData::getMessage(ClickData::ERROR_TRANSACTION_CANCELLED);
                    } elseif ($transaction->status == ClickTransactions::STATUS_ACTIVE) {
                        return ClickData::getMessage(ClickData::ERROR_ALREADY_PAID);
                    } else return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
                } else {
                    if ($transaction->status == ClickTransactions::STATUS_INACTIVE)
                        //$transaction->delete();
                        //"Incorrect parameter amount"
                        return ClickData::getMessage(ClickData::ERROR_INCORRECT_AMOUNT);
                }
            } elseif ($this->reqData['error'] < 0) {
                if ($this->reqData['error'] == -5017) {
                    // "Transaction cancelled"
                    if ($transaction->status != ClickTransactions::STATUS_ACTIVE) {
                        $transaction->status = ClickTransactions::STATUS_CANCEL;
                        if ($transaction->save(false)) {
                            // "Transaction cancelled"
                            $this->send_mail_complete($this->reqData, true);
                            return ClickData::getMessage(ClickData::ERROR_TRANSACTION_CANCELLED);
                        }
                        return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
                    } else return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
                } elseif ($this->reqData['error'] == -1 && $transaction->status == ClickTransactions::STATUS_ACTIVE) {
                    return ClickData::getMessage(ClickData::ERROR_ALREADY_PAID);
                } else return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
            } // error > 0
            else {
                return ClickData::getMessage(ClickData::ERROR_UNKNOWN);
            }
        } // Transaction is null
        else {
            // Transaction does not exist
            return ClickData::getMessage(ClickData::ERROR_TRANSACTION_NOT_FOUND);
        }
        // echo "Hello from Complete // ";
        print_r(ClickData::getMessage(ClickData::ERROR_SUCCESS)); // TODO Nazirovlix nima uchun print_r
        // var_dump(ClickData::$messages);
    }

    private function send_mail_complete($data, $notcomplete = false)
    {
        if (!$notcomplete) {
            $message = "<p>" .Yii::t('click', "Message"). "</p>";
            $subject_text = Yii::t('click', 'Оплата CLICK');
        } else {
            $message = "<p>" .Yii::t('click', "Message"). "</p>";
            $subject_text = Yii::t('click', 'Отмена CLICK');
        }
        Yii::$app->mailer->compose()
            ->setFrom('')
            ->setTo([''])
            ->setSubject($subject_text)
            ->setHtmlBody($message)
            ->send();
    }

    /**
     * Lists all ClickTransactions models.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('click');
    }

}