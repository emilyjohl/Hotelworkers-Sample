<?php
/**
 * HTC module for Craft CMS 3.x
 *
 * Hotel Trades Council web hooks, forms, and ratifications, and the place where any of our custom functionality will go.
 *
 * @link      https://hotelworkers.org
 * @copyright Copyright (c) 2020 Joe Ridgway
 */

namespace modules\htcmodule;

use modules\htcmodule\assetbundles\htcmodule\HTCModuleAsset;
use modules\htcmodule\services\Payments as PaymentsService;
use modules\htcmodule\services\FileMaker as FileMakerService;
use modules\htcmodule\twigextensions\HTCModuleTwigExtension;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\TemplateEvent;
use craft\i18n\PhpMessageSource;
use craft\web\View;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\elements\User;
use modules\htcmodule\records\Person;
// use modules\htcmodule\controllers\CurrentUserController;


use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\web\Cookie;

use putyourlightson\logtofile\LogToFile;

/**
 * Class HTCModule
 *
 * @author    Joe Ridgway
 * @package   HTCModule
 * @since     1.0.0
 *
 */
class HTCModule extends Module
{
    // Static Properties
    // =========================================================================

    /**
     * @var HTCModule
     */
    public static $instance;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        Craft::setAlias('@modules/htcmodule', $this->getBasePath());
        $this->controllerNamespace = 'modules\htcmodule\controllers';

