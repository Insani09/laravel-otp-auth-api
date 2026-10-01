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
            ->assertJsonPath('results.0.id', 'ID')
            ->assertJsonPath('results.0.text', 'Indonesia');

        $subdivisionsResponse = $this->getJson('/api/geo/subdivisions/ID');
        $subdivisionsResponse->assertStatus(200)
            ->assertJsonPath('results.0.id', '30')
            ->assertJsonPath('results.0.text', 'Jawa Barat');
    }

    public function test_it_rejects_invalid_country_code(): void
    {
        $this->getJson('/api/geo/subdivisions/INVALID')
            ->assertStatus(422);
    }

    public function test_it_falls_back_to_502_when_geonames_fails(): void
    {
        config(['services.geonames.username' => 'testing-username']);

        Http::fake([
            'https://secure.geonames.org/*' => Http::response([
                'status' => ['message' => 'the daily limit of 30000 credits for xxx has been exceeded'],
            ], 200),
        ]);

        $this->getJson('/api/geo/countries')
            ->assertStatus(502);
    }
}
