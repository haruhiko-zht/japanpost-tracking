<?php

namespace Tracking;

/**
 * Class TrackingParser
 * @package Tracking
 * @property array $props 解析項目
 * @property Tracking $tracking
 * @property TrackingStatus $latest 最新情報
 * @property TrackingStatus $underwrite 引受情報
 * @property TrackingStatus $arrive 到着情報
 * @property TrackingStatus $passing 通過情報
 * @property TrackingStatus $done 完了情報
 * @property bool $isUsed 番号使用フラグ(1=使用)
 * @property bool $isDone 配達完了フラグ(1=完了)
 */
class TrackingParser
{
    /** @var string[] status list */
    public const STATUS = [
        '引受' => 'underwrite',
        '到着' => 'arrive',
        '通過' => 'passing',
        'ご不在のため持ち戻り' => '',
        '差出人に返送' => '',
        '配達希望受付' => '',
        '最寄局・最寄店送付' => '',
        '保管' => '',
        '持ち出し中' => '',
        'お届け先にお届け済み' => 'done',
        '窓口でお渡し' => 'done',
        '差出人に返送済み' => 'done',
    ];

    protected array $props = ['latest', 'underwrite', 'arrive', 'passing', 'done'];
    protected Tracking $tracking;
    protected TrackingStatus $latest;
    protected TrackingStatus $underwrite;
    protected TrackingStatus $arrive;
    protected TrackingStatus $passing;
    protected TrackingStatus $done;
    protected bool $isUsed;
    protected bool $isDone;

    public function __construct(Tracking $tracking)
    {
        $this->tracking = $tracking;
        $this->statusAssignment();
        $this->isUsedCheck();
        $this->statusDoneCheck();
        $this->fillEmptyStatus();
    }

    public function __get(string $name): mixed
    {
        return $this->$name ?? null;
    }

    /**
     * それぞれの解析項目に該当する履歴情報を代入する
     */
    protected function statusAssignment(): void
    {
        foreach ($this->tracking->statuses as $key => $trackingStatus) {
            $prop = empty(static::STATUS[$trackingStatus->status])
                ? 'latest'
                : static::STATUS[$trackingStatus->status];
            $this->$prop = $trackingStatus;
        }
        $this->latest = $this->tracking->statuses[array_key_last($this->tracking->statuses)];
    }

    /**
     * 配達完了状態なのかを確認して論理値でisDoneプロパティに保存する
     */
    protected function statusDoneCheck(): void
    {
        $this->isDone = isset($this->done);
    }

    /**
     * 追跡番号が使用されたかを確認して論理値でisUsedプロパティに保存する
     */
    protected function isUsedCheck(): void
    {
        $this->isUsed = isset($this->underwrite);
    }

    /**
     * 空の解析項目に空の結果を代入する
     */
    protected function fillEmptyStatus(): void
    {
        foreach ($this->props as $prop) {
            if (empty($this->$prop)) {
                $this->$prop = TrackingStatus::createEmpty();
            }
        }
    }

    /**
     * 配達の終了判定を論理値で取得する
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->isDone;
    }

    /**
     * 使用の判定を論理値で取得する
     *
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    /**
     * Trackingインスタンスを投げてTrackingParser(解析結果)を取得する
     *
     * @param Tracking $tracking
     * @return TrackingParser
     */
    public static function parse(Tracking $tracking): TrackingParser
    {
        return (new static(tracking: $tracking));
    }
}
