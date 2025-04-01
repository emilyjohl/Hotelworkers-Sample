<?php

namespace modules\htcmodule\controllers;

use modules\htcmodule\HTCModule;
use modules\htcmodule\records\Form;
use modules\htcmodule\records\FormEntry;
use modules\htcmodule\records\FormEntryFile;
use modules\htcmodule\records\Person;
use modules\htcmodule\records\AuthorizationCode;
use modules\htcmodule\queue\jobs\FormEntryJob;

use putyourlightson\logtofile\LogToFile;

use Craft;
use craft\web\Controller;
use craft\helpers\Queue;

class FormEntriesController extends Controller
{
    protected $allowAnonymous = ['sign-in', 'store', 'get', 'store-files'];

	public function actionSignIn()
	{
		$this->requirePostRequest();

		$params = Craft::$app->request->post();

		$form = Form::findOne([
	    	'id' => $params['fid']
	    ]);

		if($form) {
			$form_entry = FormEntry::findOne([
				'secret_key' => $params['secret_key'],
		    	'form_id' => $params['fid'],
		    	'shop_id' => $params['sid']
		    ]);

		    if($form_entry) {
			    if($form_entry->received_at == null) {
			    	if(array_key_exists('first_name', $params)) {
			    		$form_entry->first_name_given = $params['first_name'];
				    }

			    	if(array_key_exists('last_name', $params)) {
			    		$form_entry->last_name_given = $params['last_name'];
				    }

				    $form_entry->save();
					$this->asJson([
						'secret_key' => $form_entry['secret_key']
					]);
			    } else {
					$this->asJson(['error' => 'already-received']);
				}
			} else {
				LogToFile::info("Invalid form entry sign-in code: " . json_encode($params), 'htcmodule');
				$this->asJson(['error' => 'invalid-secret-key']);
			}
		} else {
			LogToFile::info("FORM NOT FOUND: " . json_encode($params), 'htcmodule');
			$this->asJson(['error' => 'form-not-found']);
		}
	}

