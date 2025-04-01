<?php

namespace modules\htcmodule\models;

use modules\htcmodule\HTCModule;

use Craft;
use craft\base\Model;

class Form extends Model
{
	public $name = "";
	public $form_type = "";
	public $sign_in_required = "";
	public $forward_to = "";
	public $return_fe_key = "";
	public $initial_state = "";
}
