<?php

namespace app\components;


class ClickData {

    const ERROR_SUCCESS = '0';
    const ERROR_FAILED_SIGN = '1';
    const ERROR_INCORRECT_AMOUNT = '2';
    const ERROR_ACTION_NOT_FOUND = '3';
    const ERROR_ALREADY_PAID = '4';
    const ERROR_USER_NOT_FOUND = '5';
    const ERROR_TRANSACTION_NOT_FOUND = '6';
    const ERROR_FAILED_UPDATE_USER = '7';
    const ERROR_ERROR_REQUEST_CLICK = '8';
    const ERROR_TRANSACTION_CANCELLED = '9';
    const ERROR_UNKNOWN = 'n';

    public static $secretKey = "<KEY>";
    public static $minAmount = 100;
    public static $maxAmount = 1000000;


    public static function getMessage($value)
    {
        $messages = [
            self::ERROR_SUCCESS                 => ["error" => "0",   "error_note" => Yii::t('click',"Success")],
            self::ERROR_FAILED_SIGN             => ["error" => "-1",  "error_note" => Yii::t('click',"SIGN CHECK FAILED!")],
            self::ERROR_INCORRECT_AMOUNT        => ["error" => "-2",  "error_note" => Yii::t('click',"Incorrect parameter amount")],
            self::ERROR_ACTION_NOT_FOUND        => ["error" => "-3",  "error_note" => Yii::t('click',"Action not found")],
            self::ERROR_ALREADY_PAID            => ["error" => "-4",  "error_note" => Yii::t('click',"Already paid")],
            self::ERROR_USER_NOT_FOUND          => ["error" => "-5",  "error_note" => Yii::t('click',"User does not exist")],
            self::ERROR_TRANSACTION_NOT_FOUND   => ["error" => "-6",  "error_note" => Yii::t('click',"Transaction does not exist")],
            self::ERROR_FAILED_UPDATE_USER      => ["error" => "-7",  "error_note" => Yii::t('click',"Failed to update user")],
            self::ERROR_ERROR_REQUEST_CLICK     => ["error" => "-8",  "error_note" => Yii::t('click',"Error in request from click")],
            self::ERROR_TRANSACTION_CANCELLED   => ["error" => "-9",  "error_note" => Yii::t('click',"Transaction cancelled")],
            self::ERROR_UNKNOWN                 => ["error" => "-n",  "error_note" => Yii::t('click',"Unknown Error")],
        ];
        return isset($messages[$value]) ? $messages[$value] : $messages[self::ERROR_UNKNOWN];
    }
}




?>