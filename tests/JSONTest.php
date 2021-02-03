<?php

namespace Naoray\NovaJson\Tests;

use Naoray\NovaJson\JSON;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Naoray\NovaJson\Exceptions\AttributeCast;
use Illuminate\Foundation\Auth\User as Authenticatable;

class JSONTest extends TestCase
{
    /** @test */
    public function it_works_when_passing_no_fields_to_it()
    {
        $json = JSON::make('', []);

        $this->assertEquals([], $json->data);
    }

    /** @test */
    public function it_automatically_resolves_the_label_to_the_attribute_name_if_no_attribute_was_given()
    {
        $json = JSON::make('Address', []);

        $this->assertEquals('address', $json->attribute);
    }

    /** @test */
    public function it_resolves_fields_to_the_given_column_name()
    {
        $userWithData = new User(['address' => ['street' => 'test street']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ]);

        $json->data[0]->resolve($userWithData, 'address->street');
        $this->assertEquals('test street', $json->data[0]->value);
    }

    /** @test */
    public function it_fills_fields_with_the_updated_values()
    {
        $user = new User(['address' => ['street' => '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ]);

        $json->data[0]->fillInto(new NovaRequest(['address->street' => 'test street']), $user, 'address->street');
        $this->assertEquals('test street', $user->address['street']);
    }

    /** @test */
    public function it_throws_a_no_attribute_cast_exception_if_the_json_attribute_was_not_casted()
    {
        $user = new UserWithoutCasts();
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ]);

        $this->expectException(AttributeCast::class);
        $json->data[0]->fillInto(new NovaRequest(['address->street' => 'test street']), $user, 'address->street');
    }

    /** @test */
    public function it_allows_nested_json_fields()
    {
        $user = new User(['address' => ['street' => '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),

            JSON::make('Location', [
                Text::make('Latitude'),
                Text::make('Longitude'),
            ]),
        ]);

        $request = new NovaRequest(['address->location->latitude' => 'some-val', 'address->location->longitude' => 'other-val']);
        $json->data[1]->fillInto($request, $user, 'address->location->latitude');
        $json->data[2]->fillInto($request, $user, 'address->location->longitude');

        $this->assertEquals(['street' => '', 'location' => ['latitude' => 'some-val', 'longitude' => 'other-val']], $user->address);
    }

    /** @test */
    public function it_respects_fillable_callbacks_to_retrieve_values()
    {
        $user = new User(['address' => ['street' => '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street')->fillUsing(fn ($request, $model, $attribute, $requestAttribute) => $request[$requestAttribute] . ' Foo'),
        ]);

        $request = new NovaRequest(['address->street' => 'some-val']);
        $json->data[0]->fillInto($request, $user, 'address->street');
        $this->assertEquals('some-val Foo', $user->address['street']);
    }

    /** @test */
    public function it_can_fill_all_json_values_at_once()
    {
        $user = new User(['address' => ['street' => '', 'city' => '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
            Text::make('City'),
        ])->fillAtOnce();

        $request = new NovaRequest(['address->street' => 'some-val', 'address->city' => 'other-val', 'nonjson' => 'foo']);
        $json->data[0]->fillInto($request, $user, 'address->street');
        $json->data[1]->fillInto($request, $user, 'address->city');
        $this->assertEquals('', $user->address['street']);
        $this->assertEquals('', $user->address['city']);

        collect($json->data)->last()->fillInto($request, $user, 'address');
        $this->assertEquals(['street' => 'some-val', 'city' => 'other-val'], $user->address);
    }

    /** @test */
    public function it_respects_the_fill_all_values_at_once_callback()
    {
        $user = new User(['address' => ['street' => '', 'city' => '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
            Text::make('City'),
        ])->fillAtOnce(function ($request, $requestValues, $model, $attribute, $requestAttribute) {
            return ['nested' => $requestValues];
        });

        $request = new NovaRequest(['address->street' => 'some-val', 'address->city' => 'other-val', 'nonjson' => 'foo']);

        collect($json->data)->last()->fillInto($request, $user, 'address');
        $this->assertEquals(['nested' => ['street' => 'some-val', 'city' => 'other-val']], $user->address);
    }

    /** @test */
    public function it_respects_the_fill_all_values_at_once_callback_and_individual_field_callbacks()
    {
        $user = new User(['address' => ['street' => '', 'city' => '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street')->fillUsing(fn ($request, $model, $attribute, $requestAttribute) => $request[$requestAttribute] . ' Foo'),
            Text::make('City'),
        ])->fillAtOnce(function ($request, $requestValues, $model, $attribute, $requestAttribute) {
            return ['nested' => $requestValues];
        });

        $request = new NovaRequest(['address->street' => 'some-val', 'address->city' => 'other-val', 'nonjson' => 'foo']);

        collect($json->data)->last()->fillInto($request, $user, 'address');
        $this->assertEquals(['nested' => ['street' => 'some-val Foo', 'city' => 'other-val']], $user->address);
    }
}

class User extends Authenticatable
{
    protected $guarded = [];

    protected $casts = [
        'address' => 'array',
    ];
}

class UserWithoutCasts extends Authenticatable
{
    protected $guarded = [];

    protected $casts = [];
}
