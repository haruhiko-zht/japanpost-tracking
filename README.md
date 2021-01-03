haruhiko-zht/japanpost-tracking
===

Scrape japan-post's delivery status by tracking code.

## Usage

```php
// scraping
$scraper = new \Tracking\TrackingScraping(url:'japan post base search url');
$tracking = $scraper(tracking_code: 'tracking code');

// parse tracking data
$parse_data = \Tracking\TrackingParser::parse(tracking: $tracking);
```

## License

[MIT license](https://opensource.org/licenses/MIT)

## Author

[haruhiko-zht](https://github.com/haruhiko-zht)
