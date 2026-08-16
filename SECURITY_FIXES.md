# Correcciones aplicadas

- Se eliminaron las rutas y métodos de depuración expuestos.
- Se eliminó el endpoint público de limpieza de caché.
- Se protegió todo el dashboard con `DASHBOARD_ACCESS_TOKEN`.
- La importación ahora reemplaza datos dentro de una transacción y revierte cualquier fallo.
- Se rechazan importaciones sin registros válidos para evitar reemplazos vacíos.
- `APP_DEBUG` quedó desactivado y el paquete no incluye `.env` ni archivos generados de depuración.

Para iniciar: copia `.env.example` a `.env`, define `DASHBOARD_ACCESS_TOKEN` con una clave aleatoria y ejecuta `php artisan key:generate`.
