<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%click_transactions}}`.
 */
class m210204010235_create_click_transaction_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%click_transactions}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'click_trans_id' => $this->integer(50),
            'amount' => $this->integer(50),
            'click_paydoc_id' => $this->integer(),
            'service_id' => $this->integer(),
            'sign_time' => $this->string(30),
            'status' => $this->tinyInteger(),
            'create_time' => $this->integer(),
        ]);
        $this->createIndex("idx-click_transactions-user_id",
            '{{%click_transactions}}', 'user_id');
        $this->createIndex("idx-click_transactions-click_trans_id",
            '{{%click_transactions}}', 'click_trans_id');
        $this->createIndex("idx-click_transactions-click_paydoc_id",
            '{{%click_transactions}}', 'click_paydoc_id');
        $this->createIndex("idx-click_transactions-service_id",
            '{{%click_transactions}}', 'service_id');
        $this->createIndex("idx-click_transactions-status",
            '{{%click_transactions}}', 'status');
        $this->createIndex("idx-click_transactions-create_time",
            '{{%click_transactions}}', 'create_time');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex("idx-click_transactions-user_id",
            '{{%click_transactions}}');
        $this->dropIndex("idx-click_transactions-click_trans_id",
            '{{%click_transactions}}');
        $this->dropIndex("idx-click_transactions-click_paydoc_id",
            '{{%click_transactions}}');
        $this->dropIndex("idx-click_transactions-service_id",
            '{{%click_transactions}}');
        $this->dropIndex("idx-click_transactions-status",
            '{{%click_transactions}}');
        $this->dropIndex("idx-click_transactions-create_time",
            '{{%click_transactions}}');
        $this->dropTable('{{%click_transactions}}');
    }
}
