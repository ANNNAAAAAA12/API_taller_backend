# Taller Consumo de APIs - CloudSpace Solutions

Aplicacion web desarrollada con Laravel para CloudSpace Solutions. Permite realizar pagos mediante Stripe y enviar confirmaciones transaccionales con SendGrid.

## Funcionalidades

- Checkout web para ingresar correo, monto y moneda.
- Creacion de Stripe PaymentIntents mediante una clase de servicio.
- Formulario seguro con Stripe Elements.
- Recepcion y validacion de webhooks de Stripe.
- Registro de pagos exitosos en MySQL.
- Envio de correos transaccionales mediante la API REST de SendGrid.
- Separacion de integraciones externas en clases de servicio.

## Tecnologias

- PHP 8.5
- Laravel 13
- Stripe PHP SDK
- SendGrid API REST
- MySQL 8.4
- Laravel Sail
- Docker
- Blade y Tailwind CSS

## Requisitos

- Docker
- Docker Compose
- Git

## Instalacion

1. Clonar el repositorio:

```bash
git clone https://github.com/ANNNAAAAAA12/API_taller_backend.git
cd API_taller_backend
```

2. Crear el archivo de entorno:

```bash
cp .env.example .env
```

3. Configurar las variables necesarias:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

SENDGRID_API_KEY=...
MAIL_FROM_ADDRESS="correo-verificado@ejemplo.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Las credenciales reales deben permanecer en `.env`. Este archivo esta excluido de Git mediante `.gitignore`.

4. Iniciar los contenedores:

```bash
docker compose up -d
```

5. Instalar dependencias y preparar la aplicacion:

```bash
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
```

La aplicacion quedara disponible en:

- Checkout: http://localhost/checkout
- Envio de correo: http://localhost/email

## Configuracion de Stripe Webhook

Para desarrollo local se puede usar Stripe CLI:

```bash
stripe listen --forward-to localhost/stripe/webhook
```

Copiar el `whsec_...` mostrado por Stripe CLI en `STRIPE_WEBHOOK_SECRET`.

Importante: usar tarjetas de prueba de Stripe y no credenciales reales durante las pruebas.

## Rutas principales

| Metodo | Ruta | Funcion |
| --- | --- | --- |
| GET | `/checkout` | Muestra el formulario de pago |
| POST | `/create-payment-intent` | Crea el PaymentIntent y retorna `clientSecret` |
| POST | `/stripe/webhook` | Recibe eventos de Stripe y registra pagos exitosos |
| GET | `/email` | Muestra el formulario de correo |
| POST | `/email` | Envia un correo mediante SendGrid |

## Base de datos

Los pagos confirmados se almacenan en la tabla `payments` con:

- `stripe_payment_intent_id`
- `stripe_event_id`
- `customer_email`
- `amount`
- `currency`
- `status`
- `description`

## Pruebas

Ejecutar las pruebas desde Laravel Sail:

```bash
./vendor/bin/sail artisan test
```

## Seguridad

- Nunca publicar las claves reales en GitHub.
- Rotar cualquier clave que haya sido compartida o expuesta.
- Validar la firma de los webhooks antes de procesarlos.
- Mantener las claves privadas exclusivamente en `.env`.
- La ruta `/stripe/webhook` esta excluida de la validacion CSRF.
