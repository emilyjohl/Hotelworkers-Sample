<?php

namespace modules\htcmodule\controllers;

use modules\htcmodule\HTCModule;
use modules\htcmodule\records\Form;
use modules\htcmodule\records\FormEntry;

use putyourlightson\logtofile\LogToFile;

use Craft;
use craft\web\Controller;
use craft\helpers\Queue;

class FormsController extends Controller
{
    protected $allowAnonymous = ['status'];

	public function actionStatus()
	{
		$params = Craft::$app->request->get();

		$form = Form::findOne([
	    	'id' => $params['id']
	    ]);

	    $status = HTCModule::$instance->forms->status($form);

	    $this->asJson($status);
	}
}
