<?php

namespace choate\yii2\components\db;

use yii\base\ErrorException;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\db\ActiveRecord AS YiiActiveRecord;
use yii\helpers\ArrayHelper;
use Yii;

class ActiveRecord extends YiiActiveRecord
{

    private $triggerEventItems;

    /**
     * 批量删除
     *
     * @param self[] $models
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidCallException
     * @throws \yii\db\Exception
     */
    public static function batchDelete($models)
    {
        if (empty($models)) {
            return false;
        }
        foreach ($models as $model) {
            if ($model->getIsNewRecord()) {
                throw new InvalidCallException('"$models" 包含新记录');
            }
        }
        $first = reset($models);
        if (!$first->isTransactional(static::OP_DELETE)) {
            return static::batchDeleteInternal($models);
        }
        $transaction = static::getDb()->beginTransaction();
        try {
            $result = static::batchDeleteInternal($models);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }

            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

    }

    /**
     * 批量插入数据
     *
     * @param self[] $models
     * @param bool $runValidator
     * @param array|string|null $attributes
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidCallException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\InvalidParamException
     * @throws \yii\db\Exception
     */
    public static function batchInsert(&$models, $runValidator = true, $attributes = null)
    {
        if (empty($models)) {
            return false;
        }
        if ($runValidator && !static::validateMultiple($models, $attributes)) {
            return false;
        }
        $first = reset($models);
        if (!$first->isTransactional(static::OP_INSERT)) {
            return static::batchInsertInternal($models, $attributes);
        }
        $transaction = static::getDb()->beginTransaction();
        try {
            $result = static::batchInsertInternal($models, $attributes);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }

            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

    }

