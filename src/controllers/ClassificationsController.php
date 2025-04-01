<?php

namespace modules\htcmodule\controllers;

use Exception;

use modules\htcmodule\HTCModule;

use modules\htcmodule\records\Classification;

use putyourlightson\logtofile\LogToFile;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\db\Paginator;

class ClassificationsController extends Controller
{
  protected $allowAnonymous = [
  	'index', 'index2', 'index3', 'by-groupings'
  ];

  public function actionIndex() {
		if(Craft::$app->language == 'es-419') {
			$classificationNameColumn = 'name_Es';
		} else if(Craft::$app->language == 'zh-Hans') {
			$classificationNameColumn = 'name_Zh';
		} else {
			$classificationNameColumn = 'name_En';
		}

		$params = Craft::$app->request->getQueryParams();

		$classifications = Classification::find()
			->select("*, $classificationNameColumn as name ")
			->orderBy("$classificationNameColumn asc")
			->all();

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

		$this->asJson([
			'classifications' => $classifications
		]);
	}

  public function actionIndex2() {
		if(Craft::$app->language == 'es-419') {
			$classificationNameColumn = 'name_Es';
		} else if(Craft::$app->language == 'zh-Hans') {
			$classificationNameColumn = 'name_Zh';
		} else {
			$classificationNameColumn = 'name_En';
		}

		$params = Craft::$app->request->getQueryParams();

		$classifications = Classification::find()
			->select("name as value, $classificationNameColumn as name ")
			->orderBy("$classificationNameColumn asc")
			->all();

		$this->asJson([
			'classifications' => $classifications
		]);
	}

  public function actionIndex3() {
		if(Craft::$app->language == 'es-419') {
			$classificationNameColumn = 'name_Es';
		} else if(Craft::$app->language == 'zh-Hans') {
			$classificationNameColumn = 'name_Zh';
		} else {
			$classificationNameColumn = 'name_En';
		}

		$params = Craft::$app->request->getQueryParams();

		$classifications = Classification::find()
			->select("name as value, $classificationNameColumn as name ")
			->orderBy("$classificationNameColumn asc")
			->all();

    $this->renderTemplate('classifications.js', ['classifications' => $classifications]);
	}

  public function actionByGroupings($format) {
		if(Craft::$app->language == 'es-419') {
			$classificationNameColumn = 'name_Es';
		} else if(Craft::$app->language == 'zh-Hans') {
			$classificationNameColumn = 'name_Zh';
		} else {
			$classificationNameColumn = 'name_En';
		}

		$params = Craft::$app->request->getQueryParams();

		$classifications = Classification::find()
			->select("craft_htc_classifications.name as value, craft_htc_classifications.$classificationNameColumn as name, " .
				"craft_htc_classification_groupings.$classificationNameColumn as grouping")
			->join('join', 'craft_htc_classification_groupings', 'craft_htc_classification_groupings.id = craft_htc_classifications.groupingId')
			->orderBy("craft_htc_classifications.displayOrder asc")
			->all();

		$classificationsByGroupings = [];
		for($i=0; $i < count($classifications); $i+=1) {
			if(!isset($classificationsByGroupings[$classifications[$i]['grouping']])) {
				$classificationsByGroupings[$classifications[$i]['grouping']] = [];
			}
			$classificationsByGroupings[$classifications[$i]['grouping']][] = $classifications[$i];
		}

		if($format == 'json') {
			$this->asJson($classificationsByGroupings);
		} else {
    	$this->renderTemplate('classifications.js', ['classifications' => $classificationsByGroupings]);
		}
	}
}
