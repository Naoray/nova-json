# Changelog

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