    /**
     * 批量更新
     *
     * @param self[] $models
     * @param bool $runValidator
     * @param null $attributes
     *
     * @author ${author}
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidParamException
     * @throws \yii\db\Exception
     */
    public static function batchUpdate($models, $runValidator = true, $attributes = null)
    {
        if (empty($models)) {
            return false;
        }
        if ($runValidator && !static::validateMultiple($models, $attributes)) {
            return false;
        }
        $first = reset($models);
        if (!$first->isTransactional(static::OP_UPDATE)) {
            return static::batchUpdateInternal($models, $attributes);
        }
        $transaction = static::getDb()->beginTransaction();
        try {
            $result = static::batchUpdateInternal($models, $attributes);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }

            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @return bool|string
     * @throws \yii\base\InvalidConfigException
     */
    public function getAIPrimaryKey()
    {
        $columns = static::getTableSchema()->columns;
        foreach ($columns as $column) {
            if ($column->isPrimaryKey && $column->autoIncrement) {
                return $column->name;
            }
        }

        return false;
    }

    /**
     * @param self[] $models
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    private static function batchDeleteInternal($models)
    {

        $rows = [];
        foreach ($models as $model) {
            $model->beforeDelete();
            $rows[] = $model->getOldPrimaryKey();
        }
        $first = reset($models);
        $columns = array_keys($first->getOldPrimaryKey());

        static::deleteAll(['IN', $columns, $rows]);

        foreach ($models as $model) {
            $model->oldAttributes = null;

            $model->afterDelete();
        }

        return true;
    }

    /**
     * @param self[] $models
     * @param array|string|null $attributes
     *
     * @return bool
     * @throws \yii\base\InvalidCallException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    private static function batchInsertInternal(&$models, $attributes)
    {
        $db = static::getDb();
        $command = $db->createCommand();
        foreach ($models as $model) {
            $model->beforeSave(true);
        }
        $first = reset($models);
        $columns = array_keys($first->getDirtyAttributes($attributes));
        $rows = [];
        foreach ($models as $model) {
            $rows[] = $model->getAttributes($columns);
        }
        $command->batchInsert(static::tableName(), $columns, $rows);
        $num = $command->execute();
        $result = $num > 0;
        if (!$result) {
            return false;
        }
        if (($AIPK = $first->getAIPrimaryKey()) !== false) {
            $lastInsertId = $db->getLastInsertID($first->getTableSchema()->sequenceName);
            $newModels = $first::find()->andWhere(['>=', $AIPK, $lastInsertId])->limit($num)->all();
            $i = 0;
            foreach ($models as $model) {
                $model->setIsNewRecord(false);
                $model->setAttribute($AIPK, $newModels[$i]->getAttribute($AIPK));
                $model->setOldAttributes($model->getAttributes());
                $i++;
            }
        } else {
            foreach ($models as $model) {
                $model->setIsNewRecord(false);
                $model->setOldAttributes($model->getAttributes());
            }
        }
        foreach ($models as $model) {
            $model->afterSave(true, array_fill_keys($columns, null));
        }

        return true;
    }

    /**
     * @param self[] $models
     * @param string|array|null $attributes
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    private static function batchUpdateInternal($models, $attributes)
    {
        foreach ($models as $model) {
            $model->beforeSave(false);
        }
        $db = static::getDb();
        $command = $db->createCommand();
        $first = reset($models);
        $columns = array_keys($first->getAttributes());
        $rows = [];
        foreach ($models as $model) {
            $rows[] = array_merge($model->getOldAttributes(), $model->getAttributes($attributes, $model->primaryKey()));
        }
        $updateAttributesParams = [];
        foreach ($columns as $column) {
            $updateAttributesParams[] = "{$column} = VALUES({$column})";
        }
        $updateAttributesSql = implode(',', $updateAttributesParams);
        $command->batchInsert(static::tableName(), $columns, $rows);
        $rawSql = $command->getRawSql() . " ON DUPLICATE KEY UPDATE {$updateAttributesSql}";
        $command->setSql($rawSql);
        $num = $command->execute();
        $result = $num > 0;
        if (!$result) {
            return false;
        }
        foreach ($models as $model) {
            $model->afterSave(false, array_fill_keys($columns, null));
        }

        return true;
    }

    public function triggerByTransaction(AgentEvent $event)
    {
        $db = static::getDb();
        $event = $this->getTriggerEvent($event);
        $event->sender = $this;
        $handler = [$event, 'handler'];
        // 事件执行完成之后将取消事件，避免执行两次
        $offEventCallback = function () use ($db, $handler) {
            try {
                $db->off(Connection::EVENT_BEFORE_COMMIT_TRANSACTION, $handler);
                $this->off(self::EVENT_AFTER_DELETE, $handler);
                $this->off(self::EVENT_AFTER_UPDATE, $handler);
                $this->off(self::EVENT_AFTER_INSERT, $handler);
            } catch (ErrorException $e) {
                Yii::warning($e);
            }
        };
        if ($db->getTransaction()) {
            try {
                $db->off(Connection::EVENT_BEFORE_COMMIT_TRANSACTION, $handler);
            } catch (ErrorException $e) {
                Yii::warning($e);
            }
            $db->on(Connection::EVENT_BEFORE_COMMIT_TRANSACTION, $handler);
            $db->on(Connection::EVENT_BEFORE_COMMIT_TRANSACTION, $offEventCallback);
        } else {
            if ($this->getIsNewRecord()) {
                try {
                    $this->off(self::EVENT_AFTER_INSERT, $handler);
                } catch (ErrorException $e) {
                    Yii::warning($e);
                }
                $this->on(self::EVENT_AFTER_INSERT, $handler);
                $this->on(self::EVENT_AFTER_INSERT, $offEventCallback);
            } else {
                try {
                    $this->off(self::EVENT_AFTER_DELETE, $handler);
                    $this->off(self::EVENT_AFTER_UPDATE, $handler);
                } catch (ErrorException $e) {
                    Yii::warning($e);
                }
                $this->on(self::EVENT_AFTER_DELETE, $handler);
                $this->on(self::EVENT_AFTER_UPDATE, $handler);
                $this->on(self::EVENT_AFTER_DELETE, $offEventCallback);
                $this->on(self::EVENT_AFTER_UPDATE, $offEventCallback);
            }
        }
    }

    protected function getTriggerEvent(Event $event)
    {
        $eventName = get_class($event);
        $target = ArrayHelper::getValue($this->triggerEventItems, $eventName);
        if (!$target) {
            $target = $event;
            $this->triggerEventItems[$eventName] = $event;
        }

        return $target;
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => static::OP_ALL,
        ];
    }
}