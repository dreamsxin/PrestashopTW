<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    eKomi
 *  @copyright 2017 eKomi
 *  @license   LICENSE.txt
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__) . '/models/ReviewsModel.php');

class EkomiRatingsAndReviews extends Module
{

    /**
     * @var float
     */
    private $prestashopVersion;

    /**
     * @var bool
     */
    public $bootstrap;

    /**
     * @var string
     */
    public $confirmUninstall;

    /**
     * EKOMI constructor.
     */
    public function __construct()
    {
        $this->name = 'ekomiratingsandreviews';
        $this->tab = 'front_office_features';
        $this->version = '1.3.0';
        $this->author = 'eKomi';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->prestashopVersion = (float) _PS_VERSION_;

        parent::__construct();
        $this->displayName = $this->l('eKomi Ratings and Reviews');
        $this->description = $this->l('eKomi Ratings and Reviews allows to read the necessary order details 
        automatically from your shop system database which will enable eKomi to send a review request to your client, 
        determine which order status should trigger the review request, contact your clients via email or SMS, request 
        both seller and product reviews from your clients, display product reviews and ratings automatically on the 
        corresponding product pages through our Product Review Container (PRC). If you have any questions regarding the 
        plugin, please get in touch! Email us at support@ekomi.de, call us on +1 844-356-6487, or fill out our contact 
        form.');
        $this->confirmUninstall = $this->l(
            'Are you sure you want to uninstall?'
        );
        $this->module_key = '2ee1e1fac6f2f5872901416f2fc6501f';
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !ReviewsModel::createTable() ||
            !$this->installConfigurations() &&
            !$this->registerHook('ekomiReviewsCount') ||
            !$this->registerHook('ekomiReviewsContainer') ||
            !$this->registerHook('header') ||
            !$this->registerHook('ekomiReviewStars') ||
            !$this->registerHook('actionOrderStatusPostUpdate')) {
            return false;
        }

        /* Prestashop 1.5 specific hook for displaying mini stars below product name. */
        if ($this->prestashopVersion === 1.5) {
            if (!$this->registerHook('displayProductButtons')) {
                return false;
            }
        }

        return true;
    }

