<?php

require_once __DIR__ . '/vendor/autoload.php';

try {
    // scraping
    $scraper = new \Tracking\TrackingScraping(url: 'japan post base search url');
    $tracking = $scraper(tracking_code: 'tracking code');
    //var_dump($tracking);

    // tracking status parse
    $parse = \Tracking\TrackingParser::parse(tracking: $tracking);
    var_dump($parse);
} catch (\Throwable $e) {
    echo $e->getMessage(), PHP_EOL;
}