	public function actionStore()
	{
		$this->requirePostRequest();

		$params = Craft::$app->request->post();

		$form = Form::findOne([
	    	'id' => $params['fid']
	    ]);

	    if($form) {
	    	$formStatus = HTCModule::$instance->forms->status($form);

	    	if($formStatus['status'] != 'open') {
	    		$this->asJson($formStatus);
				return;
	    	} else if($form->authorization_code_required || ($form->id == 23 && isset($params['st']) && $params['st'] != "")) {
	    		$authorizationCode = AuthorizationCode::find()
					->where('autoExpireAt is null or autoExpireAt > NOW()')
					->andWhere([
				    	'secureToken' => $params['st'],
				    	'scope' => $params['scope'],
				    	'expiredAt' => null
				    ])
				    ->one();

				if(! $authorizationCode) {
					LogToFile::info("Invalid or expired form entry authorization code: " . json_encode($params), 'htcmodule');

					$this->asJson(['error' => 'invalid-or-expired-code-auth-code']);
					return;
				} else {
					$form_entry = new FormEntry;

					$sensitiveData = json_decode($authorizationCode->sensitiveData, true);

			    	if(array_key_exists('firstName', $sensitiveData)) {
			    		$form_entry->first_name_expected = $sensitiveData['firstName'];
				    }

			    	if(array_key_exists('lastName', $sensitiveData)) {
			    		$form_entry->last_name_expected = $sensitiveData['lastName'];
				    }

			    	if(array_key_exists('unionId', $sensitiveData)) {
			    		$form_entry->union_id = $sensitiveData['unionId'];
				    }
				}
	    	} else if($form->sign_in_required) {
				$form_entry = FormEntry::findOne([
			    	'form_id' => $params['fid'],
			    	'shop_id' => $params['sid'],
			    	'secret_key' => $params['secret_key']
			    ]);

			    if($form_entry) {
			    	if($form_entry->received_at != null) {
						$this->asJson(['error' => 'already-received']);
						return;
					}
				} else {
					LogToFile::info("Invalid form entry sign-in code: " . json_encode($params), 'htcmodule');
					$this->asJson(['error' => 'invalid-secret-key']);
				}
			} else if($form->return_fe_key && 
			isset($params['fe_key']) && 
			isset($params['h_fe_key']) &&
			md5(getenv('SECURITY_KEY') . '-' . $params['fe_key']) == $params['h_fe_key']) {
				if(isset($params['sid'])) {
					$form_entry = FormEntry::findOne([
				    	'form_id' => $params['fid'],
				    	'shop_id' => $params['sid'],
				    	'uid' => $params['fe_key'],
				    ]);
				} else {
					$form_entry = FormEntry::findOne([
				    	'form_id' => $params['fid'],
				    	'uid' => $params['fe_key']
				    ]);
				}

				if(! $form_entry) {
					$form_entry = new FormEntry;
				}
			} else {
				$form_entry = new FormEntry;
			}

			$form_entry->form_id = $form->id;
			$form_entry->fe_state = $form->initial_state;

	    	if(array_key_exists('first_name', $params)) {
	    		$form_entry->first_name_given = $params['first_name'];
		    }

	    	if(array_key_exists('last_name', $params)) {
	    		$form_entry->last_name_given = $params['last_name'];
		    }

	    	if(array_key_exists('s_fe_key', $params)) {
	    		$source_fe = FormEntry::findOne(['uid' => $params['s_fe_key']]);

	    		if($source_fe) {
	    			$form_entry->source_fe_id = $source_fe->id;
	    		}
		    }

	    	if(isset($params['fe_state'])) {
	    		$form_entry->fe_state = $params['fe_state'];
	    	}

	    	$fid = $params['fid'];

	    	$form_entry->save(false);

	    	unset($params['secret_key']);
	    	unset($params['CRAFT_CSRF_TOKEN']);
	    	unset($params['fid']);
	    	unset($params['sid']);
	    	unset($params['fe_state']);
	    	unset($params['fe_key']);
	    	unset($params['h_fe_key']);
	    	unset($params['s_fe_key']);
	    	unset($params['password']);
	    	unset($params['passwordConfirmation']);

	    	$fields = $params;

		    foreach($params as $field => $value) {
				if(preg_match('/.*(_files?|Files?)$/', $field)) {
					FormEntryFile::updateAll(
						[ 'form_entry_id' => $form_entry->id],
						[
							'and',
							['and', ['form_id' => $fid, 'form_field' => $field]],
							['in', 'uid', $value]
						]
					);

					$form_entry_files = FormEntryFile::find()->where([
						'form_id' => $fid,
						'form_field' => $field
					])->andWhere([
						'in', 'uid', $value
					])->asArray()->all();

					$fields[$field] = $form_entry_files;
				} else {
					$fields[$field] = $value;
				}
			}

	    	if(isset($authorizationCode) && $authorizationCode && isset($sensitiveData)) {
	    		if(array_key_exists('surveyId', $sensitiveData)) {
	    			$fields['surveyId'] = $sensitiveData['surveyId'];
	    		}

		    	if(array_key_exists('unionId', $sensitiveData)) {
		    		$fields['unionId'] = $sensitiveData['unionId'];
			    }

		    	if(array_key_exists('workerId', $sensitiveData)) {
		    		$fields['workerId'] = $sensitiveData['workerId'];
			    }

		    	if(array_key_exists('sourceRecPrimaryKey', $sensitiveData)) {
		    		$fields['sourceRecPrimaryKey'] = $sensitiveData['sourceRecPrimaryKey'];
			    }
		    }

			// For the contact assembly members (form 23)
	    	if($form->id == 23 && isset($params["st"]) && $params["st"] != "") {
	    		$authorizationCode = AuthorizationCode::find()
					->where('autoExpireAt is null or autoExpireAt > NOW()')
					->andWhere([
				    	'secureToken' => $params['st'],
				    	'scope' => 'contact-assembly-202103',
				    	'expiredAt' => null
				    ])
				    ->one();

				if($authorizationCode) {
					$sensitiveData = json_decode($authorizationCode->sensitiveData, true);

			    	if(array_key_exists('unionId', $sensitiveData)) {
			    		$fields['unionId'] = $sensitiveData['unionId'];
				    }

			    	if(array_key_exists('campaignPersonId', $sensitiveData)) {
			    		$fields['campaignPersonId'] = $sensitiveData['campaignPersonId'];
				    }

			    	if(array_key_exists('bargainingAgent', $sensitiveData)) {
			    		$fields['bargainingAgent'] = $sensitiveData['bargainingAgent'];
				    }

				    $authorizationCode->expiredAt = date('c');
				    $authorizationCode->save(false);
				}
		    }

			// For the membership survey (form 41)
	    	if($form->id == 41 && isset($params["st"]) && $params["st"] != "") {
	    		$authorizationCode = AuthorizationCode::find()
					->where('autoExpireAt is null or autoExpireAt > NOW()')
					->andWhere([
				    	'secureToken' => $params['st'],
				    	'scope' => 'membership-survey'
				    ])
				    ->one();

				if($authorizationCode) {
					$sensitiveData = json_decode($authorizationCode->sensitiveData, true);

			    	if(array_key_exists('unionId', $sensitiveData)) {
			    		$fields['unionId'] = $sensitiveData['unionId'];
			    		$form_entry->union_id = $sensitiveData['unionId'];
				    }

			    	if(array_key_exists('sourceRecPrimaryKey', $sensitiveData)) {
			    		$fields['sourceRecPrimaryKey'] = $sensitiveData['sourceRecPrimaryKey'];
				    }

				    $authorizationCode->expiredAt = date('c');
				    $authorizationCode->save(false);
				}
		    }

		    if($form_entry->fields_json != "") {
		    	$fields = array_merge(json_decode($form_entry->fields_json, true), $fields);
		    }

			$fields['personId'] = '';
			$fields['personUnionId'] = '';
			$fields['personUserEmail'] = '';
			$fields['personIsTest'] = '';

			$user = Craft::$app->getUser();
	    	if($user && $user->id) {
	    		$person = Person::findOne([ 'craftUserId' => $user->id ]);
    		} else if(isset($params['p'])) {
				$personId = (int) Craft::$app->security->decryptByKey(
					hex2bin($params['p']),
					getEnv('SECURITY_KEY')
				);
	    		$person = Person::findOne([ 'id' => $personId ]);
			} else {
    			$person = null;
    		}

    		if($person) {
    			$form_entry->union_id = $person->unionId;
    			$form_entry->currentPersonId = $person->id;
    			$form_entry->currentPersonUnionId = $person->unionId;
    			$form_entry->currentPersonIsTest = $person->test;
    			$fields['personId'] = $person->id;
    			$fields['personUnionId'] = $person->unionId;
    			$fields['personUserEmail'] = $user && $user->id ? $user->identity->email : null;
    			$fields['personIsTest'] = $person->test;
		    	unset($fields['p']);
    		}

	    	$form_entry->userDeviceJson = json_encode([
	    		'ips' => [
	    			'user' => Craft::$app->request->getUserIP(),
	    			'remote' => Craft::$app->request->getRemoteIp()
	    		],
	    		'referrer' => Craft::$app->request->getReferrer(),
	    		'userAgent' => Craft::$app->request->getUserAgent()
	    	]);

		    $form_entry->fields_json = json_encode($fields);
	    	$form_entry->received_at = date('Y-m-d H:i:s');

		    $form_entry->save(false);

			$form->lastFormReceivedTs = gmdate("Y-m-d H:i:s");
			$form->save(false);

			HTCModule::$instance->formEntries->postProcess($form_entry, $form, $person);

		    Queue::push(new FormEntryJob([ 'formEntryId' => $form_entry->id ]));

			if(isset($authorizationCode) && $authorizationCode) {
				$authorizationCode->expiredAt = date('c');
				$authorizationCode->save(false);
			}

			if($form->return_fe_key) {
				$this->asJson([
					'message' => 'entry-received', 
					'fe_key' => $form_entry->uid, 
					'h_fe_key' => md5(getenv('SECURITY_KEY') . '-' . $form_entry->uid) 
				]);
			} else {
				$this->asJson(['message' => 'entry-received']);
			}
		} else {
			LogToFile::info("FORM NOT FOUND: " . json_encode($params), 'htcmodule');
			$this->asJson(['error' => 'form-not-found']);
		}
	}

