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
            $response = $peinauapi->getAccessToken(Configuration::get("PEINAU_ENDPOINT_URL"), Configuration::get("PEINAU_IDENTIFIER"), Configuration::get("PEINAU_SECRET_KEY"));

            if ($response == null) {
                return $this->displayError('An error occurred while trying to make the payment');
            }

            if (Configuration::get("PEINAU_DEBUG_MODE") == true) {
                PrestaShopLogger::addLog("access token resp: " . $response);
            }

            $jsonRToken = Tools::jsonDecode($response);

            $access_token = $jsonRToken->access_token;

            if ($access_token == null) {
                return $this->displayError('An error occurred while trying to redirect the customer');
            }

            Context::getContext()->cookie->__set('access_token', $access_token);
            Context::getContext()->cookie->__set('access_token_exp', $jsonRToken->expires_in);

            $cart = Context::getContext()->cart;

            $payment_method_attr = Tools::getValue('payment_method');

            $payment_method = null;

            if ($payment_method_attr == "WEBPAY") {
                $payment_method = "TRANSBANK_WEBPAY";
            } else {
                $payment_method = "QUICKPAY_CREDIT";
            }

            $transaction_detail = PeinauAPI::createTransactionReq($cart, $payment_method);

            if (Configuration::get("PEINAU_DEBUG_MODE") == true) {
                PrestaShopLogger::addLog("intent req: " . $transaction_detail);
            }

            $response = $peinauapi->paymentIntent(Configuration::get("PEINAU_ENDPOINT_URL"), $access_token, $transaction_detail);

            if ($response == null) {
                return $this->displayError('An error occurred while trying to make the payment');
            }

            if (Configuration::get("PEINAU_DEBUG_MODE") == true) {
                PrestaShopLogger::addLog("intent resp: " . $response);
            }

            $jsonRIntent = Tools::jsonDecode($response);

            if ($jsonRIntent == null) {
                return $this->displayError('An error occurred while trying to make the payment');
            }

            Context::getContext()->cookie->__set('selfurl', $jsonRIntent->links[0]->href);

            if (Configuration::get("PEINAU_DEBUG_MODE") == true) {
                PrestaShopLogger::addLog("Redirected to : " . $jsonRIntent->links[1]->href);
            }

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
