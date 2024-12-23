<?php
// config/redirects.php
return [
    // Exact path match
    'redirect/from' => 'redirect/to',

    // Path match with named capture group
    'redirect/from/(?<year>\d{4})' => [
        'to' => 'redirect/to/{year}',
        'matchType' => 'regex',
        'caseSensitive' => false,
    ],

    // Match path and query string
    new \craft\web\Redirect([
        'from' => 'bar=(?<bar>[^&]+)',
        'to' => '/redirect/to/{bar}',
        'match' => fn(\Psr\Http\Message\UriInterface $url) => (string) "{$url->getPath()}?{$url->getQuery()}",
        'matchType' => \craft\enums\MatchType::Regex,
    ]),

    // Match full URL
    'https://craft-5-project.ddev.site/redirect/from/foo/(.+)' => [
        'to' => 'https://redirect.to/{1}',
        'match' => fn(\Psr\Http\Message\UriInterface $url) => (string) $url,
        'matchType' => \craft\enums\MatchType::Regex,
        'statusCode' => 301,
        'caseSensitive' => false,
    ],
];
