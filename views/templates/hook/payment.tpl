{*
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
*}

{if $payment_cmr == 1}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module" id="peinau_cmr_payment_button">
			<a id="cmr" class="peinau" href="{$link->getModuleLink('peinau', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with CMR' mod='peinau'}">
				{l s='Pay with CMR'}
				<span>
					{l s='(using Peinau)'}
				</span>
			</a>
		</p>
	</div>
</div>
{/if}

{if $payment_cc == 1}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module" id="peinau_cc_payment_button">
			<a id="cc" class="peinau" href="{$link->getModuleLink('peinau', 'capture', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with Credit Card' mod='peinau'}">
				{l s='Pay with Credit Card'}
				<span>
					{l s='(using Peinau)'}
				</span>
			</a>
		</p>
	</div>
</div>
{/if}

{if $payment_wp == 1}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module" id="peinau_webpay_payment_button">
			<a id="webpay" class="peinau" href="{$link->getModuleLink('peinau', 'redirect', ["payment_method"=>"WEBPAY"], true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with Credit Card' mod='peinau'}">
				{l s='Pay with WebPay'}
				<span>
					{l s='(using Peinau)'}
				</span>
			</a>
		</p>
	</div>
</div>
{/if}
