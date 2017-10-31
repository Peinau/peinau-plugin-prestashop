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

class PeinauRedirectModuleFrontController extends ModuleFrontController
{
    /**
     * Do whatever you have to before redirecting the customer on the website of your payment processor.
     */
    public function initContent()
    {
        parent::initContent();
        /**
         * Oops, an error occured.
         */
        if (Tools::getValue('action') == 'error') {
            return $this->displayError('An error occurred while trying to redirect the customer');
        } else {
            $this->context->smarty->assign(array(
                'cart_id' => Context::getContext()->cart->id,
                'secure_key' => Context::getContext()->customer->secure_key,
            ));

            $peinauapi = new PeinauAPI();
            $response = $peinauapi->getAccessToken(Configuration::get("PEINAU_SSO_ENDPOINT_URL"), Configuration::get("PEINAU_IDENTIFIER"), Configuration::get("PEINAU_SECRET_KEY"));

            if ($response == null) {
                return $this->displayError('An error occurred while trying to make the payment');
            }

            $jsonRToken = Tools::jsonDecode($response);

            $access_token = $jsonRToken->access_token;

            if ($access_token == null) {
                return $this->displayError('An error occurred while trying to redirect the customer');
            }

            $cart = Context::getContext()->cart;

            $payment_method_attr = Tools::getValue('payment_method');

            $payment_method = null;

            if ($payment_method_attr == "WEBPAY") {
                $payment_method = "TRANSBANK_WEBPAY";
            } else {
                $payment_method = "QUICKPAY_CREDIT";
            }

            $transaction_detail = PeinauAPI::createTransactionReq($cart, $payment_method);

            $response = $peinauapi->paymentIntent(Configuration::get("PEINAU_CH_ENDPOINT_URL"), $access_token, $transaction_detail);

            if ($response == null) {
                return $this->displayError('An error occurred while trying to make the payment');
            }

            $jsonRIntent = Tools::jsonDecode($response);

            if ($jsonRIntent == null) {
                return $this->displayError('An error occurred while trying to make the payment');
            }

            Context::getContext()->cookie->__set('selfurl', $jsonRIntent->links[0]->href);
            Tools::redirect($jsonRIntent->links[1]->href);
            return $this->displayError('An error occurred while trying to make the payment');
        }
    }

    protected function displayError($message, $description = false)
    {
        $peinauerrors = array($this->module->l($message));
        $this->context->smarty->assign('peinauerrors', $peinauerrors);

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            return $this->setTemplate('error.16.tpl');
        } else {
            return $this->setTemplate('module:peinau/views/templates/front/error.tpl');
        }
    }
}
