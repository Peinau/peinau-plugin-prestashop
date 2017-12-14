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

{extends file='page.tpl'}
{block name='page_content'}

<div>
	<h1 align="center" style="color: orange;">Tu compra no pudo ser realizada</h1>
    <br/>
	
	<p>Las posibles causas de este rechazon son:</p>
    <ul> 
		<li>- Error en el ingreso de los datos de tu tarjeta (fecha y/o código de seguridad)</li>
		<li>- Tu tarjeta no cuenta con saldo suficiente</li>
		<li>- Tarjeta aún no habilitada en el sistema financiero</li>
    </ul>

</div>

<a href="{$link->getPageLink('order', null, null, 'step=3')}">{l s='Go back and try again' mod='peinau'} </a>

{/block}
