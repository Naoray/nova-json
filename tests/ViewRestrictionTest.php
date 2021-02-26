<?php

namespace Naoray\NovaJson\Tests;

use Laravel\Nova\Fields\Text;
use Naoray\NovaJson\JSON;

class ViewRestrictionTest extends TestCase
{
    /** @test */
    public function it_can_show_fields_only_on_index()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->onlyOnIndex();

        $this->assertTrue($json->data[0]->showOnIndex);
        $this->assertFalse($json->data[0]->showOnDetail);
        $this->assertFalse($json->data[0]->showOnCreation);
        $this->assertFalse($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_show_fields_only_on_detail()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->onlyOnDetail();

        $this->assertFalse($json->data[0]->showOnIndex);
        $this->assertTrue($json->data[0]->showOnDetail);
        $this->assertFalse($json->data[0]->showOnCreation);
        $this->assertFalse($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_show_fields_only_on_forms()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->onlyOnForms();

        $this->assertFalse($json->data[0]->showOnIndex);
        $this->assertFalse($json->data[0]->showOnDetail);
        $this->assertTrue($json->data[0]->showOnCreation);
        $this->assertTrue($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_show_fields_exept_on_forms()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->exceptOnForms();

        $this->assertTrue($json->data[0]->showOnIndex);
        $this->assertTrue($json->data[0]->showOnDetail);
        $this->assertFalse($json->data[0]->showOnCreation);
        $this->assertFalse($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_hide_fields_from_index_view()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->hideFromIndex();

        $this->assertFalse($json->data[0]->showOnIndex);
        $this->assertTrue($json->data[0]->showOnDetail);
        $this->assertTrue($json->data[0]->showOnCreation);
        $this->assertTrue($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_hide_fields_from_detail_view()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->hideFromDetail();

        $this->assertTrue($json->data[0]->showOnIndex);
        $this->assertFalse($json->data[0]->showOnDetail);
        $this->assertTrue($json->data[0]->showOnCreation);
        $this->assertTrue($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_hide_fields_from_creating_view()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->hideWhenCreating();

        $this->assertTrue($json->data[0]->showOnIndex);
        $this->assertTrue($json->data[0]->showOnDetail);
        $this->assertFalse($json->data[0]->showOnCreation);
        $this->assertTrue($json->data[0]->showOnUpdate);
    }

    /** @test */
    public function it_can_hide_fields_from_updating_view()
    {
        $json = JSON::make('Address', 'address', [
            Text::make('Street'),
        ])->hideWhenUpdating();

        $this->assertTrue($json->data[0]->showOnIndex);
        $this->assertTrue($json->data[0]->showOnDetail);
        $this->assertTrue($json->data[0]->showOnCreation);
        $this->assertFalse($json->data[0]->showOnUpdate);
    }
}
