<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\StringHelper;
use Illuminate\Support\Collection;
use League\Uri\Http;

class RedirectRule extends \yii\base\BaseObject
{
    public string $to;
    public string $from;
    public int $statusCode = 302;
    public bool $caseSensitive = false;
    private \Closure $_match;
    private array $regexTokens = [];

    public function __invoke(?callable $callback = null): void
    {
        $to = $this->getMatch();

        if ($to === null) {
            return;
        }

        if ($callback) {
            $callback($this);
        }

        Craft::$app->getResponse()->redirect(
            $to,
            $this->statusCode,
        );
        Craft::$app->end();
    }

    public function setMatch(\Closure $match): void
    {
        $this->_match = $match;
    }

    public function getMatch(): ?string
    {
        if (isset($this->_match)) {
            return ($this->_match)(Http::new(Craft::$app->getRequest()->getAbsoluteUrl()));
        }

        $subject = Craft::$app->getRequest()->getFullPath();

        if (str_contains($this->from, '<')) {
            if (preg_match(
                $this->toRegexPattern($this->from),
                $subject,
                $matches,
            )) {
                return $this->replaceParams($this->to, $matches);
            }

            return null;
        }

        if ($this->caseSensitive) {
            return strcmp($this->from, $subject) === 0 ? $this->to : null;
        }

        return strcasecmp($this->from, $subject) === 0 ? $this->to : null;
    }

    private function replaceParams(string $value, array $params): string
    {
        $params = Collection::make($params)
            ->mapWithKeys(fn($item, $key) => ["<$key>" => $item]);

        return strtr($value, $params->all());
    }

    private function toRegexPattern(string $from): string
    {
        // Tokenize the patterns first, so we only escape regex chars outside of patterns
        $tokenizedPattern = preg_replace_callback('/<([\w._-]+):?([^>]+)?>/', function($match) {
            $name = $match[1];
            $pattern = strtr($match[2] ?? '[^\/]+', StringHelper::regexTokens());
            $token = "<$name>";
            $this->regexTokens[$token] = "(?P<$name>$pattern)";

            return $token;
        }, $from);

        $replacements = array_merge($this->regexTokens, [
            '.' => '\\.',
            '*' => '\\*',
            '$' => '\\$',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
        ]);

        $pattern = strtr($tokenizedPattern, $replacements);
        $flags = $this->caseSensitive ? 'u' : 'iu';

        return "`^{$pattern}$`{$flags}";
    }
}
