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

class PeinauAPI
{
    public function getAccessToken($url, $client_id, $client_secret) {
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.$client_id.':'.$client_secret,
        );
        $endpoint = $url."/oauth2/v2/token";
        return $this->sendByCURL($endpoint, "grant_type=client_credentials", $headers);
    }

    public function paymentIntent($url, $access_token, $transaction_detail) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        );

        $endpoint = $url."/payments";
        return $this->sendByCURL($endpoint, $transaction_detail, $headers);
    }

    public function captureIntent($url, $access_token, $transaction_detail) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        );

        $endpoint = $url."/captures";
        return $this->sendByCURL($endpoint, $transaction_detail, $headers);
    }

    public function sendByCURL($url, $body, $headers)
    {
        $ch = curl_init();
        if ($ch) {

            curl_setopt($ch, CURLOPT_URL, $url);

            if ($body) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            if ($headers) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result = curl_exec($ch);
            curl_close($ch);
        }

        return $result;
    }

    public static function createCaptureReq($cart) {
        $cartId = $cart->id;
        $customer = new Customer(intval($cart->id_customer));
        $shipping_address = new Address(intval($cart->id_address_delivery));
        $country = new Country(intval($shipping_address->id_country));

        $capture_req = '
        {
            "capture": "CREDIT_CARD",
            "capture_method": "TOKENIZATION",
            "cardholder": {
            "reference_id": "Merchant_id_reference",
            "name": "'.$customer->firstname.' '.$customer->lastname.'",
            "email": "'.$customer->email.'",
            "country": "'.$country->iso_code.'"
            },
            "billing": {
            "line1": "'.$shipping_address->address1.'",
            "city": "'.$shipping_address->city.'",
            "state": "RM",
            "country": "'.$country->iso_code.'"
            },
            "redirect_urls": {
            "return_url": "'.Context::getContext()->link->getModuleLink('peinau','confirmation').'",
            "cancel_url": "'.Context::getContext()->link->getPageLink('order', null, null, 'step=3').'"
            }
        }';

        return $capture_req;
    }

    public static function createTransactionReq($cart, $method = "QUICKPAY_CREDIT", $capture_token = null) {
        $cartId = $cart->id;
        $order = new Order(Order::getOrderByCartId($cartId));
        $summary = $cart->getSummaryDetails();

        $customer = new Customer(intval($cart->id_customer));
        $shipping_address = new Address(intval($cart->id_address_delivery));
        $country = new Country(intval($shipping_address->id_country));
        $products = $cart->getProducts(true);
        $total_items = $cart->nbProducts();

        $string_products = "";
        foreach($products as $product) {
            $item = '{
                "thumbnail":"//'.(new Link())->getImageLink($product['link_rewrite'],$product['id_image'], 'small_default').'",
                "sku": "'.($product['reference'] ? $product['reference'] : "0").'",
                "name": "'.strval($product['name']).'",
                "description": "'.($product['description_short'] ? strval($product['description_short']) : strval($product['name'])).'",
                "quantity": '.strval($product['cart_quantity']).',
                "price": '.strval($product['price']).',
                "tax": '.strval($product['price_wt'] - $product['price']).'
            },';

            $string_products.=$item;
        }

        $string_products = Tools::substr($string_products, 0, -1);

        $transaction_detail = '
        {
            "intent": "sale",
            "payer": {
                "payer_info": {
                "email": "'.$customer->email.'",
                "full_name": "'.$customer->firstname.' '.$customer->lastname.'",
                "country": "'.$country->iso_code.'"
                },
                "payment_method": "'.$method.'"
                '.($capture_token ? ', "capture_token": "'.$capture_token.'"' : '').'
            },
            "transaction": {
                "reference_id": "'.$cartId.'",
                "description": "Compra en: '.Configuration::get('PS_SHOP_NAME').' de '.$total_items.' producto(s).",
                "soft_descriptor": "Compra en: '.Configuration::get('PS_SHOP_NAME').'",
                "amount": {
                "currency": "CLP",
                "total": '.$cart->getOrderTotal(true, Cart::BOTH).',
                "details": {
                    "subtotal": '.$cart->getOrderTotal(false, 1).',
                    "tax": '.strval($summary['total_tax']).',
                    "shipping": '.strval($summary['total_shipping']).',
                    "shipping_discount": 0
                }
                },
                "item_list": {
                "shipping_address": {
                    "line1": "'.$shipping_address->address1.'",
                    "city": "'.$shipping_address->city.'",
                    "country_code": "'.$country->iso_code.'",
                    "phone": "'.($shipping_address->phone ? $shipping_address->phone : "0").'",
                    "type": "HOME_OR_WORK",
                    "recipient_name": "'.$shipping_address->firstname.' '.$shipping_address->lastname.'"
                },
                "shipping_method": "DIGITAL",
                "items":
                    [
                        '.$string_products.'
                    ]
                }
            },
            "redirect_urls": {
                "return_url": "'.Context::getContext()->link->getModuleLink('peinau','confirmation').'",
                "cancel_url": "'.Context::getContext()->link->getPageLink('order', null, null, 'step=3').'"
            }
        }';

        return $transaction_detail;
    }
}
