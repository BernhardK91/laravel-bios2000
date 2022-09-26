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
