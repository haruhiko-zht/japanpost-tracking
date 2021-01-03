<?php

namespace Tracking;

/**
 * Class Tracking
 * @package Tracking
 * @property string $tracking_code お問い合わせ番号
 * @property array<TrackingStatus> $statuses 履歴情報
 */
class Tracking
{
    public function __construct(
        protected string $tracking_code,
        protected array $statuses,
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->$name ?? null;
    }
}
