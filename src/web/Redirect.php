<?php

namespace craft\web;

use Craft;
use craft\enums\MatchType;
use League\Uri\Http;

class Redirect extends \yii\base\BaseObject
{
    public string $to;
    public string $from;
    public \Closure|string $match;
    public MatchType $matchType;
    public int $statusCode = 302;
    public bool $caseSensitive = true;
    private string $delimiter = '`';
    private array $matches = [];

    public function __construct($config = [])
    {
        $this->match = $config['match'] ?? Craft::$app->getRequest()->getFullPath();
        $matchType = $config['matchType'] ?? MatchType::Exact;

        $this->matchType = match (true) {
            $matchType instanceof MatchType => $matchType,
            default => MatchType::tryFrom($matchType),
        };

        unset($config['match']);
        unset($config['matchType']);

        parent::__construct($config);
    }

    public function __invoke(?callable $callback = null): void
    {
        if (!$this->findMatch()) {
            return;
        }

        if ($callback) {
            $callback($this);
        }

        Craft::$app->getResponse()->redirect($this->replaceMatches($this->to), $this->statusCode);
        Craft::$app->end();
    }

    private function replaceMatches(string $url): string
    {
        return Craft::$app->getView()->renderObjectTemplate($url, $this->matches);
    }

    private function findMatch(): bool
    {
        $url = Http::new(Craft::$app->getRequest()->getAbsoluteUrl());
        $match = is_callable($this->match) ? ($this->match)($url) : $this->match;

        if ($this->matchType === MatchType::Regex) {
            $regexFlags = $this->caseSensitive ? '' : 'i';
            $pattern = "{$this->delimiter}{$this->from}{$this->delimiter}{$regexFlags}";

            return preg_match($pattern, $match, $this->matches);
        }

        return $this->caseSensitive ?
            strcmp($this->from, $match) === 0 :
            strcasecmp($this->from, $match) === 0;
    }
}
