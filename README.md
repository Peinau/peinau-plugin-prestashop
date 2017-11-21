# Módulo Prestashop para Peinau

Plugin (o módulo) de Prestashop, para aceptar pagos a través de Peinau.

![Screenshot de pagos](views/img/screenshot.png)

## Uso
### Requisitos
- Prestashop v1.6 o v1.7
- PHP >= 5.4

### Descarga e Instalación

Descarga la [última versión del plugin desde acá](https://github.com/peinau/peinau-plugin-prestashop/releases/latest).

Para instalar, hay dos opciones:

- Subir el .zip descargado a través del Backoffice de Prestashop. Hay que dirigirse a Módulos, presionar el botón ```Subir un módulo```, y seleccionar el plugin descargado.
- Deja el plugin descomprimido en la carpeta ```modules/peinau```, dentro de la carpeta donde tienes instalado Prestashop.

Luego, busca el módulo en la opción "Módulos" del backoffice, bajo el nombre de "Peinau", y haz click en el botón "Instalar".

### Configuración

Antes de ocupar el módulo, debes configurar lo esencial para que funcione correctamente. En la página de configuración del módulo podrás ver lo siguiente:

#### Llaves y Endpoints 

- Identificador:
	- Descripción: Número único que identifica la aplicación.
	- _Ejemplo: 708060666609999000000_
- Llave secreta:
	- Descripción: Alfanumérico que permite autorizar el acceso a la API de Peinau.
	- _Ejemplo: a65xoHzQ1QlE5R65IO9C0WzzuWDmwEXqFOUqw8LfINrN_
- URL Endpoint SSO:
	- Descripción: URL de autorización-
	- _Ejemplo: https://quickpay-connect-sso.azurewebsites.net_
- URL Endpoint Checkout:
	- Descripción: URL para realizar intención de pago.
	- _Ejemplo: https://quickpay-connect-checkout.azurewebsites.net_
- URL Endpoint Capture Card: 
	- Descripción: URL para capturar tarjetas de crédito.
	- _Ejemplo: https://quickpay-connect-capture-card.azurewebsites.net_

#### Medios de pago disponibles

- Debería el pago con CMR estar habilitado?:
	- Descripción: Permite activar o desactivar el pago con CMR.
	- Estados: Habilitado / Deshabilitado
- Debería el pago con Tarjeta de crédito estar habilitado?:
	- Descripción: Permite activar o desactivar el pago con Tarjeta de Crédito.
	- Estados: Habilitado / Deshabilitado
- Debería el pago con WebPay estar habilitado?: 
	- Descripción: Permite activar o desactivar el pago con Pago con WebPay.
	- Estados: Habilitado / Deshabilitado


## Desarrollo

### Consideraciones
El plugin posee compatibilidad para Prestashop v1.6 y v1.7 de forma simultánea, por ende, cualquier cambio debe considerar el soporte para ambas versiones.

### Ambiente de desarrollo
Se crea a través de [Docker](https://www.docker.com), _se sugiere revisar el item [Deployment de desarrollo](#desarrollo)_.

## Deployment

El plugin posee soporte para levantar _Prestashop + Plugin Peinau_ de forma automática, a través de Docker Compose.

### Desarrollo

docker-compose-dev.yml levantará dos contenedores de Prestashop, v1.6 y v1.7

* ```docker-compose -f docker-compose-dev.yml up```
* URL Backoffice PS v1.6: http://localhost:8016/adminpn
* URL Backoffice PS v1.7: http://localhost:8017/adminpn
* Usuario Backoffice: peinau@peinau.com - Clave: peinau

### Producción

La variable de entorno ```MYSQL_ROOT_PASSWORD``` debe especificar la clave que tendrá el usuario Root de MySQL. Se sugiere crear una que no sea no intuitiva. Y en caso de que no se especifique, por defecto la clave será _admin_.

* ```MYSQL_ROOT_PASSWORD=clavepropiaderoot docker-compose up -d```
* URL Instalación: http://localhost:8000/installpn
* URL Backoffice PS: http://localhost:8000/adminpn
* Host MySQL: peinau_mysql
* User MySQL: root
* Password Root MySQL: La especificada previamente en ```MYSQL_ROOT_PASSWORD```.

