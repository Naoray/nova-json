<?php

namespace Naoray\NovaJson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Hidden;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Naoray\NovaJson\Exceptions\AttributeCast;

class JSON extends MergeValue
{
    use Makeable;
    use ForwardsCalls;

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

    /**
     * Determines if all values are filled at once
     * or if they are filled one after another.
     */
    public bool $fillAtOnce = false;

    /**
     * Create a JSON field collection instance.
     *
     * @param string $name
     * @param string|callable|\Illuminate\Support\Collection|array $attribute
     * @param mixed $fields
     */
    public function __construct(string $name, $attribute, $fields = [])
    {
        $this->name = $name;

        if (is_string($attribute)) {
            $this->attribute = $attribute;
        } else {
            $this->attribute = Str::of($name)
                ->lower()
                ->replace(' ', '_')
                ->__toString();
        }

        parent::__construct(
            $this->prepareFields($this->holdsFieldValues($attribute) ? $attribute : $fields)
        );
    }

    /**
     * Checks if $attribute holds the nova fields.
     *
     * @param mixed $attribute
     * @return bool
     */
    private function holdsFieldValues($attribute): bool
    {
        return is_array($attribute)
            || $attribute instanceof Collection
            || is_callable($attribute);
    }

    /**
     * Prepare the given fields.
     *
     * @param  \Closure|array  $fields
     * @return array
     */
    protected function prepareFields($fields): array
    {
        return collect(is_callable($fields) ? $fields() : $fields)
            ->map(function ($field) {
                return $field instanceof self
                    ? $this->prepareNestedJSONFields($field)
                    : [$this->prepareField($field)];
            })
            ->flatten()
            ->all();
    }

    /**
     * Apply fillUsing callback to single field.
     *
     * @param Field $field
     * @return Field
     */
    protected function prepareField(Field $field): Field
    {
        $field->attribute = "{$this->attribute}->{$field->attribute}";
        $this->fillCallbacks[$field->attribute] = $field->fillCallback;

        return $field->fillUsing(function ($request, Model $model, $attribute, $requestAttribute) {
            if ($this->fillAtOnce) {
                return;
            }

            if (! $model->hasCast($this->attribute)) {
                throw AttributeCast::notFoundFor($this->attribute);
            }

            $value = $this->fetchValueFromRequest($request, $model, $attribute, $requestAttribute);

            if (! $this->nullable && $this->isNullValue($value)) {
                return;
            }

            $data = $this->getOldValue($model);
            $dottedAttrKey = $this->getDottedAttributeKey($attribute);
            $data = data_set($data, $dottedAttrKey, $this->isNullValue($value) ? null : $value);

            $model->{$this->attribute} = $data;
        });
    }

    /**
     * Apply callbacks to nested fields.
     *
     * @param self $json
     * @return array
     */
    protected function prepareNestedJSONFields(self $json): array
    {
        $fields = collect($json->fields())->each(function ($field) use ($json) {
            $field->fillUsing($json->fillCallbacks[$field->attribute] ?? null);
        })->all();

        return  $this->prepareFields($fields);
    }

    /**
     * Get all JSON nested fields and flatten them.
     *
     * @return array
     */
    public function fields(): array
    {
        return collect($this->data)->map(function ($field) {
            return $field instanceof self ? $field->fields() : [$field];
        })->flatten()->all();
    }

    /**
     * Fetches value from incoming nova request.
     *
     * @param NovaRequest $request
     * @param Model $model
     * @param string $attribute
     * @param string $requestAttribute
     * @return mixed
     */
    protected function fetchValueFromRequest(NovaRequest $request, Model $model, string $attribute, string $requestAttribute)
    {
        $resolver = $this->getValueResolver($attribute);

        return $resolver($request, $model, $attribute, $requestAttribute);
    }

    /**
     * Get value resolve callback.
     *
     * @param string $attribute
     * @return callable
     */
    protected function getValueResolver(string $attribute): callable
    {
        return $this->fillCallbacks[$attribute] ?? function ($request, $model, $attribute, $requestAttribute) {
            if ($request->exists($requestAttribute)) {
                return $request[$requestAttribute];
            }
        };
    }

    /**
     * Check value for null value.
     *
     * @param  mixed $value
     * @return bool
     */
    protected function isNullValue($value): bool
    {
        return is_callable($this->nullValues)
            ? ($this->nullValues)($value)
            : in_array($value, (array) $this->nullValues);
    }

    /**
     * Indicate that the field should be nullable.
     *
     * @param  bool  $nullable
     * @param  array|Closure  $values
     * @return $this
     */
    public function nullable(bool $nullable = true, $values = null)
    {
        $this->nullable = $nullable;

        if ($values !== null) {
            $this->nullValues($values);
        }

        return $this;
    }

    /**
     * Specify nullable values.
     *
     * @param  array|Closure  $values
     * @return $this
     */
    public function nullValues($values)
    {
        $this->nullValues = $values;

        return $this;
    }

    /**
     * Get old value from json attribute.
     *
     * @param Model $model
     * @return mixed
     */
    protected function getOldValue(Model $model)
    {
        return $this->value ?? (array) $model->{$this->attribute};
    }

    /**
     * Get dotted key for the given attribute.
     *
     * @param string $attribute
     * @return string
     */
    protected function getDottedAttributeKey(string $attribute): string
    {
        return str_replace(["{$this->attribute}->", '->'], ['', '.'], $attribute);
    }

    /**
     * Append Hidden field to JSON fields
     * to resolve all values at once.
     *
     * @param callable $fillAtOnceCallback
     * @return self
     */
    public function fillAtOnce(callable $fillAtOnceCallback = null): self
    {
        $this->fillAtOnce = true;

        $this->data[] = Hidden::make($this->attribute)->fillUsing(function (NovaRequest $request, $model, $attribute, $requestAttribute) use ($fillAtOnceCallback) {
            $requestValues = $this->getRequestValues($request, $model);

            $value = $fillAtOnceCallback
                ? $fillAtOnceCallback($request, $requestValues, $model, $attribute, $requestAttribute)
                : $requestValues;

            if (! $this->nullable && $this->isNullValue($value)) {
                return;
            }

            $model->{$attribute} = $value;
        });

        return $this;
    }

    /**
     * Get all JSON request values from the given nova request.
     *
     * @param NovaRequest $request
     * @param Model $model
     * @return array
     */
    protected function getRequestValues(NovaRequest $request, Model $model): array
    {
        $keys = collect($request->keys())
            ->filter(fn ($key) => Str::startsWith($key, $this->attribute) && $key !== $this->attribute)
            ->all();

        return collect($request->only($keys))
            ->mapWithKeys(function ($value, $key) use ($request, $model) {
                return [$key => $this->fetchValueFromRequest($request, $model, $key, $key)];
            })
            ->reduceWithKeys(function ($carry, $item, $key) {
                $path = str_replace('->', '.', $key);
                data_set($carry, $path, $item);

                return $carry;
            }, [$this->attribute => []])[$this->attribute];
    }

    /**
     * Allow calls to FieldElement methods
     * and apply them to all fields.
     *
     * @param string $method
     * @param array $attrs
     * @return self
     */
    public function __call($method, $attrs): self
    {
        if (! method_exists(\Laravel\Nova\Fields\Field::class, $method)) {
            throw new \BadMethodCallException;
        }

        foreach ($this->fields() as $field) {
            $this->forwardCallTo($field, $method, $attrs);
        }

        return $this;
    }
}
