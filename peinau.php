<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_.'peinau/api/PeinauAPI.php';
class Peinau extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'peinau';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Peinau';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Peinau');
        $this->description = $this->l('Pay with Peinau using your favorite Credit Card!');

        $this->confirmUninstall = $this->l('Are you sure that you want to uninstall the module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        Configuration::updateValue("PEINAU_PAYMENT_CMR", 1);
        Configuration::updateValue("PEINAU_PAYMENT_CC", 1);
        Configuration::updateValue("PEINAU_PAYMENT_WEBPAY", 1);

        //include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayPayment');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PEINAU_IDENTIFIER');
        Configuration::deleteByName('PEINAU_SECRET_KEY');
        Configuration::deleteByName('PEINAU_PAYMENT_CMR');
        Configuration::deleteByName('PEINAU_PAYMENT_CC');
        Configuration::deleteByName('PEINAU_PAYMENT_WEBPAY');
        Configuration::deleteByName('PEINAU_SSO_ENDPOINT_URL');
        Configuration::deleteByName('PEINAU_CC_ENDPOINT_URL');
        Configuration::deleteByName('PEINAU_CH_ENDPOINT_URL');

        //include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPeinauModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPeinauModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Enter the identifier of the app'),
                        'name' => 'PEINAU_IDENTIFIER',
                        'label' => $this->l('Identifier'),
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Enter the secret key of the app'),
                        'name' => 'PEINAU_SECRET_KEY',
                        'label' => $this->l('Secret Key'),
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'prefix' => '<i class="icon icon-cloud"></i>',
                        'desc' => $this->l('Enter the ENDPOINT of the Peinau environment'),
                        'name' => 'PEINAU_SSO_ENDPOINT_URL',
                        'label' => $this->l('SSO Endpoint URL (Ex.: https://api-sso-quickpay.azurewebsites.net)'),
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'prefix' => '<i class="icon icon-cloud"></i>',
                        'desc' => $this->l('Enter the ENDPOINT of the Peinau environment'),
                        'name' => 'PEINAU_CH_ENDPOINT_URL',
                        'label' => $this->l('Checkout Endpoint URL (Ex.: https://api-checkout-quickpay.azurewebsites.net)'),
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'prefix' => '<i class="icon icon-cloud"></i>',
                        'desc' => $this->l('Enter the ENDPOINT of the Peinau environment'),
                        'name' => 'PEINAU_CC_ENDPOINT_URL',
                        'label' => $this->l('Capture Card Endpoint URL (Ex.: https://api-capture-card-quickpay.azurewebsites.net)'),
                    ),
                    array(
                        'type' => 'radio',
                        'desc' => $this->l('CMR Payment'),
                        'name' => 'PEINAU_PAYMENT_CMR',
                        'is_bool' => true,
                        'values' => array(
                            array(
                              'id' => 'active_on',
                              'value' => 1,
                              'label' => $this->l('Enabled')
                            ),
                            array(
                              'id' => 'active_off',
                              'value' => 0,
                              'label' => $this->l('Disabled')
                            )
                          ),
                        'label' => $this->l('Should CMR Payment be active?'),
                    ),
                    array(
                        'type' => 'radio',
                        'is_bool' => true,
                        'desc' => $this->l('Credit Card Payment'),
                        'name' => 'PEINAU_PAYMENT_CC',
                        'values' => array(
                            array(
                              'id' => 'active_on',
                              'value' => 1,
                              'label' => $this->l('Enabled')
                            ),
                            array(
                              'id' => 'active_off',
                              'value' => 0,
                              'label' => $this->l('Disabled')
                            )
                          ),
                        'label' => $this->l('Should Credit Card Payment be active?'),
                    ),
                    array(
                        'type' => 'radio',
                        'is_bool' => true,
                        'desc' => $this->l('WebPay Payment'),
                        'name' => 'PEINAU_PAYMENT_WEBPAY',
                        'values' => array(
                            array(
                              'id' => 'active_on',
                              'value' => 1,
                              'label' => $this->l('Enabled')
                            ),
                            array(
                              'id' => 'active_off',
                              'value' => 0,
                              'label' => $this->l('Disabled')
                            )
                          ),
                        'label' => $this->l('Should WebPay Payment be active?'),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        PrestaShopLogger::addLog("? PEINAU_PAYMENT_CMR : ". Configuration::get('PEINAU_PAYMENT_CMR', 1));

        return array(
            'PEINAU_SSO_ENDPOINT_URL' => Configuration::get('PEINAU_SSO_ENDPOINT_URL'),
            'PEINAU_CC_ENDPOINT_URL' => Configuration::get('PEINAU_CC_ENDPOINT_URL'),
            'PEINAU_CH_ENDPOINT_URL' => Configuration::get('PEINAU_CH_ENDPOINT_URL'),

            'PEINAU_PAYMENT_CMR' => Configuration::get('PEINAU_PAYMENT_CMR'),
            'PEINAU_PAYMENT_CC' => Configuration::get('PEINAU_PAYMENT_CC'),
            'PEINAU_PAYMENT_WEBPAY' => Configuration::get('PEINAU_PAYMENT_WEBPAY'),
            'PEINAU_IDENTIFIER' => Configuration::get('PEINAU_IDENTIFIER'),
            'PEINAU_SECRET_KEY' => Configuration::get('PEINAU_SECRET_KEY'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            PrestaShopLogger::addLog($key." : ". Tools::getValue($key));
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        $this->smarty->assign(array('module_dir' => $this->_path,
                            'payment_cmr' => Configuration::get('PEINAU_PAYMENT_CMR'),
                            'payment_cc' => Configuration::get('PEINAU_PAYMENT_CC'),
                            'payment_wp' => Configuration::get('PEINAU_PAYMENT_WEBPAY'),
                            )
                        );

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = null;
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR'))
            $this->smarty->assign('status', 'ok');

        $total = null;

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $total = Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false);
        } else {
            $total = Tools::displayPrice($order->getOrdersTotalPaid(), new Currency($order->id_currency), false);
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'shop_name' => $this->context->shop->name,
            'total' => $total,
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $payments = array();
        $dynPaymentOption = 'PrestaShop\PrestaShop\Core\Payment\PaymentOption';

        if (Configuration::get('PEINAU_PAYMENT_CMR') == 1) {
            $CMROption = new $dynPaymentOption;

            $paymentController = $this->context->link->getModuleLink(
            $this->name,'redirect',array(),true);
            $CMROption->setCallToActionText($this->l('Pay with CMR').' '.$this->l('(using Peinau)'))
            ->setAction($paymentController)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/cmr_small.png'));
            array_push($payments, $CMROption);
        }

        if (Configuration::get('PEINAU_PAYMENT_CC') == 1) {
            $CCOption = new $dynPaymentOption;

            $paymentController = $this->context->link->getModuleLink(
            $this->name,'capture',array(),true);
            $CCOption->setCallToActionText($this->l('Pay with Credit Card').' '.$this->l('(using Peinau)'))
            ->setAction($paymentController)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/cc_small.png'));

            array_push($payments, $CCOption);
        }

        if (Configuration::get('PEINAU_PAYMENT_WEBPAY') == 1) {
            $WPOption = new $dynPaymentOption;

            $paymentController = $this->context->link->getModuleLink(
            $this->name,'redirect',["payment_method"=>"WEBPAY"] ,true);
            $WPOption->setCallToActionText($this->l('Pay with WebPay').' '.$this->l('(using Peinau)'))
            ->setAction($paymentController)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/webpay_small.png'));

            array_push($payments, $WPOption);
        }

        return $payments;
    }

}
