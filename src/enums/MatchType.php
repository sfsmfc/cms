<?php

namespace craft\enums;

enum MatchType: string
{
    case Exact = 'exact';
    case Regex = 'regex';
}
