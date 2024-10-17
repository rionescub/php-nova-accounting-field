<?php

namespace HeliosLive\PhpNovaAccountingField;

use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Laravel\Nova\Fields\Currency;
use Symfony\Polyfill\Intl\Icu\Currencies;

class Accounting extends Currency
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'php-nova-accounting-field';
    protected $typeCallback;
    public $inMinorUnits = false;

    public function __construct($name, $attribute = null, $cb = null)
    {
        parent::__construct($name, $attribute, $cb);


        $this->typeCallback = $this->defaultTypeCallback();

        $this->step(0.01)
            ->currency('USD')
            ->asHtml()
            ->displayUsing(function ($value) {
                $this->withMeta(['justify' => $this->transformAlign()]);

                $this->context = new CustomContext(8);
                // try {
                if ($this->inMinorUnits && !$this->isValidNullValue($value)) {

                    $value = $this->toMoneyInstance(
                        $value / (10 ** Currencies::getFractionDigits($this->currency)),
                        $this->currency
                    )->getMinorAmount()->toScale(2, RoundingMode::HALF_UP)->toFloat();
                }

                $this->currencySymbol = $this->currencySymbol ?? Currencies::getSymbol($this->currency);

                $decimals = strlen(explode(".", $this->step)[1]);

                $class = "text-green-500";
                $res = ($this->typeCallback)($value);

                $value = number_format(abs($value), $decimals);
                if ($res === true) {
                    $value = "(" . $value . ")";
                    $class = "text-red-500";
                } elseif (is_null($res)) {
                    $class = "";
                }

                $this->withMeta(['symbol' => $this->currencySymbol, 'class' => $class]);
                return $value;
            });
    }

    public function type(callable $typeCallback)
    {
        $this->typeCallback = $typeCallback;
        return $this;
    }
    protected function defaultTypeCallback()
    {
        return function ($value) {
            if ($value == 0) return null;
            return $value < 0;
        };
    }
    /*
     *
     */
    public function justify($direction)
    {
        if (!in_array($direction, ['start', 'end',  'center'])) {
            return $this;
        }
        $revMap = [
            'start' => 'left',
            'end' => 'right',
            'center' => 'center',
        ];
        $this->withMeta(['justify' => $direction, 'textAlign' => $revMap[$direction]]);
        return $this;
    }

    public function transformAlign()
    {
        $align = $this->meta['textAlign'] ?? 'right';
        $map = [
            'right' => 'end',
            'left' => 'start',
            'center' => 'center',
        ];
        return $map[$align];
    }
    /**
     * The value in database is store in minor units (cents for dollars).
     */
    public function storedInMinorUnits($yes = true): static
    {
        $this->inMinorUnits = $yes;

        return $this;
    }
}
