<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/27
 * Time: 11:59
 */

namespace choate\yii2\components\db;

use yii\db\Exception;
use yii\db\Transaction AS YiiTransaction;
use Yii;

class Transaction extends YiiTransaction
{
    /**
     * @inheritdoc
     */
    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->setLevel($this->getLevel() - 1);
        if ($this->getLevel() === 0) {
            Yii::debug('Commit transaction', __METHOD__);
            $this->beforeCommit();
            $this->db->pdo->commit();
            $this->afterCommit();
            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::debug('Release savepoint ' . $this->getLevel(), __METHOD__);
            $schema->releaseSavepoint('LEVEL' . $this->getLevel());
        } else {
            Yii::info('Transaction not committed: nested transaction not supported', __METHOD__);
        }
    }

    protected function beforeCommit()
    {
        $this->setLevel(1);
        $this->db->trigger(Connection::EVENT_BEFORE_COMMIT_TRANSACTION);
        $this->setLevel(0);
    }

    protected function afterCommit()
    {
        $this->db->trigger(Connection::EVENT_COMMIT_TRANSACTION);
    }

    private function setLevel($value)
    {
        $class = get_class($this);
        $reflect = new \ReflectionClass($class);
        $parentReflect = $reflect->getParentClass();
        $property = $parentReflect->getProperty('_level');
        $property->setAccessible(true);
        $property->setValue($this, $value);
    }
}