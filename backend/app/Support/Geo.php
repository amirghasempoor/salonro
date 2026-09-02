<?php

namespace App\Support;

class Geo
{
    /**
     * Radius of the Earth in kilometres.
     */
    private const int EARTH_RADIUS_KM = 6371;

    /**
     * Kilometres per degree of latitude (constant everywhere on the sphere).
     */
    private const float KM_PER_DEGREE_LAT = self::EARTH_RADIUS_KM * M_PI / 180;

    /**
     * Haversine SQL expression for the great-circle distance, in kilometres,
     * between the given lat/lng columns and a fixed point.
     *
     * Returns the raw expression together with its positional bindings so
     * callers can drop it straight into selectRaw()/whereRaw(). It relies only
     * on functions available in both SQLite and PostgreSQL
     * (sin/cos/radians/asin/sqrt/power and `cast(... as real)`), so the same
     * expression runs against the test and production databases alike.
     *
     * The whole result is cast to `real` so that, when aliased as a derived
     * column, it carries numeric affinity — without it SQLite compares the
     * computed distance against string-bound parameters (e.g. a `<= 10`
     * filter) textually and never filters.
     *
     * This is O(n) over whatever rows reach it — callers should narrow the
     * candidate set first with boundingBox() so it isn't run against the
     * whole table on every request.
     *
     * @return array{0: string, 1: array<int, float>} [expression, bindings]
     */
    public static function distanceInKmSql(string $latColumn, string $lngColumn, float $lat, float $lng): array
    {
        $sql = 'cast(2 * '.self::EARTH_RADIUS_KM.' * asin(sqrt('
            ."power(sin(radians((cast($latColumn as real) - ?) / 2)), 2)"
            ." + cos(radians(?)) * cos(radians(cast($latColumn as real)))"
            ." * power(sin(radians((cast($lngColumn as real) - ?) / 2)), 2)"
            .')) as real)';

        return [$sql, [$lat, $lat, $lng]];
    }

    /**
     * Approximate lat/lng bounding box around a point for the given radius.
     *
     * Meant as a cheap, index-friendly pre-filter that runs before the exact
     * (and much more expensive) haversine calculation from distanceInKmSql():
     * filtering on plain lat/lng columns first lets the database use a b-tree
     * index on those columns to shrink the candidate set, instead of
     * computing trigonometry for every row in the table.
     *
     * The box is deliberately a superset of the circle — its corners sit
     * farther than $radiusKm from the centre — so callers must still apply
     * the exact distance filter on top of it; this only narrows what that
     * filter has to run against.
     *
     * @return array{latMin: float, latMax: float, lngMin: float, lngMax: float}
     */
    public static function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $latDelta = $radiusKm / self::KM_PER_DEGREE_LAT;

        // Degrees of longitude shrink towards the poles; clamp cos() away
        // from zero so a search near the poles doesn't blow up the box.
        $kmPerDegreeLng = self::KM_PER_DEGREE_LAT * max(cos(deg2rad($lat)), 0.000001);
        $lngDelta = $radiusKm / $kmPerDegreeLng;

        return [
            'latMin' => $lat - $latDelta,
            'latMax' => $lat + $latDelta,
            'lngMin' => $lng - $lngDelta,
            'lngMax' => $lng + $lngDelta,
        ];
    }
}
