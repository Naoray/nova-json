# Changelog

## [v1.2.3](https://github.com/naoray/nova-json/tree/v1.2.2) (2021-03-10)

**Fixed**
- `array_key_exists(): The first argument should be either a string or an integer` appearing when the attribute name of the JSON fields were not specified

## [v1.2.2](https://github.com/naoray/nova-json/tree/v1.2.2) (2021-02-26)

**Fixed**
- `nullable()` fields were not possible to be filled with `null` values

**Changed**
- changed `prepareJsonFields()` from public to protected
- added tests for `nullable()` & `nullValues()`

## [v1.2.1](https://github.com/naoray/nova-json/tree/v1.2.1) (2021-02-03)

**Fixed**
- bug where nested JSON structures would return only partly resolved with `->` instead of having an array structure.

## [v1.2.0](https://github.com/naoray/nova-json/tree/v1.2.0) (2021-02-03)

**Added**
- `JSON` can be marked as `nullable` via `nullable()` and specify values counted as `null` via `nullValues()`
- `fillOnce()` method to prevent values being filled one after another.

**Fixed**
- `fillUsing()` callbacks of individual fields are not ignored anymore.

## [v1.1.0](https://github.com/naoray/nova-json/tree/v1.1.0) (2021-01-07)

**Added**
- nesting JSON fields is now possible

```php
JSON::make('Address', 'address', [
    Text::make('Street'),

    JSON::make('Location', [
        Text::make('Latitude'),
        Text::make('Longitude'),
    ]),
]);
```

## [v1.0.0](https://github.com/naoray/nova-json/tree/v1.0.0) (2020-12-18)

**Initial Release**
