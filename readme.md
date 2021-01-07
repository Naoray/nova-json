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


## Install
`composer require naoray/nova-json`

## Usage
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

Make sure you have added the `some_json_column_name` to your `$casts` array on the resource's model!

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
