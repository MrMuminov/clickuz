<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;


class ClickTransactions extends ActiveRecord
{
    const STATUS_CANCEL = -1;
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'click_transactions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'click_trans_id', 'amount', 'click_paydoc_id', 'service_id', 'sign_time', 'status', 'create_time'], 'required'],
            [['merchant_prepare_id', 'user_id', 'click_trans_id', 'click_paydoc_id', 'service_id', 'status', 'create_time'], 'integer'],
            [['amount'], 'number'],
            [['sign_time'], 'string', 'max' => 63],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                => Yii::t('click', 'ID'),
            'user_id'           => Yii::t('click', 'User ID'),
            'click_trans_id'    => Yii::t('click', 'Click Trans ID'),
            'amount'            => Yii::t('click', 'Amount'),
            'click_paydoc_id'   => Yii::t('click', 'Click Paydoc ID'),
            'service_id'        => Yii::t('click', 'Service ID'),
            'sign_time'         => Yii::t('click', 'Sign Time'),
            'status'            => Yii::t('click', 'Status'),
            'create_time'       => Yii::t('click', 'Create Time'),
        ];
    }
}



?>