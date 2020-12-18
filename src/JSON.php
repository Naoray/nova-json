<?php

namespace Naoray\NovaJson;

use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Str;
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

    public function __construct(string $name, $attribute, $fields = [])
    {
        $this->name = $name;
        $this->attribute = is_string($attribute) ? $attribute : str_replace(' ', '_', Str::lower($name));
        $this->data = $this->applyNovaCallbacks(is_array($attribute) ? $attribute : $fields);
    }

    protected function applyNovaCallbacks(array $fields): array
    {
        return collect($fields)
            ->map(function ($field) {
                return $field->resolveUsing(fn ($value, $resource, $attribute) => data_get($resource, "{$this->attribute}.{$attribute}"))
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($field) {
                        if ($request->exists($requestAttribute)) {
                            $value = $request[$requestAttribute];

                            if (! $model->hasCast($this->attribute)) {
                                throw AttributeCast::notFound();
                            }

                            $model->setAttribute("{$this->attribute}->{$attribute}", $value ?? null);
                        }
                    });
            })
            ->all();
    }
}
