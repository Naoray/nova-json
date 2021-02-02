# nova-json
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/naoray/nova-json.svg?style=flat-square)](https://packagist.org/packages/naoray/nova-json)

The `JSON` field wrapper allows you to specify multiple fields which will be resolved into a single model attribute. This allows you to validate every information you store inside a json column seperately.

```php
JSON::make('Author', [
    Text::make('Name')->rules(['string', 'required', 'min:3']),
    Text::make('Email')->rules(['email', 'required']),
])
```
The above will be resolved into a single `author` attribute on the model.

```php
// prequesite: the 'author' attribute needs to casted into a json castable type
// e.g. object, array, ...
['author' => ['name' => '', 'email' => '']]
```


## Install & setup
`composer require naoray/nova-json`

Add the column's name, you want to use in the `JSON` field, to your `$casts` array on the resource's model!

## Usage
You can destructure one JSON column into multiple Nova fields and apply unique rules to each of the key-value pairs.

```php
use Naoray\NovaJson\JSON;

// within your nova resource
public function fields()
{
    return [
        //...
        JSON::make('Some Json Column Name', [
            Text::make('First Field'),
            Text::make('Second Field'),
            Text::make('Third Field'),
        ]);
    ]
}
```
## Use inside Panels
In order to use JSON column inside Nova Panel you need to get 'data' property of the top level JSON field.

#### Examples
1. JSON is the only field inside Panel
```php
new Panel('Brand Settings', 
    JSON::make('brand_settings', [
        Image::make('Logo')->disk('public'),
        Color::make('Primary Color')->swatches(),
        Color::make('Secondary Color')->swatches(),
    ])->data,
),
```
2. if you need other fields inside the Panel you can use splat operator like this:
```php
new Panel('Brand Settings', [
    Text::make('Some Field'),
    ...JSON::make('brand_settings', [
        Image::make('Logo')->disk('public'),
        Color::make('Primary Color')->swatches(),
        Color::make('Secondary Color')->swatches(),
    ])->data,
]),
```

### Labels & Attributes
By default the first argument you provide the `JSON` field will be considered its `name`. If you don't provide a second string argument the `attribute` of the field will be guessed e.g. `'Some Json Column Name' => 'some_json_column_name'`. If you want your field `name` to be different from your `attribute` you can provide the field with a second argument and provide the fields as the third argument: `JSON::make('Some Name', 'column_name', [])`

### Nested Structures
The `JSON` field can also be nested by itself to display nested JSON structures:

```php
JSON::make('Meta', [
    Text::make('Street'),

    JSON::make('Location', [
        Text::make('Latitude'),
        Text::make('Longitude'),
    ]),
]);
```

## Testing
Run the tests with:

``` bash
vendor/bin/phpunit
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Krishan Koenig](https://github.com/naoray)
- [All Contributors](https://github.com/naoray/nova-json/contributors)

## Security
If you discover any security-related issues, please email krishan.koenig@googlemail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
