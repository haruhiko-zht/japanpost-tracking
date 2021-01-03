<?php

namespace Tracking;

/**
 * Class TrackingStatus
 * @package Tracking
 * @property string $datetime 状態発生日
 * @property string $status 配送履歴
 * @property string $detail 詳細
 * @property string $office 取扱局
 * @property string $postcode 郵便番号
 * @property string $prefecture 県名等
 */
class TrackingStatus
{
    public function __construct(
        protected string $datetime,
        protected string $status,
        protected string $detail,
        protected string $office,
        protected string $postcode,
        protected string $prefecture,
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->$name ?? null;
    }

    /**
     * 空状態のインスタンスを生成する
     *
     * @return TrackingStatus
     */
    public static function createEmpty(): TrackingStatus
    {
        return (new static(datetime: '', status: '', detail: '', office: '', postcode: '', prefecture: ''));
    }
}