	public function actionGet()
	{
		$params = Craft::$app->request->get();

		$form = Form::findOne([
	    	'id' => $params['fid']
	    ]);

	    if($form && $form->return_fe_key) {
			if(isset($params['sid'])) {
				$form_entry = FormEntry::findOne([
			    	'form_id' => $params['fid'],
			    	'shop_id' => $params['sid'],
			    	'uid' => $params['fe_key']
			    ]);
			} else {
				$form_entry = FormEntry::findOne([
			    	'form_id' => $params['fid'],
			    	'uid' => $params['fe_key']
			    ]);
			}

			$fields = json_decode($form_entry->fields_json, true);
			unset($fields['fe_key']);
			$this->asJson($fields);
		}
	}

	public function actionStoreFiles() {
		$this->requirePostRequest();

		$params = Craft::$app->request->post();
		$query_params = Craft::$app->request->get();

		$form = Form::findOne([
	    	'id' => $query_params['fid']
	    ]);

	    $uids = [];

	    if($form) {
	    	/* We use a loop but there will never be more than one file */
		    foreach($_FILES as $file) {
		    	$form_entry_file = new FormEntryFile;
		    	$form_entry_file->form_id = $form->id;
		    	$form_entry_file->form_field = substr($query_params['field'], 0, -2);
		    	$form_entry_file->original_filename = $file['name'][0];
		    	$form_entry_file->size_in_bytes = $file['size'][0];
		    	$form_entry_file->mime_type = $file['type'][0];
		    	$form_entry_file->save(false);

				$dir = $form->form_type . '/' . $form_entry_file->form_field . '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . date('H') . '/';

			    if(!file_exists(CRAFT_BASE_PATH . '/form-entry-files/' . $dir)) {
			    	mkdir(CRAFT_BASE_PATH . '/form-entry-files/' . $dir, 0777, true);
			    }

				$form_entry_file->virus_scanned = 0;
				$form_entry_file->virus_found = 0;
				$form_entry_file->full_path = $dir . $form_entry_file->uid;
				$form_entry_file->save(false);

			    $this->nfs_rename($file['tmp_name'][0], CRAFT_BASE_PATH . '/form-entry-files/' . $form_entry_file->full_path);
		    	chmod(CRAFT_BASE_PATH . '/form-entry-files/' . $form_entry_file->full_path, 0777);

		    	$uids[] = $form_entry_file->uid;

		    	break; /* just in case */
		    }

			$this->asRaw($uids[0]);
		}
	}

