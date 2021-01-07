<?php

namespace Naoray\NovaJson;

use Laravel\Nova\Makeable;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
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

    public function __construct(string $name, $attribute, $fields = [])
    {
        $this->name = $name;
        $this->attribute = is_string($attribute) ? $attribute : str_replace(' ', '_', Str::lower($name));

        parent::__construct($this->prepareFields(is_array($attribute) || is_callable($attribute) ? $attribute : $fields));
    }

    protected function prepareFields(array $fields): array
    {
        return collect(is_callable($fields) ? $fields() : $fields)
            ->map(function ($field) {
                return $field instanceof self
                    ? $this->prepareFields($field->data)
                    : [$this->prepareField($field)];
            })
            ->flatten()
            ->all();
    }

    protected function prepareField(Field $field)
    {
        $field->attribute = "{$this->attribute}->{$field->attribute}";

        return $field->fillUsing(function ($request, Model $model, $attribute, $requestAttribute) use ($field) {
            if ($request->exists($requestAttribute)) {
                $value = $request[$requestAttribute];

                if (!$model->hasCast($this->attribute)) {
                    throw AttributeCast::notFoundFor($this->attribute);
                }

                $data = $this->getOldValue($model);
                $dottedAttrKey = str_replace(["{$this->attribute}->", '->'], ['', '.'], $attribute);
                $data = data_set($data, $dottedAttrKey, $value ?? null);

                $model->{$this->attribute} = $data;
            }
        });
    }

    protected function getOldValue($model)
    {
        return (array)$model->{$this->attribute};
    }
}
