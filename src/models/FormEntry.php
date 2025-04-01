<?php

namespace modules\htcmodule\models;

use modules\htcmodule\HTCModule;

use Craft;
use craft\base\Model;

class FormEntry extends Model
{
    public $member_id = '';
    public $form_id = '';
    public $shop_id = '';
    public $first_name_expected = '';
    public $last_name_expected = '';
    public $first_name_given = '';
    public $last_name_given = '';
    public $secret_key = '';
    public $fields_json = '';
    public $received_at = '';
    public $forwarded_to = '';
    public $forward_template = "";
    public $source_fe_id = '';
    public $fe_state = '';
    public $return_fe_key = '';
    public $userDevice = '';
}
