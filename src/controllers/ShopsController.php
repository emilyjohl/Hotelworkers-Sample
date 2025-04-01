<?php

namespace modules\htcmodule\controllers;

use modules\htcmodule\HTCModule;

use putyourlightson\logtofile\LogToFile;

use modules\htcmodule\records\Shop;

use Craft;
use craft\web\Controller;

class ShopsController extends Controller
{
    protected $allowAnonymous = ['get-book-union-shops', 'get-all-shops-json', 'get-all-shops-json2', 'get-all-shops-js'];

    public function actionGetBookUnionShops() {
        $params = Craft::$app->request->getQueryParams();

        $shops = Shop::find()->select('id, name, status, showOnMap, website, phone, ' .
            'address, city, state, zip, latitude, longitude, location, quarantineHotel, shopType,' .
            'covid19Status, covid19ReopeningDate, covid19ReopeningDateCustomText, ' .
            'covid19FoodBeverageStatus, covid19FoodBeverageReopeningDate, covid19FoodBeverageReopeningDateCustomText, ' .
            'covid19RoomServiceStatus, covid19RoomServiceReopeningDate, covid19RoomServiceReopeningDateCustomText, outlets')
        ->where(['in', 'status', ['1']])
        ->andWhere(['showOnMap' => 1])
        ->orderBy('name asc')
        ->all();

        $shops2 = [];
        for($i = 0; $i < count($shops); $i++) {
            $shops2[] = $shops[$i]->attributes;
            $shops2[$i]['outlets'] = json_decode($shops2[$i]['outlets'], true);
        }

        $this->asJson($shops2);
    }

    public function actionGetAllShopsJson() {
        $params = Craft::$app->request->getQueryParams();

        if(isset($params['state'])) {
            $shops = Shop::find()->select('name')
                ->where(['in', 'status', ['1']])
                ->andWhere(['state' => $params['state']])
                ->orderBy('name asc')
                ->all();
        } else {
            $shops = Shop::find()->select('name')
                ->where(['in', 'status', ['1']])
                ->orderBy('name asc')
                ->all();
        }

        $this->asJson(array_column($shops, 'name'));
    }

    public function actionGetAllShopsJson2() {
        $params = Craft::$app->request->getQueryParams();

        if(isset($params['state'])) {
            $shops = Shop::find()->select('name, code')
                ->where(['in', 'status', ['1']])
                ->andWhere(['state' => $params['state']])
                ->orderBy('name asc')
                ->all();
        } else {
            $shops = Shop::find()->select('name, code')
                ->where(['in', 'status', ['1']])
                ->orderBy('name asc')
                ->all();
        }

        $this->asJson($shops);
    }

    public function actionGetAllShopsJs() {
        $params = Craft::$app->request->getQueryParams();

        if(isset($params['state'])) {
            $shops = Shop::find()->select('name')
                ->where(['state' => $params['state']])
                ->orderBy('name asc')
                ->all();
        } else {
            $shops = Shop::find()->select('name')
                ->orderBy('name asc')
                ->all();
        }

        $this->renderTemplate('shops.js', ['shops' => $shops]);
    }
}
