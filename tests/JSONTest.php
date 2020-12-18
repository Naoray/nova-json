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

        $json->data[0]->resolve($userWithData, 'street');
        $this->assertEquals('test street', $json->data[0]->value);
    }

    /** @test */
    public function it_fills_fields_with_the_updated_values()
    {
        $user = new User(['address' => ['street'=> '']]);
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ]);

        $json->data[0]->fillInto(new NovaRequest(['street' => 'test street']), $user, 'street');
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
        $json->data[0]->fillInto(new NovaRequest(['street' => 'test street']), $user, 'street');
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
