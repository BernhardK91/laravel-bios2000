<?php

namespace Bios2000\Tests\Unit;

use Bios2000\Models\Adresse;
use Bios2000\Models\Ansprechpartner;
use Bios2000\Models\Artikel;
use Bios2000\Models\ArtikelLager;
use Bios2000\Models\ArtikelZusatztext;
use Bios2000\Models\ChaotLager;
use Bios2000\Tests\TestCase;
use Carbon\Carbon;

class ArtikelTest extends TestCase
{
    /** @test */
    function a_artikel_model_has_needed_columns()
    {
        $expectedDatabaseColumns = [
            'ARTNR', 'KB', 'ZN', 'BN', 'BEZ_1', 'BEZ_2', 'BEZ_3', 'BEZ_4', 'BEZ_5', 'BEZ_6', 'BEZ_7', 'BEZ_8',
            'EAN_NUMMER', 'INFO', 'PRL_POS', 'GEWICHT', 'V_EINHEIT', 'ARBEITSPREIS', 'GUE_BEME', 'EK', 'DURCH_EK',
            'MIN_DB', 'WG', 'RG', 'GARANTIE_ZEIT', 'RASTER_VK', 'PE_VK', 'STATUS', 'US', 'SL_JN', 'MS_JN', 'EM_JN',
            'ME', 'DK_1', 'DK_2', 'DK_3', 'DK_4', 'DK_5', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10',
            'SACHMERKMAL', 'VIEWER_PFAD', 'WARENNUMMER', 'URSPRUNG_LAND', 'URSPRUNG_REGION', 'MASSEINHEIT',
            'EM1_NUMMER', 'EM2_NUMMER', 'EM3_NUMMER', 'EM1_METHODE', 'EM2_METHODE', 'EM3_METHODE', 'EM1_ANTEIL',
            'EM2_ANTEIL', 'EM3_ANTEIL', 'DURCHMESSER', 'LAENGE', 'BREITE', 'HOEHE', 'SPEZ_GEWICHT', 'B_BASIS',
            'P_BASIS', 'LEAB', 'LEZU', 'ANLAGE_DATUM', 'AENDERUNG_DATUM', 'ANLAGE_USER_NR', 'AENDERUNG_USER_NR',
            'KALK_KZ', 'DISPO_STOP', 'KALK_KZ_VK', 'ZN_REV', 'MASSE_SIND_MM', 'MASSE_FUER_FA', 'AMM_PRINT',
            'AMM_ART_KEY1', 'AMM_ART_KEY2', 'AMM_ART_KEY3', 'AMM_ART_KEY4', 'AMM_ART_KEY5', 'AMM_INHALT_KEY1',
            'AMM_INHALT_KEY2', 'AMM_INHALT_KEY3', 'AMM_INHALT_FREI4', 'AMM_INHALT_FREI5', 'BV_PLAN_NUMMER',
            'BV_SI_TAGE', 'PRUEF_PFLICHTIG', 'MASSE_FREITEXT', 'PDM_GEPFLEGT', 'AL_NUMMER', 'PACKMITTEL',
            'PACKMITTEL_VERSION', 'PACKMITTEL_STUECK_IN_PM', 'PACKMITTEL_IST_PM', 'ECCN_NUMMER', 'ZOLLTARIF_NUMMER',
            'PRAEFERENZ', 'FEINPLANUNG', 'VORGABELAGER_ZU', 'VORGABELAGER_ZU_DV', 'VORGABELAGER_AB',
            'VORGABELAGER_AB_DV', 'ALTERNATE_ARTNR_1', 'ALTERNATE_ARTNR_2', 'ALTERNATE_ARTNR_3', 'CHARGENPFLICHTIG',
            'PREISAENDERUNG_BEMERKUNG', 'PRAEFERENZ_ARTIKEL', 'PRAEFERENZ_ANTEIL', 'PRAEFERENZ_UE_DATUM',
            'VERPACKUNGSKOSTEN', 'KALK_AUFSCHLAG_GRUPPE', 'HYPERLINK'
        ];

        $this->assertModelHasColumns($expectedDatabaseColumns, Artikel::class);
    }

    /** @test */
    function a_artikel_has_chaotlager()
    {
        $articlenumber = ChaotLager::first()->ARTNR;

        $article = Artikel::find($articlenumber);

        $this->assertInstanceOf(ChaotLager::class, $article->chaotLager()->first());
    }

    /** @test */
    function it_has_zusatztexte()
    {
        $articlenumber = ArtikelZusatztext::first()->ARTNR;

        $article = Artikel::find($articlenumber);

        $this->assertInstanceOf(ArtikelZusatztext::class, $article->zusatztexte()->first());
    }

    /** @test */
    function it_has_lager()
    {
        $articlenumber = ArtikelLager::where('ARTNR', '!=', '')->first()->ARTNR;

        $article = Artikel::find($articlenumber);

        $this->assertInstanceOf(ArtikelLager::class, $article->lager()->first());
    }

    /** @test */
    function anlage_datum_and_aenderung_datum_are_carbon_objects()
    {
        $article = Artikel::first();

        $this->assertInstanceOf(Carbon::class, $article->ANLAGE_DATUM);
        $this->assertInstanceOf(Carbon::class, $article->AENDERUNG_DATUM);
    }

    /** @test */
    function it_returns_stock()
    {
        $articlenumber = ArtikelLager::where('ARTNR', '!=', '')->first();

        $article = Artikel::find($articlenumber)->first();

        $this->assertObjectHasAttribute('SUM_BESTAND', $article->bestand());
        $this->assertObjectHasAttribute('SUM_BESTELLT', $article->bestand());
        $this->assertObjectHasAttribute('SUM_RUECKSTAND', $article->bestand());
        $this->assertObjectHasAttribute('SUM_MIBEST', $article->bestand());
        $this->assertObjectHasAttribute('SUM_SOLLBEST', $article->bestand());
        $this->assertObjectHasAttribute('SUM_BBK', $article->bestand());
        $this->assertObjectHasAttribute('SUM_RESERVIERT', $article->bestand());
    }
}