        // Translation category
        $i18n = Craft::$app->getI18n();
        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (!isset($i18n->translations[$id]) && !isset($i18n->translations[$id.'*'])) {
            $i18n->translations[$id] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => 'en-US',
                'basePath' => '@modules/htcmodule/translations',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ];
        }

        // Base template directory
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
            if (is_dir($baseDir = $this->getBasePath().DIRECTORY_SEPARATOR.'templates')) {
                $e->roots[$this->id] = $baseDir;
            }
        });

        // Set this as the global instance of this module class
        static::setInstance($this);

        parent::__construct($id, $parent, $config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$instance = $this;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'modules\htcmodule\console\controllers';
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                function (TemplateEvent $event) {
                    try {
                        Craft::$app->getView()->registerAssetBundle(HTCModuleAsset::class);
                    } catch (InvalidConfigException $e) {
                        Craft::error(
                            'Error registering AssetBundle - '.$e->getMessage(),
                            __METHOD__
                        );
                    }
                }
            );
        }

        Craft::$app->view->registerTwigExtension(new HTCModuleTwigExtension());

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['forms/status'] = 'htcmodule/forms/status';

                $event->rules['form-entries/sign-in'] = 'htcmodule/form-entries/sign-in';
                $event->rules['form-entries/store'] = 'htcmodule/form-entries/store';
                $event->rules['form-entries/get'] = 'htcmodule/form-entries/get';
                $event->rules['form-entries/store-files'] = 'htcmodule/form-entries/store-files';

                $event->rules['w/<slug:.*>'] = 'htcmodule/webhook-responses/store';

                $event->rules['ratifications/sign-in'] = 'htcmodule/ratifications/sign-in';
                $event->rules['ratifications/cast-ballot'] = 'htcmodule/ratifications/cast-ballot';

                $event->rules['developer-test/shops/491/ratifications/1252/status'] = 'htcmodule/ratifications-test/status';
                $event->rules['developer-test/shops/491/ratifications/1252/sign-in'] = 'htcmodule/ratifications-test/sign-in';
                $event->rules['developer-test/shops/491/ratifications/1252/cast-ballot'] = 'htcmodule/ratifications-test/cast-ballot';

                $event->rules['authorize'] = 'htcmodule/authorization-codes/authorize';
                $event->rules['authorize-token'] = 'htcmodule/authorization-codes/authorize-secure-token';

                $event->rules['guest-payments/process'] = 'htcmodule/guest-payments/process';

                $event->rules['account-claims/create'] = 'htcmodule/account-claims/create-account-claim';
                $event->rules['account-claims/validate-security-questions'] = 'htcmodule/account-claims/validate-security-questions';

                $event->rules['accounts/create'] = 'htcmodule/accounts/create';
                $event->rules['accounts/verify-email'] = 'htcmodule/accounts/verify-email';
                $event->rules['accounts/verify-email-auth-code'] = 'htcmodule/accounts/verify-email-by-authorization-code';
                $event->rules['accounts/send-email-verification'] = 'htcmodule/accounts/send-email-verification';
                $event->rules['accounts/send-two-factor-verification-code'] = 'htcmodule/accounts/send-two-factor-verification-code';
                $event->rules['accounts/validate-two-factor-verification-code'] = 'htcmodule/accounts/validate-two-factor-verification-code';
                $event->rules['accounts/send-password-reset'] = 'htcmodule/accounts/send-password-reset';
                $event->rules['accounts/set-password'] = 'htcmodule/accounts/set-password';
                $event->rules['accounts/verify-identity-from-authorization'] = 'htcmodule/accounts/verify-identity-from-authorization';
                $event->rules['accounts/authorize-verification-bypass'] = 'htcmodule/accounts/authorize-verification-bypass';
                $event->rules['accounts/update-from-authorization'] = 'htcmodule/accounts/update-from-authorization';

                $event->rules['accounts/payments/process'] = 'htcmodule/payments/process';

                $event->rules['accounts/payment-methods/create'] = 'htcmodule/payment-methods/create';
                $event->rules['accounts/payment-methods/delete'] = 'htcmodule/payment-methods/delete';

                $event->rules['accounts/recurring-payments/create'] = 'htcmodule/recurring-payments/create';
                $event->rules['accounts/recurring-payments/update'] = 'htcmodule/recurring-payments/update';
                $event->rules['accounts/recurring-payments/cancel'] = 'htcmodule/recurring-payments/cancel';

                $event->rules['accounts/form-entry-files/get-file'] = 'htcmodule/form-entries/get-form-entry-file';

                $event->rules['accounts/dashboard/get-<section>-data.json'] = 'htcmodule/dashboard/get-data';
                $event->rules['accounts/2023-senate-petition-results.csv'] = 'htcmodule/dashboard/get-petition-responses';
                $event->rules['accounts/2023-childcare-results.csv'] = 'htcmodule/dashboard/get-childcare-responses';

                $event->rules['accounts/profile/save-profile'] = 'htcmodule/profile/save-profile';
                $event->rules['accounts/profile/save-profile-picture'] = 'htcmodule/profile/save-profile-picture';
                $event->rules['accounts/profile/delete-profile-picture'] = 'htcmodule/profile/delete-profile-picture';

                $event->rules['accounts/comments/create-comment'] = 'htcmodule/comments/create-comment';
                $event->rules['accounts/comments/mark-thread-as-read'] = 'htcmodule/comments/mark-thread-as-read';

                $event->rules['accounts/attachments/store'] = 'htcmodule/attachments/store-attachment';
                $event->rules['accounts/attachments/get'] = 'htcmodule/attachments/get-attachment';

                $event->rules['hire/index.json'] = 'htcmodule/hire/index';
                $event->rules['hire/member-data.json'] = 'htcmodule/hire/member-data';
                $event->rules['hire/job-openings/actions/create'] = 'htcmodule/hire/create-action';
                $event->rules['hire/referrals/apply'] = 'htcmodule/hire/apply-to-interview';
                $event->rules['hire/referrals/cancel'] = 'htcmodule/hire/cancel-referral';
                $event->rules['hire/save-heo-intake-form'] = 'htcmodule/hire/save-heo-intake-form';

                $event->rules['events/index.json'] = 'htcmodule/events/index';

                $event->rules['non-union-survey/get-data.json'] = 'htcmodule/non-union-survey/get-non-union-survey-data';

                $event->rules['classifications.js'] = 'htcmodule/classifications/index3';
                $event->rules['classifications/index.json'] = 'htcmodule/classifications/index';
                $event->rules['classifications/index2.json'] = 'htcmodule/classifications/index2';
                $event->rules['classifications/by-groupings.<format>'] = 'htcmodule/classifications/by-groupings';
                $event->rules['languages/index.json'] = 'htcmodule/languages/index';
                $event->rules['languages/index2.json'] = 'htcmodule/languages/index2';
                $event->rules['languages.js'] = 'htcmodule/languages/index3';
                $event->rules['book-union.json'] = 'htcmodule/shops/get-book-union-shops';
                $event->rules['shops.js'] = 'htcmodule/shops/get-all-shops-js';
                $event->rules['shops.json'] = 'htcmodule/shops/get-all-shops-json';
                $event->rules['shops2.json'] = 'htcmodule/shops/get-all-shops-json2';

                $event->rules['s/nc/unconfirmed-domain/form'] = 'htcmodule/management-domains/form';
                $event->rules['s/nc/unconfirmed-domain/confirm'] = 'htcmodule/management-domains/confirm';

                $event->rules['comments/create-comment'] = 'htcmodule/comments/create-comment';
                $event->rules['comments/mark-thread-as-read'] = 'htcmodule/nu-workers/mark-thread-as-read';

                $event->rules['profile/save-profile'] = 'htcmodule/profile/save-profile';
                $event->rules['profile/save-profile-picture'] = 'htcmodule/profile/save-profile-picture';
                $event->rules['profile/delete-profile-picture'] = 'htcmodule/profile/delete-profile-picture';

                $event->rules['current-user.js'] = 'htcmodule/current-user/current-user-js';
                $event->rules['current-user-2.js'] = 'htcmodule/current-user/current-user-2-js';
                $event->rules['current-user.json'] = 'htcmodule/current-user/current-user-json';

                /*** API ***/

                $event->rules['api/authorization-codes/create'] = 'htcmodule/api/create-authorization-code';
                $event->rules['api/authorization-codes/update'] = 'htcmodule/api/update-authorization-code';

                $event->rules['api/payments'] = 'htcmodule/api/payments-index';
                $event->rules['api/payments/setup-one-time-payment'] = 'htcmodule/api/setup-one-time-payment';
                $event->rules['api/payments/not-synced-to-fm'] = 'htcmodule/api/payments-not-synced-to-fm';
                $event->rules['api/payments/mark-synced-to-fm'] = 'htcmodule/api/mark-payments-synced-to-fm';

                $event->rules['api/webhook-responses/not-synced-to-fm'] = 'htcmodule/api/webhook-responses-not-synced-to-fm';
                $event->rules['api/webhook-responses/mark-synced-to-fm'] = 'htcmodule/api/mark-webhook-responses-synced-to-fm';
                $event->rules['api/webhook-responses/get'] = 'htcmodule/api/get-webhook-response';
                $event->rules['api/webhooks/get'] = 'htcmodule/api/get-webhook';

                $event->rules['api/persons/create'] = 'htcmodule/api/create-person';
                $event->rules['api/persons/get'] = 'htcmodule/api/get-person';
                $event->rules['api/persons/update'] = 'htcmodule/api/update-person';
                $event->rules['api/persons/delete'] = 'htcmodule/api/delete-person';
                $event->rules['api/persons'] = 'htcmodule/api/persons-index';
                $event->rules['api/persons/not-synced-to-fm'] = 'htcmodule/api/persons-not-synced-to-fm';
                $event->rules['api/persons/mark-synced-to-fm'] = 'htcmodule/api/mark-persons-synced-to-fm';

                $event->rules['api/person-datums/create'] = 'htcmodule/api/create-person-datum';
                $event->rules['api/person-datums/update'] = 'htcmodule/api/update-person-datum';
                $event->rules['api/person-datums/delete'] = 'htcmodule/api/delete-person-datum';
                $event->rules['api/person-datums/get'] = 'htcmodule/api/get-person-datum';
                $event->rules['api/person-datums'] = 'htcmodule/api/person-datums-index';

                $event->rules['api/job-queue'] = 'htcmodule/api/job-queue-index';
                $event->rules['api/job-queue/retry-all'] = 'htcmodule/api/job-queue-retry-all';

                $event->rules['api/events'] = 'htcmodule/api/events-index';
                $event->rules['api/events/create'] = 'htcmodule/api/create-event';
                $event->rules['api/events/update'] = 'htcmodule/api/update-event';
                $event->rules['api/events/get'] = 'htcmodule/api/get-event';
                $event->rules['api/events/delete'] = 'htcmodule/api/delete-event';

                $event->rules['api/event-types'] = 'htcmodule/api/event-types-index';
                $event->rules['api/event-types/create'] = 'htcmodule/api/create-event-type';
                $event->rules['api/event-types/update'] = 'htcmodule/api/update-event-type';
                $event->rules['api/event-types/get'] = 'htcmodule/api/get-event-type';
                $event->rules['api/event-types/delete'] = 'htcmodule/api/delete-event-type';

                $event->rules['api/hire-job-openings'] = 'htcmodule/api/hire-job-openings-index';
                $event->rules['api/hire-job-openings/create'] = 'htcmodule/api/create-hire-job-opening';
                $event->rules['api/hire-job-openings/update'] = 'htcmodule/api/update-hire-job-opening';
                $event->rules['api/hire-job-openings/get'] = 'htcmodule/api/get-hire-job-opening';
                $event->rules['api/hire-job-openings/delete'] = 'htcmodule/api/delete-hire-job-opening';

                $event->rules['api/hire-job-member-actions'] = 'htcmodule/api/hire-job-member-actions-index';
                $event->rules['api/hire-job-member-actions/create'] = 'htcmodule/api/create-hire-job-member-action';
                $event->rules['api/hire-job-member-actions/update'] = 'htcmodule/api/update-hire-job-member-action';
                $event->rules['api/hire-job-member-actions/get'] = 'htcmodule/api/get-hire-job-member-action';
                $event->rules['api/hire-job-member-actions/delete'] = 'htcmodule/api/delete-hire-job-member-action';
                $event->rules['api/hire-job-member-actions/not-synced-to-fm'] = 'htcmodule/api/hire-job-member-actions-not-synced-to-fm';
                $event->rules['api/hire-job-member-actions/mark-synced-to-fm'] = 'htcmodule/api/mark-hire-job-member-actions-synced-to-fm';

                $event->rules['api/hire-interview-restrictions'] = 'htcmodule/api/hire-interview-restrictions-index';
                $event->rules['api/hire-interview-restrictions/create'] = 'htcmodule/api/create-hire-interview-restriction';
                $event->rules['api/hire-interview-restrictions/update'] = 'htcmodule/api/update-hire-interview-restriction';
                $event->rules['api/hire-interview-restrictions/get'] = 'htcmodule/api/get-hire-interview-restriction';
                $event->rules['api/hire-interview-restrictions/delete'] = 'htcmodule/api/delete-hire-interview-restriction';

                $event->rules['api/management-domains'] = 'htcmodule/api/management-domains-index';
                $event->rules['api/management-domains/create'] = 'htcmodule/api/create-management-domain';
                $event->rules['api/management-domains/batch-create'] = 'htcmodule/api/batch-create-management-domains';
                $event->rules['api/management-domains/update'] = 'htcmodule/api/update-management-domain';
                $event->rules['api/management-domains/get'] = 'htcmodule/api/get-management-domain';
                $event->rules['api/management-domains/delete'] = 'htcmodule/api/delete-management-domain';

                $event->rules['api/forms'] = 'htcmodule/api/forms-index';
                $event->rules['api/forms/create'] = 'htcmodule/api/create-form';
                $event->rules['api/forms/update'] = 'htcmodule/api/update-form';
                $event->rules['api/forms/get'] = 'htcmodule/api/get-form';
                $event->rules['api/forms/delete'] = 'htcmodule/api/delete-form';

                $event->rules['api/form-entries'] = 'htcmodule/api/form-entries-index';
                $event->rules['api/form-entries/create'] = 'htcmodule/api/create-form-entry';
                $event->rules['api/form-entries/update'] = 'htcmodule/api/update-form-entry';
                $event->rules['api/form-entries/get'] = 'htcmodule/api/get-form-entry';
                $event->rules['api/form-entries/delete'] = 'htcmodule/api/delete-form-entry';
                $event->rules['api/form-entries/not-synced-to-fm'] = 'htcmodule/api/form-entries-not-synced-to-fm';
                $event->rules['api/form-entries/mark-synced-to-fm'] = 'htcmodule/api/mark-form-entries-synced-to-fm';

                $event->rules['api/form-entry-files'] = 'htcmodule/api/form-entry-files-index';
                $event->rules['api/form-entry-files/create'] = 'htcmodule/api/create-form-entry-file';
                $event->rules['api/form-entry-files/update'] = 'htcmodule/api/update-form-entry-file';
                $event->rules['api/form-entry-files/get'] = 'htcmodule/api/get-form-entry-file';
                $event->rules['api/form-entry-files/get-file'] = 'htcmodule/api/get-form-entry-file-get-file';
                $event->rules['api/form-entry-files/delete'] = 'htcmodule/api/delete-form-entry-file';
                $event->rules['api/form-entry-files/not-synced-to-fm'] = 'htcmodule/api/form-entry-files-not-synced-to-fm';
                $event->rules['api/form-entry-files/mark-synced-to-fm'] = 'htcmodule/api/mark-form-entry-files-synced-to-fm';

                $event->rules['api/shops/create'] = 'htcmodule/api/create-shop';
                $event->rules['api/shops/delete'] = 'htcmodule/api/delete-shop';
                $event->rules['api/shops'] = 'htcmodule/api/shops-index';

                $event->rules['api/account-overview'] = 'htcmodule/api/account-overview';
                $event->rules['api/members-site-statistics'] = 'htcmodule/api/members-site-statistics';

                $event->rules['api/languages'] = 'htcmodule/api/languages-index';
                $event->rules['api/languages/create'] = 'htcmodule/api/create-language';
                $event->rules['api/languages/update'] = 'htcmodule/api/update-language';
                $event->rules['api/languages/get'] = 'htcmodule/api/get-language';
                $event->rules['api/languages/delete'] = 'htcmodule/api/delete-language';

                $event->rules['api/classifications'] = 'htcmodule/api/classifications-index';
                $event->rules['api/classifications/create'] = 'htcmodule/api/create-classification';
                $event->rules['api/classifications/update'] = 'htcmodule/api/update-classification';
                $event->rules['api/classifications/get'] = 'htcmodule/api/get-classification';
                $event->rules['api/classifications/delete'] = 'htcmodule/api/delete-classification';

                $event->rules['api/classification-groupings'] = 'htcmodule/api/classification-groupings-index';
                $event->rules['api/classification-groupings/create'] = 'htcmodule/api/create-classification-grouping';
                $event->rules['api/classification-groupings/update'] = 'htcmodule/api/update-classification-grouping';
                $event->rules['api/classification-groupings/get'] = 'htcmodule/api/get-classification-grouping';
                $event->rules['api/classification-groupings/delete'] = 'htcmodule/api/delete-classification-grouping';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'htcmodule/test/do-something';
            }
        );

        // COMMENTED OUT FOR TESTING, WILL HAVE TO ADJUST THIS COOKIE AFTER VERIFICATION!!
        Event::on(\yii\web\User::class,  \yii\web\User::EVENT_AFTER_LOGIN, function ($event) {
            $user = Craft::$app->getUser();
            $person = Person::findOne(['craftUserId' => $user->id]);
            $person->verificationLevel = 'noMfa';
            $person->save(false);
            
      
        });

        Event::on(\yii\web\User::class,  \yii\web\User::EVENT_AFTER_LOGOUT, function ($event) {
            HTCModule::$instance->accounts->unsetVCacheCookie();
        });

        // Craft::info(
        //     Craft::t(
        //         'HTCModule',
        //         '{name} module loaded',
        //         ['name' => 'htc']
        //     ),
        //     __METHOD__
        // );
    }

    // Protected Methods
    // =========================================================================
}
