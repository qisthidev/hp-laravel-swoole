<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravolt\Indonesia\Facade as Indonesia;

class WilayahController extends Controller
{
    /**
     * Get all provinces.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function provinces()
    {
        $provinces = Indonesia::allProvinces();

        return response()->json($provinces);
    }

    /**
     * Get cities based on a province ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cities(Request $request)
    {
        $request->validate([
            'province_id' => 'required|exists:indonesia_provinces,id',
        ]);

        $province = Indonesia::findProvince($request->province_id, ['cities']);

        if (!$province) {
            return response()->json(['message' => 'Province not found'], 404);
        }

        return response()->json($province->cities);
    }

    /**
     * Get detailed information for a specific city, including its districts and villages.
     * We don't have postal codes in this package, so we'll return null for that.
     * You might need to integrate another data source for postal codes.
     *
     * @param  int  $cityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCity($cityId)
    {
        // Eager load the relationships for better performance
        $city = Indonesia::findCity($cityId, ['province', 'districts.villages']);

        if (!$city) {
            return response()->json(['message' => 'City not found'], 404);
        }

        // The structure is already quite good, we can just return it.
        // The front-end will need to be adapted to this new data structure.
        return response()->json($city);
    }
}
