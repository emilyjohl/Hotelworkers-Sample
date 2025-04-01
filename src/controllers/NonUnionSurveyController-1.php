<?php

namespace modules\htcmodule\controllers;

use Exception;

use modules\htcmodule\HTCModule;

use modules\htcmodule\records\Contract;
use modules\htcmodule\records\Classification;
use modules\htcmodule\records\WageRate;
use modules\htcmodule\records\MasterZone;
use modules\htcmodule\records\WageItem;
use modules\htcmodule\records\WageItemRate;
use modules\htcmodule\records\Zip;

use putyourlightson\logtofile\LogToFile;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\db\Paginator;
use yii\db\Expression;

class NonUnionSurveyController extends Controller
{
    protected $allowAnonymous = [
    	'get-non-union-survey-data'
    ];


	public function actionGetNonUnionSurveyData() {
		$params = Craft::$app->request->getQueryParams();

	    $masterZones = MasterZone::find()
	    	->select('craft_htc_master_zones.*, craft_htc_contracts.name as contractName, ' .
	    		'craft_htc_contracts.fullName as contractFullName, ' .
	    		'craft_htc_contracts.expirationYear as contractExpirationYear,' .
	    		'craft_htc_contracts.id as contractId')
			->leftJoin('craft_htc_contracts', 'craft_htc_contracts.id = craft_htc_master_zones.contractId')
			->all();
		$masterZonesMap = [];
		foreach($masterZones as $masterZone) {
			$masterZonesMap[$masterZone->name] = [
				'id' => $masterZone->id,
				'name' => $masterZone->name,
				'contractName' => $masterZone->contractName,
				'contractFullName' => $masterZone->contractFullName,
				'contractExpirationYear' => $masterZone->contractExpirationYear,
				'contractId' => $masterZone->contractId
			];
		}

		$classifications = Classification::find()->all();
		$classificationsMap = [];
		foreach($classifications as $classification) {
			$classificationsMap[$classification->name] = [
				'id' => $classification->id,
				'name' => $classification->name,
				'tippedIwa' => $classification->tippedIwa,
				'tippedGriwa' => $classification->tippedGriwa
			];
		}

		$zips = Zip::find()->select('craft_htc_zips.zip, craft_htc_zips.county, craft_htc_master_zones.name as masterZoneName')
			->leftJoin('craft_htc_master_zones', 'craft_htc_master_zones.id = craft_htc_zips.masterZoneId')
			->all();
		$zipsMap = [];
		foreach($zips as $zip) {
			$zipsMap[$zip->zip] = [
				"masterZone" => $zip->masterZoneName,
				"county" => $zip->county
			];
		}

		$wageRates = WageRate::find()
			->select(new Expression(<<<END
				craft_htc_master_zones.name as masterZoneName,
				craft_htc_classifications.name as classificationName,
				craft_htc_wage_rates.rate,
				date_format(craft_htc_wage_rates.dateTakesEffect, '%M %e, %Y') as dateTakesEffect
			END))
			->leftJoin('craft_htc_master_zones', 'craft_htc_wage_rates.masterZoneId = craft_htc_master_zones.id')
			->leftJoin('craft_htc_classifications', 'craft_htc_wage_rates.classificationId = craft_htc_classifications.id')
			->where(['<=', 'craft_htc_wage_rates.dateTakesEffect', date('c')])
			->orderBy('dateTakesEffect desc')
			->all();
		$wageRatesMap = [];
		foreach($wageRates as $classificationRate) {
			if(!isset($wageRatesMap[$classificationRate->masterZoneName])) {
				$wageRatesMap[$classificationRate->masterZoneName] = [];
			}

			if(!isset($wageRatesMap[$classificationRate->masterZoneName][$classificationRate->classificationName])) {
				$wageRatesMap[$classificationRate->masterZoneName][$classificationRate->classificationName] =
					$classificationRate->rate;
				$wageRatesMap[$classificationRate->masterZoneName][$classificationRate->classificationName . ' Date Takes Effect'] =
					$classificationRate->dateTakesEffect;
			}
		}

		$wageItemRates = WageItemRate::find()
			->select(new Expression(<<<END
				craft_htc_master_zones.name as masterZoneName,
				craft_htc_wage_items.name as wageItemName,
				craft_htc_wage_item_rates.rate,
				date_format(craft_htc_wage_item_rates.dateTakesEffect, '%M %e, %Y') as dateTakesEffect
			END))
			->leftJoin('craft_htc_master_zones', 'craft_htc_wage_item_rates.masterZoneId = craft_htc_master_zones.id')
			->leftJoin('craft_htc_wage_items', 'craft_htc_wage_item_rates.wageItemId = craft_htc_wage_items.id')
			->where(['<=', 'craft_htc_wage_item_rates.dateTakesEffect', date('c')])
			->orderBy('dateTakesEffect desc')
			->all();
		$wageItemRatesMap = [];
		foreach($wageItemRates as $wageItemRate) {
			if(!isset($ratesItem[$wageItemRate->masterZoneName])) {
				$ratesItem[$wageItemRate->masterZoneName] = [];
			}

			if(!isset($wageItemRatesMap[$wageItemRate->masterZoneName][$wageItemRate->wageItemName])) {
				$wageItemRatesMap[$wageItemRate->masterZoneName][$wageItemRate->wageItemName] = $wageItemRate->rate;
				$wageItemRatesMap[$wageItemRate->masterZoneName][$wageItemRate->wageItemName . ' Date Takes Effect'] = $wageItemRate->dateTakesEffect;
			}
		}

		$this->asJson([
			'masterZones' => $masterZonesMap,
			'classifications' => $classificationsMap,
			'zips' => $zipsMap,
			'wageRates' => $wageRatesMap,
			'wageItemRates' => $wageItemRatesMap
		]);
	}
}
