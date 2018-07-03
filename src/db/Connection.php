<?php

namespace choate\yii2\components\db;

use yii\db\Connection AS YiiConnection;

class Connection extends YiiConnection
{
    /**
     * @event Event an event that is triggered right before a top-level transaction is committed
     */
    const EVENT_BEFORE_COMMIT_TRANSACTION = 'beforeCommitTransaction';

    /**
     * @inheritdoc
     */
    public function beginTransaction($isolationLevel = null)
    {
        $this->open();

        if (($transaction = $this->getTransaction()) === null) {
            $transaction = new Transaction(['db' => $this]);
            $this->setTransaction($transaction);
        }
        $transaction->begin($isolationLevel);

        return $transaction;
    }

    private function setTransaction(\yii\db\Transaction $transaction)
    {
        $class = get_class($this);
        $reflect = new \ReflectionClass($class);
        $parentReflect = $reflect->getParentClass();
        $property = $parentReflect->getProperty('_transaction');
        $property->setAccessible(true);
        $property->setValue($this, $transaction);
    }
}