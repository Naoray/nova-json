<?php

namespace Naoray\NovaJson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Hidden;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Naoray\NovaJson\Exceptions\AttributeCast;

class JSON extends MergeValue
{
    use Makeable;

    /**
     * The displayable name of the field.
     *
     * @var string
     */
    public $name;

    /**
     * The attribute / column name of the json field.
     *
     * @var string
     */
    public $attribute;

    /**
     * Array of fields callback.
     *
     * @var array
     */
    public $fillCallbacks = [];

    /**
     * Indicates if the field accepting nullable.
     *
     * @var bool
     */
    public $nullable = true;

    /**
     * Values which will be replaced to null.
     *
     * @var array
     */
    public $nullValues = [''];

    public bool $fillOnce = false;

    public function __construct(string $name, $attribute, $fields = [])
    {
        $this->name = $name;
        $this->attribute = is_string($attribute) ? $attribute : str_replace(' ', '_', Str::lower($name));

        parent::__construct($this->prepareFields(is_array($attribute) || is_callable($attribute) ? $attribute : $fields));
    }

    public function fillAtOnce(callable $fillOnceCallback = null)
    {
        $this->fillOnce = true;

        $this->data[] = Hidden::make($this->attribute)->fillUsing(function (NovaRequest $request, $model, $attribute, $requestAttribute) use ($fillOnceCallback) {
            $keys = collect($request->keys())
                ->filter(fn ($key) => Str::startsWith($key, $this->attribute))
                ->all();

            $requestValues = collect($request->only($keys))
                ->mapWithKeys(function ($value, $key) use ($request, $model) {
                    $value = $this->fetchValueFromRequest($request, $model, $key, $key);
                    $key = Str::after($key, $this->attribute . '->');

                    return [$key => $value];
                })
                ->all();

            $model->{$attribute} = $fillOnceCallback
                ? $fillOnceCallback($request, $requestValues, $model, $attribute, $requestAttribute)
                : $requestValues;
        });

        return $this;
    }

    public function nullable(bool $nullable = true, $values = null)
    {
        $this->nullable = $nullable;

        if ($values !== null) {
            $this->nullValues($values);
        }

        return $this;
    }

    public function nullValues($values)
    {
        $this->nullValues = $values;

        return $this;
    }

    protected function isNullValue($value): bool
    {
        return is_callable($this->nullValues)
            ? ($this->nullValues)($value)
            : in_array($value, (array)$this->nullValues);
    }

    protected function prepareFields(array $fields): array
    {
        return collect(is_callable($fields) ? $fields() : $fields)
            ->map(function ($field) {
                return $field instanceof self
                    ? $this->prepareJsonFields($field)
                    : [$this->prepareField($field)];
            })
            ->flatten()
            ->all();
    }

    public function prepareJsonFields(self $json)
    {
        $fields = collect($json->fields())->each(function ($field) use ($json) {
            $field->fillUsing($json->fillCallbacks[$field->attribute] ?? null);
        })->all();

        return  $this->prepareFields($fields);
    }

    public function fields()
    {
        return collect($this->data)->map(function ($field) {
            return $field instanceof self ? $field->fields() : [$field];
        })->flatten()->all();
    }

    protected function prepareField(Field $field)
    {
        $field->attribute = "{$this->attribute}->{$field->attribute}";
        $this->fillCallbacks[$field->attribute] = $field->fillCallback;

        return $field->fillUsing(function ($request, Model $model, $attribute, $requestAttribute) use ($field) {
            $value = $this->fetchValueFromRequest($request, $model, $attribute, $requestAttribute);

            if ($this->nullable && $this->isNullValue($value)) {
                return;
            }

            if (! $model->hasCast($this->attribute)) {
                throw AttributeCast::notFoundFor($this->attribute);
            }

            $data = $this->getOldValue($model);
            $dottedAttrKey = str_replace(["{$this->attribute}->", '->'], ['', '.'], $attribute);
            $data = data_set($data, $dottedAttrKey, $value ?? null);

            if ($this->fillOnce) {
                return;
            }

            $model->{$this->attribute} = $data;
        });
    }

    protected function fetchValueFromRequest($request, $model, $attribute, $requestAttribute)
    {
        $resolver = $this->getValueResolver($attribute);

        return $resolver($request, $model, $attribute, $requestAttribute);
    }

    protected function getValueResolver(string $attribute): callable
    {
        return $this->fillCallbacks[$attribute] ?? function ($request, $model, $attribute, $requestAttribute) {
            if ($request->exists($requestAttribute)) {
                return $request[$requestAttribute];
            }
        };
    }

    protected function getOldValue($model)
    {
        return $this->value ?? (array)$model->{$this->attribute};
    }
}
