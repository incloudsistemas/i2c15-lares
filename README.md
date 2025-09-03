<p align="center">
  <a href="https://incloudsistemas.com.br" target="_blank">
    <img src="https://github.com/incloudsistemas/i2c15-lares/blob/main/public/images/app-logo-large.png" alt="The i2c | InCloud skeleton engine application logo.">
  </a>
</p>

# i2c15 - Lares | Real Estate Hub - Website (CMS), CRM, Financial and Marketing Automation. 

Lares is a modular and scalable real estate management hub built on the i2c15 starter kit. Designed for agencies seeking a unified platform, it combines a fully customizable CMS-based website, a robust CRM for managing clients, properties, and workflows, integrated financial control (for commissions and expenses), and marketing automation tools to capture leads and nurture opportunities. Built with Laravel 12, Filament 3, and the TALL Stack.

## Requirements

-   **Operating System**: Windows, macOS, or Linux
-   **Web Server**: Apache or Nginx
-   **PHP**: 8.2+
-   **Node.js**: 18+ (LTS recommended)
-   **Composer**: 2+
-   **Database**: MySQL 8+

## Installation

### 1. Clone the repository and navigate into it

```bash
git clone https://github.com/incloudsistemas/i2c15-lares.git
cd i2c15-lares
```

### 2. Install backend dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Configure environment variables and generate app key

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Create a MySQL database and update `.env` with your database credentials

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=landlord_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 7. Creating test tenants and users (Example using Tinker)

```bash
php artisan tinker

$tenant1 = App\Models\System\Tenant::create(['id' => 'foo']);
$tenant1->account()->create(['name' => 'Foo']);
$tenant1->domains()->create(['domain' => 'foo.i2c.local']);

$tenant2 = App\Models\System\Tenant::create(['id' => 'bar']);
$tenant2->account()->create(['name' => 'Bar']);
$tenant2->domains()->create(['domain' => 'bar.i2c.local']);
```

### 8. For local development, map your domains in your hosts file:

```bash
127.0.0.1 admin.i2c.local
127.0.0.1 foo.i2c.local
127.0.0.1 bar.i2c.local

Linux and macOS => /etc/hosts
Windows => C:\Windows\System32\drivers\etc\hosts
```

### 9. Start development server

```bash
php artisan serve --host=0.0.0.0 --port=8000

Central Admin: http://admin.i2c.local:8000
Tenant  Foo: http://foo.i2c.local:8000
Tenant  Bar: http://bar.i2c.local:8000
```

### 10. Build frontend assets

```bash
npm run dev
```

## Security Vulnerabilities

If you discover a security vulnerability within InCloudCodile15, please report it by emailing Vinícius C. Lemos at [contato@incloudsistemas.com.br](mailto:contato@incloudsistemas.com.br). We take security seriously and will address all vulnerabilities promptly.

## License

InCloudCodile15 is an open-source project licensed under the [MIT license](https://opensource.org/licenses/MIT).
