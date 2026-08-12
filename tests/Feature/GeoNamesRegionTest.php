<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoNamesRegionTest extends TestCase
{
    public function test_it_can_fetch_countries_and_subdivisions_from_geonames(): void
    {
        Http::fake([
            'https://secure.geonames.org/countryInfoJSON*' => Http::response([
                'geonames' => [
                    [
                        'countryName' => 'Indonesia',
                        'countryCode' => 'ID',
                        'continentName' => 'Asia',
                    ],
                ],
            ], 200),
            'https://secure.geonames.org/searchJSON*' => Http::response([
                'geonames' => [
                    [
                        'name' => 'Jawa Barat',
                        'adminCode1' => '30',
                        'countryCode' => 'ID',
                        'geonameId' => 1642668,
                    ],
                ],
            ], 200),
        ]);

        $countriesResponse = $this->getJson('/api/geo/countries');
        $countriesResponse->assertStatus(200)
            ->assertJsonPath('countries.0.countryName', 'Indonesia')
            ->assertJsonPath('countries.0.countryCode', 'ID');

        $subdivisionsResponse = $this->getJson('/api/geo/subdivisions/ID');
        $subdivisionsResponse->assertStatus(200)
            ->assertJsonPath('subdivisions.0.name', 'Jawa Barat')
            ->assertJsonPath('subdivisions.0.countryCode', 'ID');
    }
}
