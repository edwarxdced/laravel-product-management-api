# Product Management API

API REST desarrollada con **Laravel y MySQL** para gestionar productos y registrar precios adicionales en distintas monedas. Utiliza Eloquent ORM, Form Requests y pruebas automatizadas con PHPUnit.

## Requisitos

- PHP y Composer compatibles con la versión de Laravel definida en `composer.json`.
- MySQL y la extensión PHP `pdo_mysql`.

## Instalación

1. Clonar el repositorio e instalar dependencias:

   ```bash
   git clone https://github.com/edwarxdced/laravel-product-management-api
   cd laravel-product-management-api
   composer install
   ```

2. Crear el archivo de entorno (`.env`) a partir de `.env.example`. En **PowerShell**:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Crear la base de datos en MySQL:

   ```sql
   CREATE DATABASE backend_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Configurar la conexión en `.env` (ajustar usuario, contraseña y puerto según el entorno):

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=backend_test
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Preparar y ejecutar la aplicación:

   ```bash
   php artisan key:generate
   php artisan config:clear
   php artisan migrate
   php artisan db:seed --class=CurrencySeeder
   php artisan serve
   ```

La API estará disponible en `http://127.0.0.1:8000/api`. El seeder crea monedas de ejemplo (USD y EUR); ejecútalo una sola vez sobre la base de datos recién creada.

## Endpoints

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/api/products` | Listar productos con paginación (`page`, `per_page`) |
| POST | `/api/products` | Crear producto |
| GET | `/api/products/{product}` | Consultar producto, moneda base y precios adicionales |
| PUT / PATCH | `/api/products/{product}` | Actualizar producto |
| DELETE | `/api/products/{product}` | Eliminar producto |
| GET | `/api/products/{product}/prices` | Listar precios adicionales |
| POST | `/api/products/{product}/prices` | Registrar precio adicional en otra moneda |

El listado utiliza `per_page=15` por defecto (máximo 100). Las respuestas HTTP habituales son **200**, **201**, **204**, **404** y **422**. El precio base se almacena en `products`; los precios adicionales, en `product_prices`. No se admiten precios adicionales con la moneda base ni monedas duplicadas por producto.

## Colección de Postman

El archivo **`Product Management API.postman_collection.json`** se incluye en la carpeta `docs/` del repositorio.

1. Abrir **Postman** y seleccionar **Import**.
2. Elegir `Product Management API.postman_collection.json`.
3. En las variables de la colección, configurar `base_url` con `http://127.0.0.1:8000/api`.
4. Ejecutar **Create Product** para crear un producto de ejemplo: la colección guarda automáticamente el ID en `product_id`.
5. Probar las demás solicitudes; al registrar precios adicionales, usar una `currency_id` diferente de la moneda base. Ejecutar **Delete Product** al final si se desea eliminar el registro de prueba.

La colección incluye las carpetas **Products** y **Product Prices**.

## Pruebas automatizadas

Ejecutar toda la suite:

```bash
php artisan test
```

Ejecutar solo los tests de productos y precios:

```bash
php artisan test --filter=Product
```

Los tests utilizan una configuración de base de datos aislada. El mecanismo de protección admite **MySQL con una base llamada `backend_test_testing`** o **SQLite en memoria (`:memory:`)**. Antes de ejecutarlos, revisar `phpunit.xml` y/o `.env.testing` para comprobar la conexión efectiva; **no utilizar la base de datos de desarrollo o producción para los tests**.

## Notas de diseño

- Las validaciones de entrada se centralizan en **Form Requests**.
- Eloquent gestiona las relaciones entre productos, monedas y precios adicionales.
- La existencia de un recurso se resuelve con `findOrFail()` (404); los datos inválidos devuelven 422.
- Al eliminar un producto, MySQL elimina sus precios adicionales mediante una clave foránea con `ON DELETE CASCADE`.
