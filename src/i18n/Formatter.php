<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/23
 * Time: 20:58
 */

namespace choate\yii2\components\i18n;

use yii\i18n\Formatter as YiiFormatter;
use DateInterval;
use DateTime;
use Yii;

class Formatter extends YiiFormatter
{
    /**
     * @inheritdoc
     */
    public function asDatetime($value, $format = null)
    {
        if (empty($value)) {
            $value = null;
        }

        return parent::asDatetime($value, $format);
    }

    /**
     * $callback(DateInterval $interval, bool $isNegative)
     *
     * @inheritdoc
     * @param \Closure $callback
     */
    public function asDuration($value, $implodeString = ', ', $negativeSign = '-', \Closure $callback = null)
    {
        if (!($callback instanceof \Closure)) {
            return parent::asDuration($value, $implodeString, $negativeSign);
        }

        if ($value === null) {
            return $this->nullDisplay;
        }

        if ($value instanceof DateInterval) {
            $isNegative = $value->invert;
            $interval = $value;
        } elseif (is_numeric($value)) {
            $isNegative = $value < 0;
            $zeroDateTime = (new DateTime())->setTimestamp(0);
            $valueDateTime = (new DateTime())->setTimestamp(abs($value));
            $interval = $valueDateTime->diff($zeroDateTime);
        } elseif (strpos($value, 'P-') === 0) {
            $interval = new DateInterval('P' . substr($value, 2));
            $isNegative = true;
        } else {
            $interval = new DateInterval($value);
            $isNegative = $interval->invert;
        }

        return call_user_func($callback, $interval, $isNegative);
    }

    /**
     * @inheritdoc
     */
    public function asTimeDuration($value, $negativeSign = '-')
    {
        return $this->asDuration($value, ', ', $negativeSign, function(DateInterval $interval, $isNegative) use ($negativeSign) {
            $houseCount = (int)$interval->days * 24 + $interval->h;

            return ($isNegative ? $negativeSign : '') . sprintf("%02d:%02d:%02d", $houseCount, $interval->i, $interval->s);
        });
    }
}