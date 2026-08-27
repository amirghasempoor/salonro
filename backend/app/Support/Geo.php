<?php

namespace App\Support;

class Geo
{
    /**
     * Radius of the Earth in kilometres.
     */
    private const int EARTH_RADIUS_KM = 6371;

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
}
