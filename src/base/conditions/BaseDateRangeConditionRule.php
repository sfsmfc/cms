<?php

namespace craft\base\conditions;

use Craft;
use craft\enums\PeriodType;
use craft\fields\Date;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateRange;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use DateTime;
use Exception;

/**
 * BaseDateRangeConditionRule provides a base implementation for condition rules that are composed of date range inputs.
 *
 * @property string|null $startDate
 * @property string|null $endDate
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseDateRangeConditionRule extends BaseConditionRule
{
    /**
     * @var string
     * @phpstan-var DateRange::TYPE_*
     * @since 4.3.0
     */
    public string $rangeType = DateRange::TYPE_TODAY;

    /**
     * @var string
     * @phpstan-var DateRange::PERIOD_*
     * @since 4.3.0
     */
    public string $periodType = DateRange::PERIOD_DAYS_AGO;

    /**
     * @var float|null
     * @since 4.3.0
     */
    public ?float $periodValue = null;

    /**
     * @var string|null
     */
    private ?string $_startDate = null;

    /**
     * @var string|null
     */
    private ?string $_endDate = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (
            !isset($config['attributes']['rangeType']) &&
            (!empty($config['attributes']['startDate']) || !empty($config['attributes']['endDate']))
        ) {
            $config['attributes']['rangeType'] = DateRange::TYPE_RANGE;
        }

        if (isset($config['attributes']['periodType'])) {
            // Maintain BC with older periodType values
            $config['attributes']['periodType'] = match ($config['attributes']['periodType']) {
                PeriodType::Minutes => DateRange::PERIOD_MINUTES_AGO,
                PeriodType::Hours => DateRange::PERIOD_HOURS_AGO,
                PeriodType::Days => DateRange::PERIOD_DAYS_AGO,
                default => $config['attributes']['periodType'],
            };
        }

        parent::__construct($config);
    }

    /**
     * @return string|null
     */
    public function getStartDate(): ?string
    {
        return $this->_startDate;
    }

    /**
     * @param mixed $value
     */
    public function setStartDate(mixed $value): void
    {
        $this->_startDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /**
     * @return string|null
     */
    public function getEndDate(): ?string
    {
        return $this->_endDate;
    }

    /**
     * @param mixed $value
     */
    public function setEndDate(mixed $value): void
    {
        $this->_endDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /**
     * Returns the input container attributes.
     *
     * @return array
     */
    protected function containerAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'rangeType' => $this->rangeType,
            'periodType' => $this->periodType,
            'periodValue' => $this->periodValue,
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
        ]);
    }

    /**
     * @inheritdoc
     * @noinspection PhpNamedArgumentsWithChangedOrderInspection
     */
    protected function inputHtml(): string
    {
        $rangeTypeOptionsHtml = Html::beginTag('ul', ['class' => 'padded']);
        $foundAdvancedRuleType = false;

        foreach ($this->rangeTypeOptions() as $value => $label) {
            if (
                !$foundAdvancedRuleType &&
                in_array($value, [
                    DateRange::TYPE_BEFORE,
                    DateRange::TYPE_AFTER,
                    DateRange::TYPE_RANGE,
                ])
            ) {
                $rangeTypeOptionsHtml .= Html::tag('hr', options: ['class' => 'padded']);
                $foundAdvancedRuleType = true;
            }

            $rangeTypeOptionsHtml .= Html::tag('li',
                Html::a($label, options: [
                    'class' => $value === $this->rangeType ? 'sel' : false,
                    'data' => ['value' => $value],
                ])
            );
        }
        $rangeTypeOptionsHtml .= Html::endTag('ul');

        $buttonId = 'date-range-btn';
        $inputId = 'date-range-input';
        $menuId = 'date-range-menu';

        $view = Craft::$app->getView();
        $view->registerJsWithVars(
            fn($buttonId, $inputId) => <<<JS
Garnish.requestAnimationFrame(() => {
  const \$button = $('#' + $buttonId);
  \$button.menubtn().data('menubtn').on('optionSelect', event => {
    const \$option = $(event.option);
    \$button.text(\$option.text()).removeClass('add');
    // Don't use data('value') here because it could result in an object if data-value is JSON
    const \$input = $('#' + $inputId).val(\$option.attr('data-value'));
    htmx.trigger(\$input[0], 'change');
  });
});
JS,
            [
                $view->namespaceInputId($buttonId),
                $view->namespaceInputId($inputId),
            ]
        );

        $html = Html::button($this->rangeTypeOptions()[$this->rangeType], [
            'id' => $buttonId,
            'class' => ['btn', 'menubtn'],
            'autofocus' => false,
            'aria' => [
                'label' => Craft::t('app', 'Date Range'),
            ],
        ]) .
        Html::tag('div', $rangeTypeOptionsHtml, [
            'id' => $menuId,
            'class' => 'menu',
        ]) .
        Html::hiddenInput('rangeType', $this->rangeType, [
            'id' => $inputId,
            'hx' => [
                'post' => UrlHelper::actionUrl('conditions/render'),
            ],
        ]);

        if ($this->rangeType === DateRange::TYPE_RANGE) {
            $html .= Html::tag(
                    'div',
                    options: ['class' => ['flex', 'flex-nowrap']],
                    content:
                    Html::label(Craft::t('app', 'From'), 'start-date-date') .
                    Html::tag('div',
                        Cp::dateHtml([
                            'id' => 'start-date',
                            'name' => 'startDate',
                            'value' => $this->getStartDate(),
                        ])
                    )
                ) .
                Html::tag(
                    'div',
                    options: ['class' => ['flex', 'flex-nowrap']],
                    content:
                    Html::label(Craft::t('app', 'To'), 'end-date-date') .
                    Html::tag('div',
                        Cp::dateHtml([
                            'id' => 'end-date',
                            'name' => 'endDate',
                            'value' => $this->getEndDate(),
                        ])
                    )
                );
        } elseif (in_array($this->rangeType, [DateRange::TYPE_BEFORE, DateRange::TYPE_AFTER])) {
            $periodValueId = 'period-value';
            $periodTypeId = 'period-type';

            $html .= Html::hiddenLabel(Craft::t('app', 'Period Value'), $periodValueId) .
                Html::tag(
                'div',
                options: ['class' => ['flex', 'flex-nowrap']],
                content:
                Cp::textHtml([
                    'id' => $periodValueId,
                    'name' => 'periodValue',
                    'value' => $this->periodValue,
                    'size' => '5',
                ]) .
                Html::hiddenLabel(Craft::t('app', 'Period Type'), $periodTypeId) .
                Cp::selectHtml([
                    'id' => $periodTypeId,
                    'name' => 'periodType',
                    'value' => $this->periodType,
                    'options' => $this->periodTypeOptions(),
                ])
            );
        }

        return Html::tag('div', $html, ['class' => ['flex']]);
    }

    /**
     * Returns the available range type options for the rule.
     *
     * @return array
     */
    protected function rangeTypeOptions(): array
    {
        return [
            DateRange::TYPE_TODAY => Craft::t('app', 'Today'),
            DateRange::TYPE_THIS_WEEK => Craft::t('app', 'This week'),
            DateRange::TYPE_THIS_MONTH => Craft::t('app', 'This month'),
            DateRange::TYPE_THIS_YEAR => Craft::t('app', 'This year'),
            DateRange::TYPE_PAST_7_DAYS => Craft::t('app', 'Past {num} days', ['num' => 7]),
            DateRange::TYPE_PAST_30_DAYS => Craft::t('app', 'Past {num} days', ['num' => 30]),
            DateRange::TYPE_PAST_90_DAYS => Craft::t('app', 'Past {num} days', ['num' => 90]),
            DateRange::TYPE_PAST_YEAR => Craft::t('app', 'Past year'),
            DateRange::TYPE_BEFORE => Craft::t('app', 'Before…'),
            DateRange::TYPE_AFTER => Craft::t('app', 'After…'),
            DateRange::TYPE_RANGE => Craft::t('app', 'Range…'),
        ];
    }

    /**
     * Returns the available period type options for the rule.
     *
     * @return array
     */
    protected function periodTypeOptions(): array
    {
        return [
            DateRange::PERIOD_MINUTES_AGO => Craft::t('app', 'minutes ago'),
            DateRange::PERIOD_HOURS_AGO => Craft::t('app', 'hours ago'),
            DateRange::PERIOD_DAYS_AGO => Craft::t('app', 'days ago'),
            DateRange::PERIOD_MINUTES_FROM_NOW => Craft::t('app', 'minutes from now'),
            DateRange::PERIOD_HOURS_FROM_NOW => Craft::t('app', 'hours from now'),
            DateRange::PERIOD_DAYS_FROM_NOW => Craft::t('app', 'days from now'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['startDate', 'endDate', 'rangeType', 'timeFrameUnits', 'timeFrameValue'], 'safe'],
            [['rangeType'], 'in', 'range' => array_keys($this->rangeTypeOptions())],
            [['periodType'], 'in', 'range' => array_keys($this->periodTypeOptions())],
            [['periodValue'], 'number', 'skipOnEmpty' => true],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[\craft\helpers\Db::parseDateParam()]].
     *
     * @return array|string|null
     */
    protected function queryParamValue(): array|string|null
    {
        if ($this->rangeType === DateRange::TYPE_RANGE && ($this->_startDate || $this->_endDate)) {
            return array_filter([
                'and',
                $this->_startDate ? ">= $this->_startDate" : null,
                $this->_endDate ? "< $this->_endDate" : null,
            ]);
        }

        if (in_array($this->rangeType, [DateRange::TYPE_BEFORE, DateRange::TYPE_AFTER]) && $this->periodValue) {
            $dateInterval = DateRange::dateIntervalByTimePeriod($this->periodValue, $this->periodType);
            return ($this->rangeType === DateRange::TYPE_AFTER ? '>=' : '<') . ' ' . DateTimeHelper::toIso8601(DateTimeHelper::now()->add($dateInterval));
        }

        $rangeTypeOptions = $this->rangeTypeOptions();
        ArrayHelper::remove($rangeTypeOptions, DateRange::TYPE_BEFORE);
        ArrayHelper::remove($rangeTypeOptions, DateRange::TYPE_AFTER);
        ArrayHelper::remove($rangeTypeOptions, DateRange::TYPE_RANGE);

        if (array_key_exists($this->rangeType, $rangeTypeOptions)) {
            [$startDate, $endDate] = DateRange::dateRangeByType($this->rangeType);
            $startDate = DateTimeHelper::toIso8601($startDate);
            $endDate = DateTimeHelper::toIso8601($endDate);
            return ['and', ">= $startDate", "< $endDate"];
        }

        return null;
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param DateTime|null $value
     * @return bool
     * @throws Exception
     */
    protected function matchValue(?DateTime $value): bool
    {
        if ($this->rangeType === DateRange::TYPE_RANGE) {
            return (
                (!$this->_startDate || ($value && $value >= DateTimeHelper::toDateTime($this->_startDate))) &&
                (!$this->_endDate || ($value && $value < DateTimeHelper::toDateTime($this->_endDate)))
            );
        }

        if (in_array($this->rangeType, [DateRange::TYPE_BEFORE, DateRange::TYPE_AFTER]) && $this->periodValue) {
            $date = DateTimeHelper::now()->add(DateRange::dateIntervalByTimePeriod($this->periodValue, $this->periodType));

            if ($this->rangeType === DateRange::TYPE_AFTER) {
                return $value && $value >= $date;
            }

            return $value && $value < $date;
        }

        $rangeTypeOptions = $this->rangeTypeOptions();
        ArrayHelper::remove($rangeTypeOptions, DateRange::TYPE_BEFORE);
        ArrayHelper::remove($rangeTypeOptions, DateRange::TYPE_AFTER);
        ArrayHelper::remove($rangeTypeOptions, DateRange::TYPE_RANGE);
        if (array_key_exists($this->rangeType, $rangeTypeOptions)) {
            [$startDate, $endDate] = DateRange::dateRangeByType($this->rangeType);
            return $value && $value >= $startDate && $value < $endDate;
        }

        return false;
    }
}
