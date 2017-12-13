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

class PeinauAPI
{
    const Sandbox = 0;
    const Production = 1;

    public function getSSOUrl($id_type){
        if ($id_type > 1)
            $id_type = 0;

        $url_array = array(
            0 => "https://api.sandbox.connect.fif.tech/sso",
            1 => "https://api.prod.connect.fif.tech/sso",
        );

        return $url_array[$id_type];
    }

    public function getCheckoutUrl($id_type){
        if ($id_type > 1)
            $id_type = 0;

        $url_array = array(
            0 => "https://api.sandbox.connect.fif.tech/checkout",
            1 => "https://api.prod.connect.fif.tech/checkout",
        );

        return $url_array[$id_type];
    }

    public function getCaptureUrl($id_type){
        if ($id_type > 1)
            $id_type = 0;

        $url_array = array(
            0 => "https://api.sandbox.connect.fif.tech/tokenization",
            1 => "https://api.prod.connect.fif.tech/tokenization",
        );

        return $url_array[$id_type];
    }

    public function getAccessToken($id_type, $client_id, $client_secret) {
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.$client_id.':'.$client_secret,
        );
        $endpoint = $this->getSSOUrl($id_type)."/oauth2/v2/token";
        return $this->sendByCURL($endpoint, "grant_type=client_credentials", $headers);
    }

    public function getWithToken($url, $access_token) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        );

        return $this->sendByCURL($url, null, $headers);
    }

    public function postWithToken($url, $access_token) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        );

        return $this->sendByCURL($url, null, $headers, true);
    }

    public function paymentIntent($id_type, $access_token, $transaction_detail) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        );

        $endpoint = $this->getCheckoutUrl($id_type)."/payments";

        return $this->sendByCURL($endpoint, $transaction_detail, $headers);
    }

    public function captureIntent($id_type, $access_token, $transaction_detail) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        );

        $endpoint = $this->getCaptureUrl($id_type)."/captures";
        return $this->sendByCURL($endpoint, $transaction_detail, $headers);
    }


    public function sendByCURL($url, $body, $headers, $post=false)
    {
        $ch = curl_init();
        if ($ch) {

            curl_setopt($ch, CURLOPT_URL, $url);

            if (($post)||($body)){
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
            "reference_id": "'.$cartId.'",
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
            "cancel_url": "'.Context::getContext()->link->getModuleLink('peinau','error').'"
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
                "thumbnail": "//'.(new Link())->getImageLink($product['link_rewrite'],$product['id_image'], 'small_default').'",
                "sku": "'.($product['reference'] ? $product['reference'] : "0").'",
                "name": "'.strval($product['name']).'",
                "description": "'.($product['description_short'] ? strval(strip_tags($product['description_short'])) : strval($product['name'])).'",
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
                "cancel_url": "'.Context::getContext()->link->getModuleLink('peinau','error').'"
            }
            '.($capture_token ? ', "additional_attributes": {"capture_token": "'.$capture_token.'"}' : '').'
        }';

        return $transaction_detail;
    }
}
