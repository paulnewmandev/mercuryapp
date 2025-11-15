# MercuryApp

<div align="center">

![MercuryApp](https://img.shields.io/badge/MercuryApp-ERP-blue)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/license-MIT-green)

**Sistema ERP completo para gestión empresarial con facturación electrónica integrada**

[Características](#-características) • [Tecnologías](#-tecnologías) • [Instalación](#-instalación) • [Módulos](#-módulos) • [Documentación](#-documentación)

</div>

---

## 📋 Descripción

**MercuryApp** es un sistema ERP (Enterprise Resource Planning) completo desarrollado en Laravel, diseñado para gestionar todos los aspectos de una empresa: ventas, inventario, contabilidad, taller de servicios, clientes y más. Incluye integración nativa con el SRI (Servicio de Rentas Internas) de Ecuador para facturación electrónica.

### ¿Para qué sirve?

MercuryApp está diseñado para empresas que necesitan:

- ✅ **Gestión de ventas**: Punto de venta (POS), facturas, notas de venta, cotizaciones
- ✅ **Control de inventario**: Productos, bodegas, movimientos, proveedores
- ✅ **Gestión contable**: Cuentas por cobrar/pagar, ingresos, egresos
- ✅ **Taller de servicios**: Órdenes de trabajo, equipos, abonos
- ✅ **Facturación electrónica**: Integración directa con SRI Ecuador
- ✅ **Multi-sucursal**: Gestión de múltiples establecimientos
- ✅ **Multi-tenant**: Soporte para múltiples empresas en una sola instalación

---

## ✨ Características

### 🎯 Características Principales

- **🏢 Multi-tenant**: Sistema multi-empresa con aislamiento completo de datos
- **🌍 Multiidioma**: Soporte para Español e Inglés (gettext)
- **📱 Responsive**: Interfaz adaptativa para desktop, tablet y móvil
- **🔐 Seguridad**: Sistema de roles y permisos granular
- **📊 Dashboard**: Panel de control con métricas en tiempo real
- **🧾 Facturación Electrónica**: Integración directa con SRI Ecuador
- **📄 Generación de PDFs**: Facturas, tickets, etiquetas
- **🔍 Búsqueda avanzada**: Filtros y búsqueda en todas las tablas
- **📧 Notificaciones**: Sistema de notificaciones integrado
- **🔄 Auditoría**: Registro de cambios y timestamps

### 🧾 Facturación Electrónica (SRI Ecuador)

- Generación automática de XML según estándar SRI
- Firma digital con certificados .p12
- Envío y autorización directa con SRI
- Generación de PDF con formato oficial
- Envío automático de facturas por email
- Soporte para ambiente de pruebas y producción
- Código de barras y clave de acceso

---

## 🛠 Tecnologías

### Backend

- **Laravel 12.x** - Framework PHP
- **PHP 8.2+** - Lenguaje de programación
- **MySQL/MariaDB** - Base de datos
- **UUID** - Identificadores únicos universales

### Frontend

- **Tailwind CSS 4.x** - Framework CSS utility-first
- **Alpine.js 3.x** - Framework JavaScript ligero
- **Vite** - Build tool y dev server
- **Font Awesome 6** - Iconos
- **SweetAlert2** - Alertas y confirmaciones

### Librerías PHP

- **dompdf** - Generación de PDFs
- **picqer/php-barcode-generator** - Generación de códigos de barras
- **robrichards/xmlseclibs** - Firma digital XML

### Arquitectura

- **MVC** - Modelo-Vista-Controlador
- **Repository Pattern** - Abstracción de acceso a datos
- **Service Layer** - Lógica de negocio en servicios
- **Form Requests** - Validación de formularios
- **Blade Components** - Componentes reutilizables

---

## 📦 Módulos del Sistema

### 💰 Ventas
- **Punto de Venta (POS)**: Terminal de ventas con escáner de código de barras
- **Facturas**: Gestión completa de facturas electrónicas
- **Notas de Venta**: Documentos de venta sin facturación
- **Cotizaciones**: Creación y seguimiento de cotizaciones

### 🔧 Taller
- **Órdenes de Trabajo**: Gestión completa de servicios técnicos
- **Equipos**: Registro de equipos y dispositivos
- **Marcas y Modelos**: Catálogo de marcas y modelos
- **Estados**: Control de estados de órdenes
- **Abonos**: Gestión de pagos parciales
- **Tickets e Etiquetas**: Impresión de documentos

### 📦 Inventario
- **Productos**: Gestión completa de productos con SKU y códigos de barras
- **Bodegas**: Control multi-bodega
- **Movimientos**: Transferencias entre bodegas
- **Precios**: Listas de precios (POS, B2B, B2C)
- **Proveedores**: Gestión de proveedores

### 📊 Contabilidad
- **Cuentas por Cobrar**: Gestión de cuentas por cobrar
- **Cuentas por Pagar**: Gestión de cuentas por pagar
- **Ingresos**: Registro de ingresos
- **Egresos**: Registro de egresos
- **Ventas**: Vista consolidada de ventas

### 👥 Clientes
- **Clientes Naturales**: Gestión de personas naturales
- **Empresas**: Gestión de empresas
- **Categorías**: Clasificación de clientes

### 🗂 Catálogo
- **Líneas**: Líneas de productos
- **Categorías**: Categorías de productos
- **Subcategorías**: Subcategorías de productos

### 🛠 Servicios
- **Categorías de Servicios**: Clasificación de servicios
- **Servicios**: Catálogo de servicios

### 🔐 Seguridad
- **Usuarios**: Gestión de usuarios
- **Roles**: Sistema de roles
- **Permisos**: Control granular de permisos

### ⚙️ Configuración
- **Empresa**: Datos de la empresa y certificado digital
- **Sucursales**: Gestión de sucursales
- **Secuenciales**: Configuración de secuenciales de documentos
- **Tipos de Ingresos/Egresos**: Clasificación contable
- **Cuentas Bancarias**: Gestión de cuentas bancarias
- **Tarjetas**: Configuración de métodos de pago

---

## 📋 Requisitos

- **PHP**: 8.2 o superior
- **Composer**: 2.x
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior
- **MySQL/MariaDB**: 10.4 o superior
- **Extensiones PHP**:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD o Imagick (para generación de imágenes)

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/mercuryapp.git
cd mercuryapp
```

### 2. Instalar dependencias

```bash
# Dependencias PHP
composer install

# Dependencias Node.js
npm install
```

### 3. Configurar entorno

```bash
# Copiar archivo de entorno
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar base de datos

Editar `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mercuryapp
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Ejecutar migraciones

```bash
php artisan migrate --seed
```

### 6. Compilar assets

```bash
npm run build
```

### 7. Iniciar servidor

```bash
php artisan serve
```

La aplicación estará disponible en `http://localhost:8000`

---

## ⚙️ Configuración

### Facturación Electrónica (SRI Ecuador)

1. **Subir certificado .p12**:
   - Ir a Configuración → Empresa
   - Subir el archivo `.p12` del certificado digital
   - Ingresar la contraseña del certificado

2. **Configurar ambiente**:
   - Seleccionar "Desarrollo (Pruebas)" o "Producción"
   - Las URLs del SRI se configuran automáticamente

3. **Configurar secuenciales**:
   - Ir a Configuración → Secuenciales
   - Crear secuencial para tipo "FACTURA"
   - Configurar código de establecimiento y punto de emisión

### Variables de Entorno Importantes

```env
# Aplicación
APP_NAME="MercuryApp"
APP_URL=http://localhost:8000

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=mercuryapp

# Facturación Electrónica - URLs SRI
SRI_RECEPTION_URL_DEVELOPMENT=https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl
SRI_AUTHORIZATION_URL_DEVELOPMENT=https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl

SRI_RECEPTION_URL_PRODUCTION=https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl
SRI_AUTHORIZATION_URL_PRODUCTION=https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl

# Correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

---

## 📁 Estructura del Proyecto

```
mercuryapp/
├── app/
│   ├── Console/Commands/          # Comandos Artisan
│   ├── Http/
│   │   ├── Controllers/            # Controladores
│   │   ├── Middleware/             # Middleware
│   │   └── Requests/               # Form Requests (validación)
│   ├── Models/                     # Modelos Eloquent
│   ├── Services/                   # Servicios de negocio
│   └── View/Components/            # Componentes Blade
├── config/                         # Archivos de configuración
├── database/
│   ├── migrations/                 # Migraciones
│   └── seeders/                    # Seeders
├── locales/                        # Archivos de traducción (gettext)
│   ├── es/
│   └── en/
├── public/                         # Archivos públicos
├── resources/
│   ├── Css/                        # Estilos CSS
│   ├── Js/                         # JavaScript
│   └── Views/                      # Vistas Blade
├── routes/
│   └── web.php                     # Rutas web
└── storage/                        # Archivos de almacenamiento
```

---

## 🔧 Comandos Útiles

### Desarrollo

```bash
# Iniciar servidor de desarrollo con hot-reload
composer dev

# Compilar assets para producción
npm run build

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Rollback de migraciones
php artisan migrate:rollback

# Ejecutar seeders
php artisan db:seed
```

### Facturación Electrónica

```bash
# Generar documentos de prueba
php artisan invoice:generate-test-documents

# Actualizar números de factura al nuevo formato
php artisan invoice:update-numbers-new-format
```

---

## 🌍 Internacionalización

El sistema soporta múltiples idiomas usando gettext:

- **Español** (por defecto): `locales/es/LC_MESSAGES/`
- **Inglés**: `locales/en/LC_MESSAGES/`

Para agregar un nuevo idioma:

1. Crear carpeta `locales/{codigo}/LC_MESSAGES/`
2. Copiar archivo `.po` desde español
3. Traducir los mensajes
4. Compilar con `msgfmt`

---

## 🔒 Seguridad

- **Autenticación**: Sistema de autenticación Laravel
- **Autorización**: Roles y permisos granulares
- **CSRF Protection**: Protección CSRF en todos los formularios
- **SQL Injection**: Prevención mediante Eloquent ORM
- **XSS Protection**: Escapado automático en Blade
- **Multi-tenant**: Aislamiento de datos por empresa

---

## 📝 Convenciones

### Base de Datos

- **Primary Keys**: UUIDs en lugar de IDs incrementales
- **Timestamps**: `created_at` y `updated_at` en todas las tablas
- **Status**: Campo `status` con valores: `A` (Activo), `I` (Inactivo), `T` (Trash)
- **Soft Deletes**: No se usan, se usa campo `status`

### Código

- **PSR-12**: Estándar de codificación PHP
- **Laravel Conventions**: Convenciones de Laravel
- **Naming**: Inglés para código, español para UI

---

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Con cobertura
php artisan test --coverage
```

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo [LICENSE](LICENSE) para más detalles.

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📞 Soporte

Para soporte, por favor abre un [issue](https://github.com/tu-usuario/mercuryapp/issues) en GitHub.

---

## 🙏 Agradecimientos

- [Laravel](https://laravel.com) - Framework PHP
- [Tailwind CSS](https://tailwindcss.com) - Framework CSS
- [Alpine.js](https://alpinejs.dev) - Framework JavaScript
- [Font Awesome](https://fontawesome.com) - Iconos

---

<div align="center">

**Desarrollado con ❤️ usando Laravel**

[⬆ Volver arriba](#mercuryapp)

</div>