	public function actionGetFormEntryFile()
	{
		$params = Craft::$app->request->getQueryParams();
		$user = Craft::$app->getUser();

        if($user) {
    		$person = Person::findOne(['craftUserId' => $user->id]);

        	if($person) {
				$formEntryFile = FormEntryFile::findOne([
			    	'id' => $params['id']
			    ]);

			    if($formEntryFile) {
			    	$formEntry = FormEntry::findOne([
				    	'id' => $formEntryFile->form_entry_id,
				    	'currentPersonId' => $person->id
				    ]);

				    if($formEntry) {
				    	Craft::$app->response->sendFile(
		                	CRAFT_BASE_PATH . '/form-entry-files/' . $formEntryFile->full_path,
		                	$formEntryFile->original_filename
		                );
				    }
				}
			}
		}

		$this->asRaw('File not found');
	}

	public function formatBytes($bytes, $precision = 2) {
	    $units = array('B', 'KB', 'MB', 'GB', 'TB');

	    $bytes = max($bytes, 0);
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	    $pow = min($pow, count($units) - 1);
	    $bytes /= pow(1024, $pow);

	    return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/* This is a simple replacement for rename(), since rename() throws an error on NFS mounts */
	public function nfs_rename($old, $new) {
		copy($old, $new);
		unlink($old);
	}

	public function fields_to_html($fields) {
		$html_body = "";

		foreach($fields as $field => $value) {
			if(preg_match('/.*(_files?|Files?)$/', $field)) {
				$html_body .= '<p><strong>' . $this->prettyPrintFieldName($field) . ':</strong><ol>';
				foreach($value as $form_entry_file) {
					if($form_entry_file['virus_scanned'] == '1' &&  $form_entry_file['virus_found'] == '0') {
						$html_body .= '<li>' . $form_entry_file['original_filename'] .
							' (' . $this->formatBytes($form_entry_file['size_in_bytes']) . ', ' .
							$form_entry_file['mime_type'] . ')</li>';
					} else if($form_entry_file['virus_scanned'] == '0') {
						$html_body .= '<li>' . $form_entry_file['original_filename'] .
							' (' . $this->formatBytes($form_entry_file['size_in_bytes']) . ', ' .
							$form_entry_file['mime_type'] . ')' .
							'<br/> <strong style="color:#ff8c00">Not scanned for viruses! Do NOT open ' .
							'the attachment unless you have ESET Antivirus installed on your computer ' .
							'and it has successfully scanned the attachment. ' .
							'Contact IT support for assistance.</strong></li>';
					} else {
						$html_body .= '<li>' . $form_entry_file['original_filename'] .
							' (' . $this->formatBytes($form_entry_file['size_in_bytes']) . ', ' .
							$form_entry_file['mime_type'] . ')' .
							'<br/> <strong style="color:#FF0007">Virus found! The file was not attached. '.
							'Contact IT support for assistance.</strong></li>';
					}
				}
				$html_body .= '</ol>';
			} else if(is_array($value)) {
				/* Don't show zero-based array index */
				if(is_numeric($field)) {
					$field += 1;
				}

				$html_body .= '<p><strong>' . $this->prettyPrintFieldName($field) . ':</strong></p><div style="padding-left:20px">' . $this->fields_to_html($value) . '</div>';
			} else {
				$html_body .= '<p><strong>' . $this->prettyPrintFieldName($field) . ':</strong><br/>' . $value . '</p>';
			}
		}

		return $html_body;
	}

	public function prettyPrintFieldName($fieldName) {
	    return ucwords(str_replace("_", " ", preg_replace(['/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/'], ' $0', $fieldName)));
	}
}
