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

class PeinauConfirmationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {

        $self_url = Context::getContext()->cookie->selfurl;
        PrestaShopLogger::addLog($self_url);
        $peinauapi = new PeinauAPI();
        $response = $peinauapi->sendByCURL($self_url, null, null);

        $jsonRIntent = Tools::jsonDecode($response);

        PrestaShopLogger::addLog($response);

        if ($jsonRIntent->state == "canceled") {
            return $this->displayError('El pago ha sido anulado');
        } else if ($jsonRIntent->state == "paid") {
            $cart_id=Context::getContext()->cart->id;
            $secure_key=Context::getContext()->customer->secure_key;

            $cart = new Cart((int)$cart_id);
            $customer = new Customer((int)$cart->id_customer);

            /**
             * Since it's an example we are validating the order right here,
             * You should not do it this way in your own module.
             */
            $payment_status = Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
            $message = null; // You can add a comment directly into the order so the merchant will see it in the BO.

            /**
             * Converting cart into a valid order
             */

            $module_name = $this->module->displayName;
            $currency_id = (int)Context::getContext()->currency->id;

            $this->module->validateOrder($cart_id, $payment_status, $cart->getOrderTotal(), $module_name, $message, array(), $currency_id, false, $secure_key);

            /**
             * If the order has been validated we try to retrieve it
             */
            $order_id = Order::getOrderByCartId((int)$cart->id);

            if ($order_id && ($secure_key == $customer->secure_key)) {
                /**
                 * The order has been placed so we redirect the customer on the confirmation page.
                 */

                $module_id = $this->module->id;
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart_id.'&id_module='.$module_id.'&id_order='.$order_id.'&key='.$secure_key);
            } else {
                /**
                 * An error occured and is shown on a new page.
                 */
                $this->errors[] = $this->module->l('An error occurred while trying to make the payment');
                return $this->setTemplate('error.tpl');
            }
        } else if ($jsonRIntent->state == "captured") {
            $access_token = Context::getContext()->cookie->access_token;
            $cart = Context::getContext()->cart;

            $transaction_detail = PeinauAPI::createTransactionReq($cart, "QUICKPAY_TOKEN", $jsonRIntent->id);
            PrestaShopLogger::addLog($transaction_detail);

            $response = $peinauapi->paymentIntent(Configuration::get("PEINAU_CH_ENDPOINT_URL"), $access_token, $transaction_detail);

            PrestaShopLogger::addLog($response);
            $jsonRIntent = Tools::jsonDecode($response);

            Context::getContext()->cookie->__set('selfurl', $jsonRIntent->links[0]->href);
            Tools::redirect($jsonRIntent->links[1]->href);
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
