<div>
	<h1 style="color: green;">¡Pago realizado con éxito!</h1>
    <br/>

    <div><div style="width:40%; float: left;">Número de orden de compra:</div><div style="width:60%; float: left;">{$payment->transaction->buy_order}</div></div>
    <div><div style="width:40%; float: left;">Nombre del comercio:</div><div style="width:60%; float: left;">{$name}</div></div>
    <div><div style="width:40%; float: left;">Monto: </div><div style="width:60%; float: left;">${$payment->transaction->amount}</div></div>
    <div><div style="width:40%; float: left;">Código de autorización de la transacción:</div><div style="width:60%; float: left;">{$payment->authorizations->code}</div></div>
    <div><div style="width:40%; float: left;">Fecha de transacción</div><div style="width:60%; float: left;">{{$payment->transaction->date}}</div></div>
    <div><div style="width:40%; float: left;">Tipo de pago:</div><div style="width:60%; float: left;">{$payment->transaction->type}</div></div>
    <div><div style="width:40%; float: left;">Número de cuotas</div><div style="width:60%; float: left;">{$payment->transaction->installments_number}</div></div>
    <div><div style="width:40%; float: left;">Últimos 4 dígitos de la tarjeta:</div><div style="width:60%; float: left;">{$payment->card_number->pan_last4}</div></div>
    <div><div style="width:40%; float: left;">Descripción del artículo</div><div style="width:60%; float: left;"> Compra en {$name}</div></div>

</div>