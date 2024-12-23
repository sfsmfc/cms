<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace acceptance;

use ApiTester;
use Codeception\Example;

class RedirectCest
{
    public function _before(ApiTester $I)
    {
    }

    /**
     * @dataProvider redirectDataProvider
     */
    public function testRedirect(ApiTester $I, Example $example): void
    {
        $I->stopFollowingRedirects();
        $I->sendGet($example['fromPath'], $example['fromParams'] ?? []);
        $I->seeResponseCodeIs($example['statusCode']);
        if (isset($example['to'])) {
            $I->haveHttpHeader('Location', $example['to']);
        }
    }

    /**
     * @phpstan-ignore-next-line
     */
    private function redirectDataProvider(): array
    {
        return [
            [
                'fromPath' => '/redirect/from',
                'to' => 'https://craft-5.ddev.site/redirect/to',
                'statusCode' => 302,
            ],
            [
                'fromPath' => '/redirect/FROM',
                'statusCode' => 404,
            ],
            [
                'fromPath' => '/redirect/FROM/1234',
                'to' => 'https://craft-5.ddev.site/redirect/to/1234',
                'statusCode' => 302,
            ],
            [
                'fromPath' => '/redirect/from/foo',
                'fromParams' => ['bar' => 'baz'],
                'to' => 'https://craft-5.ddev.site/redirect/to/baz',
                'statusCode' => 302,
            ],
            [
                'fromPath' => '/REDIRECT/from/foo/bar',
                'fromParams' => ['baz' => 'qux'],
                'to' => 'https://redirect.to/bar?baz=qux',
                'statusCode' => 301,
            ],
        ];
    }
}
