<?php

return [
    // Path match (case-insensitive by default)
    'redirect/from' => 'redirect/to',

    // Path match with Yii URL Rule named parameters
    // https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#named-parameters
    'redirect/from/foo/<bar:{slug}>' => 'redirect/to/<bar>',

    'redirect/from/$special.chars' => 'redirect/to',

    // Path match (case-sensitive)
    [
        'from' => 'redirect/FROM/<year:\d{4}>/<month>',
        'to' => 'https://redirect.to/<year>/<month>',
        'caseSensitive' => true,
    ],

    // Custom match callback
    [
        'match' => function(\Psr\Http\Message\UriInterface $url): ?string {
            parse_str($url->getQuery(), $params);

            return isset($params['bar'])
                ? sprintf('redirect/to/%s', $params['bar'])
                : null;
        },
        'statusCode' => 301,
    ],
];
