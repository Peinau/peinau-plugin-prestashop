<?php
/**
 * MIT License
 * Copyright (c) 2017 Peinau
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
        $this->version = '1.0.0-beta.5';
        $this->author = 'Peinau';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Peinau');
        $this->description = $this->l('Pay with Peinau using your favorite Credit Card!');

        $this->confirmUninstall = $this->l('Are you sure that you want to uninstall the module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

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
        Configuration::updateValue("PEINAU_DEBUG_MODE", 1);
        Configuration::updateValue("PEINAU_ENDPOINT_URL", 1);


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
        Configuration::deleteByName('PEINAU_ENDPOINT_URL');

        return parent::uninstall();
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitPeinauModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

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

    protected function getConfigForm()
    {
        $options = array(
            array(
              'id_option' => 0,
              'name' => $this->l('Sandbox')
            ),
            array(
              'id_option' => 1,
              'name' => $this->l('Production')
            ),
          );

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
                        'type' => 'select',
                        'required' => true,
                        'prefix' => '<i class="icon icon-cloud"></i>',
                        'desc' => $this->l('Select the ENDPOINT of the Peinau environment'),
                        'name' => 'PEINAU_ENDPOINT_URL',
                        'label' => $this->l('Endpoint'),
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name'
                          )
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
                        ),
                    array(
                        'type' => 'radio',
                        'is_bool' => true,
                        'desc' => $this->l('Debug Mode'),
                        'name' => 'PEINAU_DEBUG_MODE',
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
                        'label' => $this->l('Enable if you want to see the requests in the log'),
                    )


                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {

        return array(
            'PEINAU_ENDPOINT_URL' => Configuration::get('PEINAU_ENDPOINT_URL'),

            'PEINAU_PAYMENT_CMR' => Configuration::get('PEINAU_PAYMENT_CMR'),
            'PEINAU_PAYMENT_CC' => Configuration::get('PEINAU_PAYMENT_CC'),
            'PEINAU_PAYMENT_WEBPAY' => Configuration::get('PEINAU_PAYMENT_WEBPAY'),
            'PEINAU_DEBUG_MODE' => Configuration::get('PEINAU_DEBUG_MODE'),

            'PEINAU_IDENTIFIER' => Configuration::get('PEINAU_IDENTIFIER'),
            'PEINAU_SECRET_KEY' => Configuration::get('PEINAU_SECRET_KEY'),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            PrestaShopLogger::addLog($key." : ". Tools::getValue($key));
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

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
