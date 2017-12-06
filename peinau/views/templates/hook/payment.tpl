{*
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

{if $payment_ex == 1}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module" id="peinau_express_payment_button">
			<a id="express" class="peinau" href="{$link->getModuleLink('peinau', 'redirect', ["payment_method"=>"EXPRESS"], true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with Express Checkout' mod='peinau'}">
				{l s='Pay with Express Checkout'}
				<span>
					{l s='(using Peinau)'}
				</span>
			</a>
		</p>
	</div>
</div>
{/if}
