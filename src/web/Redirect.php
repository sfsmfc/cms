<?php

namespace craft\web;

use Craft;
use League\Uri\Http;

class Redirect extends \yii\base\BaseObject
{
    public string $to;
    public string $from;
    public int $status = 302;
    public \Closure|string $match;
    public bool $caseSensitive = true;
    private string $delimiter = '`';
    private array $matches = [];

    public function __construct($config = [])
    {
        $this->match = $config['match'] ?? Craft::$app->getRequest()->getFullPath();
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

        Craft::$app->getResponse()->redirect($this->replaceMatches($this->to), $this->status);
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
        $regexFlags = $this->caseSensitive ? '' : 'i';
        $pattern = "{$this->delimiter}{$this->from}{$this->delimiter}{$regexFlags}";
        return preg_match($pattern, $match, $this->matches);
    }
}
