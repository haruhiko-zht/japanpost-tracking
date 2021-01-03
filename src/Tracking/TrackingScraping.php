<?php

namespace Tracking;


/**
 * Class TrackingScraping
 * @package Tracking
 * @property string $url サイトの検索URL
 * @property string|null $proxy プロキシー
 * @property int|string|null $port プロキシーのポート番号
 */
class TrackingScraping
{
    public function __construct(
        protected string $url,
        protected string|null $proxy = null,
        protected int|string|null $port = null,
    ) {
    }

    /**
     * プロキシーが利用可能かを論理値で取得する
     *
     * @return bool
     */
    public function isAvailableProxy(): bool
    {
        return isset($this->proxy, $this->port);
    }

    /**
     * curlオプション(基本設定)を取得する
     *
     * @return array
     */
    protected function getCurlGeneralOpt(): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
        ];
    }

    /**
     * curlオプション(リクエストURL設定)を取得する
     *
     * @param string $tracking_code
     * @return string[]
     */
    protected function getCurlRequestUrlOpt(string $tracking_code): array
    {
        return [
            CURLOPT_URL => $this->genRequestUrl($tracking_code),
        ];
    }

    /**
     * curlオプション(プロキシー設定)を取得する
     *
     * @return array
     */
    protected function getCurlProxyOpt(): array
    {
        return [
            CURLOPT_PROXY => $this->proxy,
            CURLOPT_PROXYPORT => $this->port,
        ];
    }

    /**
     * curlオプションを合成する
     *
     * @param string $tracking_code
     * @return array
     */
    protected function getCurlOpt(string $tracking_code): array
    {
        $opt = array_replace($this->getCurlGeneralOpt(), $this->getCurlRequestUrlOpt($tracking_code));
        return ($this->isAvailableProxy())
            ? array_replace($opt, $this->getCurlProxyOpt())
            : $opt;
    }

    /**
     * お問い合わせ番号から数値以外を除去する
     *
     * @param string $string
     * @return string
     */
    public function removeNonNumericChars(string $string): string
    {
        return preg_replace(pattern: '@\D@u', replacement: '', subject: $string);
    }

    /**
     * curlでリクエストするURLを生成する
     *
     * @param string $tracking_code
     * @return string
     */
    protected function genRequestUrl(string $tracking_code): string
    {
        return sprintf(
            '%s?%s',
            $this->url,
            http_build_query(
                [
                    'requestNo1' => $tracking_code,
                    'requestNo2' => '',
                    'requestNo3' => '',
                    'requestNo4' => '',
                    'requestNo5' => '',
                    'requestNo6' => '',
                    'requestNo7' => '',
                    'requestNo8' => '',
                    'requestNo9' => '',
                    'requestNo10' => '',
                    'search.x' => mt_rand(min: 0, max: 160),
                    'search.y' => mt_rand(min: 0, max: 40),
                    'startingUrlPatten' => '',
                    'locale' => 'ja',
                ]
            )
        );
    }

    /**
     * curlでリクエストを行う
     *
     * @param string $tracking_code
     * @return bool|string
     * @throws TrackingException
     */
    protected function request(string $tracking_code): bool|string
    {
        // init
        @sleep(2);
        $ch = curl_init();

        // option
        curl_setopt_array(handle: $ch, options: $this->getCurlOpt($tracking_code));

        // exec
        $res = curl_exec(handle: $ch);

        if (curl_errno(handle: $ch) !== CURLE_OK) {
            $message = curl_error(handle: $ch);
            curl_close(handle: $ch);
            throw new TrackingException(message: 'Curl error: ' . $message);
        }

        $status_code = curl_getinfo(handle: $ch, option: CURLINFO_HTTP_CODE);
        curl_close(handle: $ch);

        if ($status_code < 200 || $status_code >= 400) {
            throw new TrackingException(message: 'Request failed. HTTP response code [' . $status_code . '].');
        }
        return $res;
    }

    /**
     * スクレイピングを行う
     *
     * @param string $html
     * @return array
     * @throws TrackingException
     */
    protected function scrape(string $html): array
    {
        // DOM解析
        $dom = new \DOMDocument;
        @$dom->loadHTML(source: $html);

        // XPath解析
        $xpath = new \DOMXPath(document: $dom);

        // 履歴情報テーブル取得
        $nodes = iterator_to_array($xpath->query(expression: '//table[@summary="履歴情報"]/tr'));

        // テーブルの行数が2以下、もしくは2の倍数でない時は処理できないのでスロー
        if ((count(value: $nodes) <= 2) || count(value: $nodes) % 2 !== 0) {
            throw new TrackingException(message: 'Unexpected format.');
        }

        // 2行で1情報なので配列を2分割する
        $items = array_chunk(array: $nodes, length: 2);

        // 最初の配列はテーブルのヘッドなので削除する
        array_shift(array: $items);

        // スクレピング
        return array_map(
            static function ($item) use ($xpath) {
                return [
                    'datetime' => $xpath->evaluate('string(.//td[1])', $item[0]),
                    'status' => $xpath->evaluate('string(.//td[2])', $item[0]),
                    'detail' => $xpath->evaluate('string(.//td[3])', $item[0]),
                    'office' => $xpath->evaluate('string(.//td[4])', $item[0]),
                    'postcode' => $xpath->evaluate('string(.//td[1])', $item[1]),
                    'prefecture' => $xpath->evaluate('string(.//td[5])', $item[0]),
                ];
            },
            $items
        );
    }

    /**
     * @param string $tracking_code
     * @return Tracking
     * @throws TrackingException
     */
    public function __invoke(string $tracking_code): Tracking
    {
        // 追跡番号の整形
        $code = $this->removeNonNumericChars($tracking_code);

        // リクエスト
        $res = $this->request($code);

        // スクレピング
        $statuses = $this->scrape($res);

        // インスタンス生成
        return (new Tracking(
            tracking_code: $code,
            statuses: array_map(
                               static fn($status) => new TrackingStatus(
                                   datetime: $status['datetime'],
                                   status: $status['status'],
                                   detail: $status['detail'],
                                   office: $status['office'],
                                   postcode: $status['postcode'],
                                   prefecture: $status['prefecture'],
                               ),
                               $statuses
                           )
        ));
    }
}
