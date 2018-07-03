<?php

namespace choate\yii2\components\vo;


use choate\yii2\components\base\ValueObject;

class DateRangeVO extends ValueObject
{
    /**
     * @var string
     */
    private $startAt;

    /**
     * @var string
     */
    private $endAt;

    public function __construct(?string $startAt, ?string $endAt)
    {
        $this->startAt = $startAt;

        $this->endAt = $endAt;
    }

    /**
     * @return int|null
     */
    public function getStartTimestamps(): ?int
    {
        if ($this->startAt) {
            return strtotime($this->startAt);
        } elseif ($this->endAt) {
            return 0;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getEndTimestamps(): ?int
    {
        if ($this->endAt) {
            return strtotime($this->endAt);
        } elseif ($this->startAt) {
            return time();
        }

        return null;
    }
}