    protected function installConfigurations()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $this->installConfiguration((int)$lang['id_lang']);
        }

        Configuration::updateValue('EKOMI_RNR_ENABLE', '0');
        Configuration::updateValue('EKOMI_RNR_GROUP_REVIEWS', '0');
        Configuration::updateValue('EKOMI_RNR_ORDER_STATUS', '');
        Configuration::updateValue('EKOMI_RNR_PRODUCT_REVIEWS', '0');
        Configuration::updateValue('EKOMI_RNR_MODE', '0');

        return true;
    }

    protected function installConfiguration($id_lang)
    {
        $values = array();

        $values['EKOMI_RNR_SHOP_ID'][(int)$id_lang]         = '';
        $values['EKOMI_RNR_SHOP_PASSWORD'][(int)$id_lang]   = '';
        $values['EKOMI_RNR_NO_REVIEW_MSG'][(int)$id_lang]   = 'So far no reviews received for this Product';

        Configuration::updateValue('EKOMI_RNR_SHOP_ID', $values['EKOMI_RNR_SHOP_ID']);
        Configuration::updateValue('EKOMI_RNR_SHOP_PASSWORD', $values['EKOMI_RNR_SHOP_PASSWORD']);
        Configuration::updateValue('EKOMI_RNR_NO_REVIEW_MSG', $values['EKOMI_RNR_NO_REVIEW_MSG']);
    }

    /**
     * @param $params
     */
    //public function hookActionValidateOrder($params)
    public function hookActionOrderStatusPostUpdate($params)
    {
        $configValues = $this->getConfigValues();

        $orderStatusesArray = $configValues['EKOMI_RNR_ORDER_STATUS[]'];
        if (in_array($params['newOrderStatus']->id, $orderStatusesArray)) {
            $order = new Order((int) $params['id_order']);
            if (Validate::isLoadedObject($order)) {
                if ($this->isActivated()) {
                    $fields = $this->getRequiredFields($configValues, $order);
                    $this->sendPostVars($fields);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !ReviewsModel::dropTable() ||
            !Configuration::deleteByName('EKOMI_RNR_ENABLE') ||
            !Configuration::deleteByName('EKOMI_RNR_SHOP_ID') ||
            !Configuration::deleteByName('EKOMI_RNR_SHOP_PASSWORD') ||
            !Configuration::deleteByName('EKOMI_RNR_ORDER_STATUS') ||
            !Configuration::deleteByName('EKOMI_RNR_PRODUCT_REVIEWS') ||
            !Configuration::deleteByName('EKOMI_RNR_MODE') ||
            !Configuration::deleteByName('EKOMI_RNR_GROUP_REVIEWS') ||
            !Configuration::deleteByName('EKOMI_RNR_NO_REVIEW_MSG')
        ) {
            return false;
        }

        return true;
    }

    /**
     * This function is executed when displaying the contact form.
     * It can also be used to catch the form submissions. You can
     * retrieve values of the submitted form here.
     * @return string
     */
    public function getContent()
    {
        $this->context->smarty->assign(
            array(
                'rnr_module_path' => $this->_path
            )
        );

        $this->context->controller->addCSS($this->_path .'views/css/config.css');

        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $configValues = $this->getPostValues();
            $response = $this->verifyAccounts($configValues);

            if ($response['success']) {
                $this->updateValues($configValues);
                $output .= $this->displayConfirmation(
                    $this->l($response['message'])
                );

                $result = ReviewsModel::populateTable('all');

                if ($result['status'] === 'success') {
                    $this->smarty->assign(array(
                        "starsAvg" => 11,
                        "productName" => 'aaa',
                        "reviewsCount" => 333,
                    ));

                    $languages = Language::getLanguages(false);
                    foreach ($languages as $lang) {
                        if ($result[$lang['id_lang']]['status'] === 'success') {
                            $output .= $this->displayConfirmation(
                                $this->l($result[$lang['id_lang']]['message'])
                            );
                        } else {
                            $output .= $this->displayError(
                                $this->l($result[$lang['id_lang']]['message'])
                            );
                        }
                    }
                    $output .= $this->display(__FILE__, 'views/templates/admin/notification_text.tpl');
                } else {
                    $output .= $this->displayError($this->l($result['message']));
                }
            } else {
                $output .= $this->displayError($this->l($response['message']));
                Configuration::updateValue('EKOMI_RNR_ENABLE', 0);
            }
            return $output.$this->displayForm().$this->interactiveScreenTop();
        } else {
            if ($this->isActivated()) {
                return $output.$this->displayForm().$this->interactiveScreenTop();
            } else {
                return $output.$this->displayForm().$this->interactiveScreenTop() .$this->interactiveScreenBottom();
            }
        }
    }

    /**
     * @return string
     */
    public function interactiveScreenTop()
    {
        return $this->display(__FILE__, 'views/templates/admin/interactive_screen_top.tpl');
    }

    /**
     * @return string
     */
    public function interactiveScreenBottom()
    {
        $this->context->controller->addJS($this->_path .'views/js/config.js');

        $this->context->smarty->assign(
            array(
                'rnr_booking_url' =>$this->getBookingURL()
            )
        );
        return $this->display(__FILE__, 'views/templates/admin/interactive_screen_bottom.tpl');
    }

    /**
     * @return string
     */
    private function getBookingURL()
    {
        $rnr_iso_code = $this->context->language->iso_code;

        $rnr_booking_url='https://ekomi.youcanbook.me/';

        switch ($rnr_iso_code) {
            case "es":
                $rnr_booking_url='https://ekomies.youcanbook.me/';
                break;
            case "pt":
                $rnr_booking_url='https://ekomies.youcanbook.me/';
                break;
            case "fr":
                $rnr_booking_url='https://ekomifr.youcanbook.me/';
                break;
            case "it":
                $rnr_booking_url='https://ekomiit.youcanbook.me/';
                break;
            case "de":
                $rnr_booking_url='https://ekomide.youcanbook.me/';
                break;
            default:
                $rnr_booking_url='https://ekomi.youcanbook.me/';
        }

        return $rnr_booking_url;
    }

    /**
     * @return mixed
     */
    public function displayForm()
    {
        // Init Fields form array
        $fields_form = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Settings'),
                    ),
                    'input' => $this->getInputFields(),
                    'submit' => array(
                        'title' => $this->l('Save & Import Reviews'),
                        'class' => 'button'
                    )
                ),
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        // Language
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = 0;

        if (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')) {
            $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        }

        $helper->identifier = $this->identifier;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex .
                        '&configure=' . $this->name .
                        '&save' . $this->name .
                        '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        return $helper->generateForm($fields_form);
    }

    /**
     * @return array
     */
    public function getPostValues()
    {
        $languages = Language::getLanguages(false);
        $fields = array();

        foreach ($languages as $lang) {
            $fields['EKOMI_RNR_SHOP_ID'][$lang['id_lang']] = Tools::getValue('EKOMI_RNR_SHOP_ID_'.$lang['id_lang']);
            $fields['EKOMI_RNR_SHOP_PASSWORD'][$lang['id_lang']] = Tools::getValue('EKOMI_RNR_SHOP_PASSWORD_'.$lang['id_lang']);
            $fields['EKOMI_RNR_NO_REVIEW_MSG'][$lang['id_lang']] = Tools::getValue('EKOMI_RNR_NO_REVIEW_MSG_'.$lang['id_lang']);
        }

        $fields['EKOMI_RNR_ENABLE'] = Tools::getValue('EKOMI_RNR_ENABLE');
        $fields['EKOMI_RNR_GROUP_REVIEWS'] = Tools::getValue('EKOMI_RNR_GROUP_REVIEWS');
        $fields['EKOMI_RNR_PRODUCT_REVIEWS'] = Tools::getValue('EKOMI_RNR_PRODUCT_REVIEWS');
        $fields['EKOMI_RNR_MODE'] = Tools::getValue('EKOMI_RNR_MODE');
        $fields['EKOMI_RNR_ORDER_STATUS'] = Tools::getValue('EKOMI_RNR_ORDER_STATUS');

        return $fields;
    }

    /**
     * @return array
     */
    public static function getConfigValues()
    {
        $languages = Language::getLanguages(false);
        $fields = array();

        foreach ($languages as $lang) {
            $fields['EKOMI_RNR_SHOP_ID'][$lang['id_lang']] = Tools::getValue(
                'EKOMI_RNR_SHOP_ID_'.$lang['id_lang'],
                Configuration::get('EKOMI_RNR_SHOP_ID', $lang['id_lang'])
            );
            $fields['EKOMI_RNR_SHOP_PASSWORD'][$lang['id_lang']] = Tools::getValue(
                'EKOMI_RNR_SHOP_PASSWORD_'.$lang['id_lang'],
                Configuration::get('EKOMI_RNR_SHOP_PASSWORD', $lang['id_lang'])
            );
            $fields['EKOMI_RNR_NO_REVIEW_MSG'][$lang['id_lang']] = Tools::getValue(
                'EKOMI_RNR_NO_REVIEW_MSG_'.$lang['id_lang'],
                Configuration::get('EKOMI_RNR_NO_REVIEW_MSG', $lang['id_lang'])
            );
        }

        $states = Configuration::get('EKOMI_RNR_ORDER_STATUS');
        $fields['EKOMI_RNR_ORDER_STATUS[]'] = explode(',', $states);
        $fields['EKOMI_RNR_ENABLE'] = Configuration::get('EKOMI_RNR_ENABLE');
        $fields['EKOMI_RNR_PRODUCT_REVIEWS'] = Configuration::get('EKOMI_RNR_PRODUCT_REVIEWS');
        $fields['EKOMI_RNR_MODE'] = Configuration::get('EKOMI_RNR_MODE');
        $fields['EKOMI_RNR_GROUP_REVIEWS'] = Configuration::get('EKOMI_RNR_GROUP_REVIEWS');

        return $fields;
    }

    /**
     * @param $langId
     *
     * @return string
     */
    public function getShopIdByLang($langId)
    {
        return Configuration::get('EKOMI_RNR_SHOP_ID', $langId);
    }

    /**
     * @param $configValues
     */
    public function updateValues($configValues)
    {
        if (is_array($configValues['EKOMI_RNR_ORDER_STATUS'])) {
            $orderStatuses = implode(',', $configValues['EKOMI_RNR_ORDER_STATUS']);
        } else {
            $orderStatuses = $configValues['EKOMI_RNR_ORDER_STATUS'];
        }

        Configuration::updateValue('EKOMI_RNR_ORDER_STATUS', $orderStatuses);
        Configuration::updateValue('EKOMI_RNR_ENABLE', $configValues['EKOMI_RNR_ENABLE']);
        Configuration::updateValue('EKOMI_RNR_GROUP_REVIEWS', $configValues['EKOMI_RNR_GROUP_REVIEWS']);
        Configuration::updateValue('EKOMI_RNR_NO_REVIEW_MSG', $configValues['EKOMI_RNR_NO_REVIEW_MSG']);
        Configuration::updateValue('EKOMI_RNR_SHOP_ID', str_replace(' ', '', $configValues['EKOMI_RNR_SHOP_ID']));
        Configuration::updateValue('EKOMI_RNR_SHOP_PASSWORD', str_replace(' ', '', $configValues['EKOMI_RNR_SHOP_PASSWORD']));
        Configuration::updateValue('EKOMI_RNR_PRODUCT_REVIEWS', $configValues['EKOMI_RNR_PRODUCT_REVIEWS']);
        Configuration::updateValue('EKOMI_RNR_MODE', $configValues['EKOMI_RNR_MODE']);
    }

    /**
     * @return string
     */
    public static function isActivated()
    {
        return Configuration::get('EKOMI_RNR_ENABLE');
    }

    /**
     * @return array
     */
    public function verifyAccounts($configValues)
    {
        $languages = Language::getLanguages(false);
        $shopCount = 0;
        $response = array();

        foreach ($languages as $lang) {
            $shopId = $configValues['EKOMI_RNR_SHOP_ID'][$lang['id_lang']];
            $shopPw = $configValues['EKOMI_RNR_SHOP_PASSWORD'][$lang['id_lang']];

            if (!empty($shopId) && !empty($shopPw)) {
                $shopCount++;
                $result = $this->verifyAccount($shopId, $shopPw);

                if (!$result['success']) {
                    $response = array(
                        'success' => false,
                        'message' => 'Access denied for ' . $lang['name'] . ' shop'
                    );

                    return $response;
                } else {
                    $response = array(
                        'success' => true,
                        'message' => $this->l('Settings Updated!')
                    );
                }
            }
        }

        if ($shopCount == 0) {
            $response = array(
                'success' => false,
                'message' => $this->l('Shop ID and Password Required.')
            );
        }

        return $response;
    }

    /**
     * @param $ShopId
     * @param $ShopPassword
     *
     * @return mixed
     */
    public static function verifyAccount($ShopId, $ShopPassword)
    {
        $response = array(
            'success' => true
        );

        if (!empty($ShopId) && !empty($ShopPassword)) {
            $ApiUrl = 'http://api.ekomi.de/v3/getSettings';
            $ApiUrl .= "?auth=" . $ShopId . "|" . $ShopPassword;
            $ApiUrl .= "&version=cust-1.0.0&type=request&charset=iso";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $ApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch);

            if ($server_output == 'Access denied') {
                $response = array(
                    'success' => false,
                    'message' => $server_output
                );
            }
        } else {
            $response = array(
                'success' => false,
                'message' => "Shop ID and Password required"
            );
        }
        return $response;
    }

    /**
     * @return array
     */
    public function getBoolOptions()
    {
        $options = array(
            array(
                'id_enable' => 0,
                'name' => 'No'
            ),
            array(
                'id_enable' => 1,
                'name' => 'Yes'
            ),
        );
        return $options;
    }

    /**
     * @return array
     */
    public function getModeOptions()
    {
        $modeOptions = array(
            array(
                'value' => 'email',
                'name' => 'Email'
            ),
            array(
                'value' => 'sms',
                'name' => 'SMS'
            ),
            array(
                'value' => 'fallback',
                'name' => 'SMS if mobile number, otherwise Email'
            ),
        );
        return $modeOptions;
    }

    /**
     * @return array
     */
    public function getStatusesArray()
    {
        $statuses_array = array();
        $statuses = OrderState::getOrderStates((int) $this->context->language->id);
        foreach ($statuses as $status) {
            $statuses_array[] = array('id' => $status['id_order_state'], 'name' => $status['name']);
        }
        return $statuses_array;
    }

    /**
     * @return array
     */
    public function getInputFields()
    {
        $options = $this->getBoolOptions();
        $modeOptions = $this->getModeOptions();
        $statuses_array = $this->getStatusesArray();

        $inputFields = array(
            array(
                'type' => 'select',
                'label' => $this->l('Plug-in Enabled'),
                'name' => 'EKOMI_RNR_ENABLE',
                'options' => array(
                    'query' => $options,
                    'id' => 'id_enable',
                    'name' => 'name'
                ),
                'required' => true,
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Interface ID'),
                'name' => 'EKOMI_RNR_SHOP_ID',
                'size' => 20,
                'required' => true,
                'lang' => true,
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Interface Password'),
                'name' => 'EKOMI_RNR_SHOP_PASSWORD',
                'size' => 20,
                'required' => true,
                'desc' => '<a href="'.$this->_path.'views/docs/wheredoIfindmyShopIDandPassword.pdf" target="_blank" class="rnr_link">'.$this->l('Where do I find my Interface ID and Interface Password?').'</a>',
                'lang' => true,
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Product Reviews'),
                'name' => 'EKOMI_RNR_PRODUCT_REVIEWS',
                'options' => array(
                    'query' => $options,
                    'id' => 'id_enable',
                    'name' => 'name'
                ),
                'desc' => $this->l('Does your eKomi subscription include product reviews?'),
                'required' => true,
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Collection Method'),
                'name' => 'EKOMI_RNR_MODE',
                'options' => array(
                    'query' => $modeOptions,
                    'id' => 'value',
                    'name' => 'name'
                ),
                'desc' => $this->l('How would you prefer us to send out review requests to your clients?'),
                'required' => true,
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Order Statuses'),
                'name' => 'EKOMI_RNR_ORDER_STATUS[]',
                'id' => 'EKOMI_RNR_ORDER_STATUS',
                'multiple' => true,
                'options' => array(
                    'query' => $statuses_array,
                    'id' => 'id',
                    'name' => 'name'
                ),
                'desc' => $this->l('Which order statuses should trigger a review request?'),
                'required' => true,
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Group Reviews'),
                'name' => 'EKOMI_RNR_GROUP_REVIEWS',
                'options' => array(
                    'query' => $options,
                    'id' => 'id_enable',
                    'name' => 'name'
                ),
                'desc' => $this->l('Do you want to display reviews for both parent and child products together?'),
                'required' => true,
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Message when no reviews found'),
                'name' => 'EKOMI_RNR_NO_REVIEW_MSG',
                'size' => 100,
                'required' => true,
                'desc' => $this->l('What text should be displayed when no reviews are available?'),
                'lang' => true,
            )
        );
        return $inputFields;
    }

    /**
     * @param $configValues
     * @param $params
     * @param $order
     *
     * @return array
     */
    public function getRequiredFields($configValues, $order)
    {
        $customer = new Customer((int) $order->id_customer);
        $address = new Address((int) $order->id_address_delivery);
        $country = new Country((int) $address->id_country);
        $orderData = array(
            'customer' => get_object_vars($customer),
            'address' => get_object_vars($address),
            'order' => get_object_vars($order),
            'shop_name' => (string) Configuration::get('PS_SHOP_NAME'),
            'shop_email' => (string) Configuration::get('PS_SHOP_EMAIL'),
            'product' => $order->getProducts(),
            'country' => get_object_vars($country)
        );
        $fields = array(
            'shop_id' => $configValues['EKOMI_RNR_SHOP_ID'][$order->id_lang],
            'interface_password' => $configValues['EKOMI_RNR_SHOP_PASSWORD'][$order->id_lang],
            'order_data' => $orderData,
            'mode' => $configValues['EKOMI_RNR_MODE'],
            'product_reviews' => $configValues['EKOMI_RNR_PRODUCT_REVIEWS'],
            'plugin_name' => 'prestashop'
        );

        return $fields;
    }

    /**
     * @param $postvars
     */
    public function sendPostVars($postvars)
    {
        $apiUrl = 'https://plugins-dashboard.ekomiapps.de/api/v1/order';

        if (!empty($postvars)) {
            $boundary = md5(time());
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('ContentType:multipart/form-data;boundary=' . $boundary));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postvars));
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage(), 1);
                return $e->getMessage();
            }
        }
    }

    /**
     * This function is specific to Prestashop 1.5.
     * If you want to modify it for Prestashop 1.6, 1.7 or for any other Prestashop version.
     * Then please explicitly use "if" statements for that specific version.
     *
     * @param $param
     * @return string
     */
    public function hookDisplayProductButtons($param)
    {
        if ($this->isActivated()) {
            $singlePageEnabled = Configuration::get('EKOMI_RNR_REVIEW_MS_SPP');
            if ($singlePageEnabled == 1) {
                return $this->getMiniStarsHtml($param);
            }
        }
        return '';
    }

    /**
     * @param $param
     * @return string
     */
    public function hookEkomiReviewStars($param)
    {
        if ($this->isActivated()) {
            return $this->getMiniStarsHtml($param);
        }
        return '';
    }

    /**
     * Hook at the head section of all the pages.
     */
    public function hookHeader()
    {
        if ($this->isActivated()) {
            $this->context->controller->addCSS($this->_path . 'views/css/ekomi-prc.css', 'all');
            $this->context->controller->addJs($this->_path . 'views/js/jquery-3.1.1.min.js', 'all');
            $this->context->controller->addJs($this->_path . 'views/js/ekomi-prc.js', 'all');
        }
    }

    /**
     * @param $param
     * @return string
     */
    public function hookEkomiReviewsCount($param)
    {
        if ($this->isActivated()) {
            $langId = $this->context->language->id;
            $shopId = $this->getShopIdByLang($langId);
            $currentProductId = $this->getProductId($param);
            $productIdsArray = $this->getProductIdsArray($currentProductId, $this->context->language->id);
            
            $reviewsCount = ReviewsModel::getReviewsCount($shopId, $productIdsArray);
        
            return $reviewsCount;
        }
    }

    /**
     * @param $param
     * @return string
     */
    public function hookEkomiReviewsContainer($param)
    {
        if ($this->isActivated()) {
            $langId = $this->context->language->id;
            $shopId = $this->getShopIdByLang($langId);

            $currentProductId = $this->getProductId($param);
            $productIdsArray = $this->getProductIdsArray($currentProductId, $this->context->language->id);
            $productName = $this->getProductName($param);
            $reviewsOffset = 0;
            $reviewsLimit = 5;
            $filter = 1;
            $queryBy = $currentProductId;
            $ajaxUrl = $this->context->link->getModuleLink('ekomiratingsandreviews', 'main');
            $reviewsCount = ReviewsModel::getReviewsCount($shopId, $productIdsArray);
            $allReviews = $this->getAllReviews($shopId, $productIdsArray, $reviewsOffset, $reviewsLimit, $filter);
            $starsCountArray = ReviewsModel::getReviewsStars($shopId, $productIdsArray);
            $starsCountArray = ReviewsModel::getReviewStarsCountsArray($starsCountArray);
            $avgStars = ReviewsModel::getReviewsStarsAvg($shopId, $productIdsArray);
            $link = new Link();
            $id_image = Product::getCover($currentProductId);
            $pImageUrl = $link->getImageLink($this->getProductLinkReWrite($param), $id_image['id_image']);

            $this->smarty->assign(array(
                'ajaxUrl' => $ajaxUrl,
                'queryBy' => $queryBy,
                'hasReviews' => ($reviewsCount > 0) ? true : false,
                'noReviewText' => Configuration::get('EKOMI_RNR_NO_REVIEW_MSG', $langId),
                'articleId' => $currentProductId,
                'shopId' => $shopId,
                'reviewsLimit' => $reviewsLimit,
                'reviewsCount' => $reviewsCount,
                'pageReviewsCount' => count($allReviews),
                'productName' => $productName,
                'pImageUrl' => $pImageUrl,
                'pDescription' => $this->getProductDescription($param),
                'pSku' => $this->getProductReference($param),
                'pPrice' => number_format(Product::getPriceStatic($currentProductId), 2),
                'pPriceCurrency' => Currency::getDefaultCurrency()->iso_code,
                'resourceDirUrl' => $this->_path,
                'starsCountArray' => $starsCountArray,
                'avgStars' => $avgStars,
                'reviews' => $allReviews,
            ));

            return $this->display(__FILE__, 'views/templates/front/reviews_container.tpl');
        }
        return '';
    }

    /**
     * @param $filter
     * @return array
     */
    public function resolveOrderBy($filter)
    {
        switch ($filter) {
            case 1:
                /* Newest reviews. */
                $orderBy = array(
                    'column_name' => 'timestamp',
                    'type' => 'DESC'
                );
                break;
            case 2:
                /* Oldest reviews. */
                $orderBy = array(
                    'column_name' => 'timestamp',
                    'type' => 'ASC'
                );
                break;
            case 3:
                /* Helpful reviews. */
                $orderBy = array(
                    'column_name' => 'helpful',
                    'type' => 'DESC'
                );
                break;
            case 4:
                /* Highest rated reviews. */
                $orderBy = array(
                    'column_name' => 'stars',
                    'type' => 'DESC'
                );
                break;
            case 5:
                /* Lowest rated reviews. */
                $orderBy = array(
                    'column_name' => 'stars',
                    'type' => 'ASC'
                );
                break;

            default:
                $orderBy = array(
                    'column_name' => 'id_ekomi_ratings_and_reviews',
                    'type' => 'DESC'
                );
                break;
        }
        return $orderBy;
    }

    /**
     * @param $shopId
     * @param $productIdsArray
     * @param $reviewsOffset
     * @param $reviewsLimit
     * @param $filter
     * @return mixed
     */
    public function getReviewsHtml(
        $shopId,
        $productIdsArray,
        $reviewsOffset,
        $reviewsLimit,
        $filter
    ) {
        $orderBy = $this->resolveOrderBy($filter);
        $reviews = ReviewsModel::getReviews($shopId, $productIdsArray, $reviewsOffset, $reviewsLimit, $orderBy);
        $this->smarty->assign(array(
            'reviews' => $reviews
        ));
        $result = array(
            "reviews_data" => array(
                "result" => $this->display(__FILE__, 'views/templates/front/reviews_container_partial.tpl'),
                "count" => count($reviews)
            )
        );
        return $result;
    }
        /**
     * @param $shopId
     * @param $productIdsArray
     * @param $reviewsOffset
     * @param $reviewsLimit
     * @param $filter
     * @return mixed
     */
    public function getAllReviews(
        $shopId,
        $productIdsArray,
        $reviewsOffset,
        $reviewsLimit,
        $filter
    ) {
        $orderBy = $this->resolveOrderBy($filter);
        $reviews = ReviewsModel::getReviews($shopId, $productIdsArray, $reviewsOffset, $reviewsLimit, $orderBy);
        return $reviews;
    }

    /**
     * @param $param
     * @return string
     */
    private function getMiniStarsHtml($param)
    {
        $langId = $this->context->language->id;
        $shopId = $this->getShopIdByLang($langId);
        $currentProductId = $this->getProductId($param);
        $productIdsArray = $this->getProductIdsArray($currentProductId, $this->context->language->id);
        if (!empty($currentProductId)) {
            $productName = $this->getProductName($param);
            $reviewsCount = ReviewsModel::getReviewsCount($shopId, $productIdsArray);
            $starsAvg = ReviewsModel::getReviewsStarsAvg($shopId, $productIdsArray);
            $this->smarty->assign(array(
                "starsAvg" => $starsAvg,
                "productName" => $productName,
                "reviewsCount" => $reviewsCount,
            ));
            return $this->display(__FILE__, 'views/templates/front/mini_stars.tpl');
        }
        return '';
    }

    /**
     * @param $param
     * @return mixed
     */
    private function getProductId($param)
    {
        if ($this->prestashopVersion === 1.5) {
            return $param['product']->id;
        } elseif ($this->prestashopVersion === 1.6) {
            if (is_array($param['product'])) {
                return $param['product']['id_product'];
            } else {
                return $param['product']->id;
            }
        } elseif ($this->prestashopVersion === 1.7) {
            return $param['product']['id'];
        }
        return null;
    }

    /**
     * @param $param
     * @return mixed
     */
    private function getLanguagetId($param)
    {
        if ($this->prestashopVersion === 1.5) {
            return $param['product']->id_lang;
        } elseif ($this->prestashopVersion === 1.6) {
            if (is_array($param['product'])) {
                return $param['product']['id_lang'];
            } else {
                return $param['product']->id_lang;
            }
        } elseif ($this->prestashopVersion === 1.7) {
            return $param['product']['id_lang'];
        }
        return null;
    }

    /**
     * @param $param
     * @return mixed
     */
    private function getProductName($param)
    {
        if ($this->prestashopVersion === 1.5) {
            return $param['product']->name;
        } elseif ($this->prestashopVersion === 1.6) {
            if (is_array($param['product'])) {
                return $param['product']['name'];
            } else {
                return $param['product']->name;
            }
        } elseif ($this->prestashopVersion === 1.7) {
            return $param['product']['name'];
        }
        return null;
    }

    /**
     * @param $param
     * @return mixed
     */
    private function getProductDescription($param)
    {
        if ($this->prestashopVersion === 1.5) {
            return $param['product']->description_short;
        } elseif ($this->prestashopVersion === 1.6) {
            if (is_array($param['product'])) {
                return $param['product']['description_short'];
            } else {
                return $param['product']->description_short;
            }
        } elseif ($this->prestashopVersion === 1.7) {
            return $param['product']['description_short'];
        }
        return null;
    }

    /**
     * @param $param
     * @return mixed
     */
    private function getProductReference($param)
    {
        if ($this->prestashopVersion === 1.5) {
            return $param['product']->reference;
        } elseif ($this->prestashopVersion === 1.6) {
            if (is_array($param['product'])) {
                return $param['product']['reference'];
            } else {
                return $param['product']->reference;
            }
        } elseif ($this->prestashopVersion === 1.7) {
            return $param['product']['reference'];
        }
        return null;
    }

    /**
     * @param $param
     * @return mixed
     */
    private function getProductLinkReWrite($param)
    {
        if ($this->prestashopVersion === 1.5) {
            return $param['product']->link_rewrite;
        } elseif ($this->prestashopVersion === 1.6) {
            if (is_array($param['product'])) {
                return $param['product']['link_rewrite'];
            } else {
                return $param['product']->link_rewrite;
            }
        } elseif ($this->prestashopVersion === 1.7) {
            return $param['product']['link_rewrite'];
        }
        return null;
    }

    /**
     * This function is responsible for returning an array of products if the
     * given product contains or has other products associated with it. In future if
     * more product associations have to be supported then only this function will
     * be modified.
     *
     * Currently, it finds if the given product is a "pack of products".
     * If so then it will return the array of all its containing products' IDs including
     * it's own product ID.
     *
     * If the given product is not a "pack of products" it will return array containing
     * only the ID of the product.
     *
     * @param $productId
     * @param $languageId
     * @return array
     */
    public function getProductIdsArray($productId, $languageId)
    {
        $productIdsArray = array();
        array_push($productIdsArray, $productId);
        if (Configuration::get('EKOMI_RNR_GROUP_REVIEWS') == 1) {
            if (Pack::isPack($productIdsArray[0])) {
                $products = Pack::getItems($productIdsArray[0], $languageId);
                foreach ($products as $product) {
                    array_push($productIdsArray, $product->id);
                }
            }
        }
        return $productIdsArray;
    }
}
