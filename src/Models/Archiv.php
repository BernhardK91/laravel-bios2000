<?php

namespace Bios2000\Models;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * TODO: This should be optimized.
 */

/**
 * Class Archiv
 * @package Bios2000\Models
 */
class Archiv
{

    /**
     * Returns meta data of a single delivery note
     *
     * @param int $number
     * @return mixed
     */
    public static function deliverynote(int $number)
    {
        $filter = [
            'ART' => 'LS',
            'BELEG' => $number,
        ];

        /*
         * Return only first Result
         */
        return (new Archiv)->aw($filter)[0][0];
    }

    /**
     * Returns meta data of a single delivery note
     *
     * @param int $number
     * @return mixed
     */
    public static function deliverynotePosten(int $number)
    {
        $filter = [
            'ART' => 'LS',
            'BELEG' => $number,
        ];

        /*
         * Return only first Result
         */
        return (new Archiv)->aw($filter, false)[0];
    }

    /**
     * Returns meta data of delivery notes
     *
     * @param array $filter
     * @param bool $withPosten
     * @return array
     */
    public static function findDeliverynotes(array $filter = [], bool $withPosten = false): array
    {
        $archiv = new Archiv();

        $defaultFilter = [
            'ART' => 'LS',
        ];

        $filter = array_merge($defaultFilter, $filter);

        /*
         * Receives all matched results as collection grouped by year and converted to single array
         */
        $resultCollection = $archiv->aw($filter);
        $results = [];

        foreach ($resultCollection as $year) {
            foreach ($year as $deliveryNote) {
                if (
                    isset($results[$deliveryNote->BELEG])
                    && (int) $results[$deliveryNote->BELEG]->VERSION > (int) $deliveryNote->VERSION
                ) {
                    continue;
                }

                $results[$deliveryNote->BELEG] = $deliveryNote;
            }
        }

        /*
         * Load all item if a delivery note is found the items needed
         */
        if ($withPosten && count($results) > 0) {
            foreach ($results as $result) {
                $result->posten = $archiv->deliverynotePosten($result->BELEG);
            }
        }

        return $results;
    }

    /**
     * Returns result filtered by $arguments of "Auftragswesen" (AW)
     *
     * @param array $arguments
     * @param bool $kopf
     * @return Collection
     */
    private function aw(array $arguments, bool $kopf = true)
    {
        return $this->archive('AW', $arguments, $kopf);
    }

    /**
     * Returns result of all archives filtered by $arguments, selected by $archive
     *
     * @param string $archive
     * @param array $arguments
     * @param bool $kopf (default: true) true = _KOPF, false = _POSTEN
     * @return Collection
     */
    private function archive(string $archive, array $arguments, bool $kopf = true)
    {
        $year = date('Y');

        $result = new Collection;

        while (1) {
            if ($kopf) {
                $tableName = config('database.connections.bios2000.dba') . '.dbo.' . $archive . '_ARCHIV_' . $year . '_KOPF';
            } else {
                $tableName = config('database.connections.bios2000.dba') . '.dbo.' . $archive . '_ARCHIV_' . $year . '_POSTEN';
            }

            try {
                DB::connection('bios2000')->table($tableName)->select('ART')->first();
            } catch (Exception $e) {
                if ($year == date('Y')) {
                    continue;
                }
                break;
            }

            if ($kopf) {
                if (DB::connection('bios2000')->table($tableName)
                    ->where($arguments)
                    ->orderBy('VERSION', 'desc')
                    ->count() != 0) {

                    if ($year < date('Y')) {
                        $dbResult = Cache::rememberForever($this->buildCacheKey($arguments, $archive, $year, $kopf),
                            function () use ($tableName, $arguments) {
                                return DB::connection('bios2000')->table($tableName)
                                    ->where($arguments)
                                    ->orderBy('VERSION', 'desc')->get()->all();
                            });
                    } else {
                        $dbResult = DB::connection('bios2000')->table($tableName)
                            ->where($arguments)
                            ->orderBy('VERSION', 'desc')->get()->all();
                    }

                    $result->push($dbResult);
                }

            } else {
                if (DB::connection('bios2000')->table($tableName)
                    ->where('POSITIONS_NR', '!=', null)
                    ->where($arguments)
                    ->orderBy('VERSION', 'desc')
                    ->count() != 0) {

                    if ($year < date('Y')) {
                        $dbResult = Cache::rememberForever($this->buildCacheKey($arguments, $archive, $year, $kopf),
                            function () use ($tableName, $arguments) {
                                return DB::connection('bios2000')->table($tableName)
                                    ->where('POSITIONS_NR', '!=', null)
                                    ->where($arguments)
                                    ->orderBy('VERSION', 'desc')->get()->all();
                            });

                    } else {
                        $dbResult = DB::connection('bios2000')->table($tableName)
                            ->where('POSITIONS_NR', '!=', null)
                            ->where($arguments)
                            ->orderBy('VERSION', 'desc')->get()->all();
                    }

                    $result->push($dbResult);

                }
            }

            $year--;
        }

        return $result;
    }

    /**
     * Builds a cache key based on the given arguments, archive, year, and type.
     *
     * @param array $arguments The array of arguments to include in the key.
     * @param string $archive The archive identifier.
     * @param string $year The year associated with the cache key.
     * @param bool $kopf Determines if the key is for 'kopf' or 'posten'.
     * @return string The generated cache key.
     */
    private function buildCacheKey(array $arguments, string $archive, string $year, bool $kopf): string
    {
        $strArguments = [];
        $strKopf = $kopf ? 'kopf' : 'posten';

        foreach ($arguments as $argument) {
            if (is_array($argument)) {
                $strArguments[] = implode('-', $argument);
            } else {
                $strArguments[] = $argument;
            }
        }

        return 'bios2000_archive_' . $archive . '_' . $year . '_' . $strKopf . '_' . implode('-', $strArguments);
    }
}
