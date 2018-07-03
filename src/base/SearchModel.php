<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/15
 * Time: 20:06
 */

namespace choate\yii2\components\base;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\Sort;

abstract class SearchModel extends Model
{
    /**
     * @return \yii\db\ActiveQuery
     */
    abstract protected function query();

    abstract protected function buildQuery();

    /**
     * @inheritdoc
     * @see Sort::$defaultOrder
     *
     * @return array
     */
    protected function defaultSort()
    {
        return [];
    }

    /**
     * @inheritdoc
     * @see Sort::$attributes
     *
     * @return array
     */
    protected function sort()
    {
        return [];
    }

    /**
     * @return \yii\data\Sort
     */
    public function getSort()
    {
        return new Sort(['defaultOrder' => $this->defaultSort(), 'attributes' => $this->sort()]);
    }

    public function setWith($with)
    {
        if (!empty($with)) {
            $this->query()->with = null;
            $this->query()->with($with);
        }
    }

    public function fetchAll($with = [])
    {
        $query = $this->query();
        $this->buildQuery();
        $this->setWith($with);
        $query->orderBy($this->getSort()->getOrders());

        return $query->all();
    }

    public function fetchDataProvider($with = [])
    {
        $sort = $this->getSort();
        $query = $this->query();
        $this->buildQuery();
        $this->setWith($with);

        return new ActiveDataProvider(['query' => $query, 'sort' => $sort]);
    }

    public function setAttributes($values, $safeOnly = false)
    {
        parent::setAttributes($values, $safeOnly);
    }
}