<?php

namespace Bios2000\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Class Archive
 * @package Bios2000\Models
 * @deprecated
 */
class Archive
{

    /**
     * Returns meta data of a single delivery note
     *
     * @param int $number
     * @return mixed
     */
    public function deliverynote(int $number)
    {
        $filter = [
            'ART' => 'LS',
            'BELEG' => $number,
        ];

        /*
         * Return only first Result
         */
        return $this->aw($filter)[0][0];
    }

    /**
     * Returns meta data of a single delivery note
     *
     * @param int $number
     * @return mixed
     */
    public function deliverynote_posten(int $number)
    {
        $filter = [
            'ART' => 'LS',
            'BELEG' => $number,
        ];

        /*
         * Return only first Result
         */
        return $this->aw($filter, false)[0];
    }

    /**
     * Returns meta data of delivery notes
     *
     * @param array $filter
     * @param bool $withPosten
     * @return array
     */
    public function find_deliverynotes(array $filter = [], bool $withPosten = false): array
    {
        $defaultFilter = [
            'ART' => 'LS',
        ];

        $filter = array_merge($defaultFilter, $filter);

        /*
         * Receives all matched results as collection grouped by year and converted to single array
         */
        $resultCollection = $this->aw($filter);
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
                $result->posten = $this->deliverynote_posten($result->BELEG);
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

        $result = new Collection();

        while(1) {
            if($kopf) {
                $tableName = config('database.connections.bios2000.dba') . '.dbo.' . $archive . '_ARCHIV_' . $year . '_KOPF';
            } else {
                $tableName = config('database.connections.bios2000.dba') . '.dbo.' . $archive . '_ARCHIV_' . $year . '_POSTEN';
            }

            try {

                if($kopf) {
                    $item = DB::connection('bios2000')->table($tableName)
                        ->where($arguments)
                        ->orderBy('VERSION', 'desc')
                        ->get()->all();
                } else {
                    $item = DB::connection('bios2000')->table($tableName)
                        ->where('POSITIONS_NR', '!=', null)
                        ->where($arguments)
                        ->orderBy('VERSION', 'desc')
                        ->get()->all();
                }

                if (count($item) > 0) {
                    $result->push($item);
                }

            } catch (Exception $e) {
                break;
            }

            $year--;

        }

        return $result;
    }

}
