<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Agency;

class Stops extends Controller
{
    /**
     * Generate GTFS stops.txt specification
     *
     * Controller - generates stops based on Mapbox API Reverse geocoding
     * Reference: https://docs.mapbox.com/api/search/#reverse-geocoding
     *
     * @since 05.06.2020
     *
     * @see Function/method/class relied on
     * @link baseurl/api/generate_stops
     *
     * @param Object $request Description.
     * @return type Description.
     */
    function generate_stops(Request $request) {
        // geojson input
        $input_json = $request->input();
        $features = $input_json['features'];
        $coordinates = $features[0]['geometry']['coordinates'];
        // $coordinates = $input_json['coordinates'];
        $endpoint = config('mapboxApi.endpoint.geocoding');
        $stops = [];
        $index = 0;
        
        foreach ($coordinates as $stop) {
            $longitude = $stop[0];
            $latitude = $stop[1];
            $geolocation = $this->sendApiRequest(
                $endpoint, 
                $longitude, 
                $latitude);
            $stops[$index] = $this->genereate_stop_data_arr($geolocation);
            $index++;
        }
        
        return response()->json($stops);
    }

    private function genereate_stop_data_arr($geolocation) {
        if(isset($geolocation['features'][0]['properties']['address'])) {
            $address = $geolocation['features'][0]['properties']['address'];
            $address = str_replace(',', ' ', $address); // Remove any comma from address property
        }

        $feature_text = $geolocation['features'][0]['text'];
        $stop_name = empty($address) ? $feature_text : $address;
       
        $stop_id = uniqid();
        $stop_code = "";
        $stop_desc = "";
        $stop_lat = $geolocation['query'][1];
        $stop_lon = $geolocation['query'][0];
        $zone_id = "";
        $stop_url = "";
        $location_type = 0;
        $parent_station = "";
        $stop_timezone = "";
        $wheelchair_boarding = "";

        $data = "$stop_id,$stop_code,$stop_name,$stop_desc,$stop_lat,$stop_lon,$zone_id,$stop_url,"
                . "$location_type,$parent_station,$stop_timezone,$wheelchair_boarding";
                
        return $data;
    }

    private function sendApiRequest($endpoint, $longitude, $latitude) {
        $mabox_URL = config('mapboxApi.url');
        $access_token = config('mapboxApi.access_token');
        $request_URL = 
            $mabox_URL 
            . $endpoint 
            . "$longitude,$latitude"
            . ".json?access_token=$access_token";
        $response = Http::get($request_URL);
        return $response;
    }
}   
