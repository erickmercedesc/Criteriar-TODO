# SecondBrain

Aplicación web personal de productividad (TODO + Puntuación + Pomodoro).
Construida con Laravel 12 + Inertia.js + Vue.js.

## Requisitos Previos

- PHP 8.2+
- Composer
- Node.js y npm
- MySQL / MariaDB (o SQLite si lo configuras)

## Instalación y Configuración

1. **Clonar y configurar dependencias:**
   ```bash
   composer install
   npm install
   ```

2. **Configurar el entorno (`.env`):**
   Copia el `.env.example` a `.env` y configura tu base de datos.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Base de Datos y Migraciones:**
   ```bash
   php artisan migrate
   ```

## Notificaciones Web Push y Colas (Background Jobs)

La aplicación utiliza notificaciones Web Push para avisar cuando termina un Pomodoro o Break, incluso si el celular/PC está bloqueado o la PWA no está activa. Para que esto funcione, se requiere la librería `laravel-notification-channels/webpush` y habilitar el sistema de Colas (Jobs) de Laravel.

### 1. Instalar librería de Web Push
Asegúrate de instalar la librería si aún no lo has hecho:
```bash
composer require laravel-notification-channels/webpush
```

### 2. Generar Claves VAPID
Ejecuta el siguiente comando para generar las llaves criptográficas VAPID necesarias para firmar las notificaciones push (esto agregará las llaves `VAPID_PUBLIC_KEY` y `VAPID_PRIVATE_KEY` a tu archivo `.env`):
```bash
php artisan webpush:vapid
```

### 3. Configurar las Colas en el `.env`
Para que los jobs de notificaciones se procesen en segundo plano con el tiempo exacto de retraso (Delayed Jobs), asegúrate de que tu `.env` tenga el driver de colas en `database` (o `redis` si prefieres):
```env
QUEUE_CONNECTION=database
```

### 4. Lanzar la aplicación y el Worker de Colas

Para correr la app localmente, necesitas 3 terminales (o procesos):

**Terminal 1: Servidor PHP**
```bash
php artisan serve
```

**Terminal 2: Compilación de Frontend (Vite)**
```bash
npm run dev
```

**Terminal 3: Worker de Colas (Obligatorio para los Web Push)**
Para que las notificaciones programadas se envíen al terminar los temporizadores, debes tener corriendo el proceso de colas:
```bash
php artisan queue:work
```

> **Nota:** Si las notificaciones no llegan cuando el timer llega a 0, revisa la consola del Worker (`php artisan queue:work`) para ver si el Job falló o si simplemente el worker no estaba encendido.
