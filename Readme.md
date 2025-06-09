# MiniBasket Pro - Sistema de Gestión de Baloncesto

## 🏀 Descripción
Sistema completo de gestión para clubes de baloncesto que permite administrar equipos, jugadores, entrenadores y más.

## ✨ Características Principales

### 🎨 **Personalización Visual**
- Extracción automática de colores del logotipo del club
- Interfaz adaptativa que refleja la identidad visual del club
- Diseño responsive para todos los dispositivos

### 👥 **Gestión de Usuarios**
- **Administradores**: Control total del sistema
- **Entrenadores**: Gestión de sus equipos y jugadores
- Sistema de autenticación seguro

### 🏀 **Gestión Deportiva**
- Administración de equipos por temporadas
- Gestión completa de jugadores (datos personales, posiciones, números)
- Asignación de entrenadores a equipos
- Categorías de edad configurables

### 📊 **Dashboard Intuitivo**
- Paneles personalizados según el rol del usuario
- Estadísticas rápidas y métricas importantes
- Navegación intuitiva y acceso rápido a funciones

## 🚀 **Instalación**

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensión GD de PHP (para procesamiento de imágenes)

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   \`\`\`bash
   # Colocar los archivos en tu servidor web
   \`\`\`

2. **Configurar la base de datos**
   \`\`\`bash
   # Ejecutar el script de creación de base de datos
   mysql -u root -p < crear_base_datos.sql
   \`\`\`

3. **Configurar conexión**
   - Editar `config/db.php` con tus credenciales de base de datos

4. **Configurar permisos**
   \`\`\`bash
   chmod 755 uploads/
   chmod 644 config/
   \`\`\`

5. **Acceder al sistema**
   - Navegar a tu dominio/carpeta del proyecto
   - El sistema redirigirá automáticamente al login

## 👤 **Usuarios por Defecto**

### Administrador
- **Usuario**: `admin`
- **Contraseña**: `password`

### Entrenador
- **Usuario**: `entrenador1`
- **Contraseña**: `password`

> ⚠️ **Importante**: Cambiar estas contraseñas en producción

## 📁 **Estructura del Proyecto**

\`\`\`
minibasket-pro/
├── index.php                 # Página principal (login automático)
├── login.php                 # Login alternativo
├── logout.php                # Cerrar sesión
├── .htaccess                 # Configuración Apache
├── admin/                    # Panel de administración
│   ├── dashboard.php
│   ├── gestionar_club.php
│   ├── gestionar_entrenadores.php
│   ├── gestionar_equipos.php
│   └── gestionar_jugadores.php
├── entrenador/               # Panel de entrenadores
│   ├── dashboard.php
│   └── gestionar_jugadores.php
├── config/                   # Configuración
│   ├── db.php               # Conexión base de datos
│   └── database.php         # Clase Database (PDO)
├── utils/                    # Utilidades
│   ├── ClubConfig.php       # Configuración del club
│   └── ColorExtractor.php   # Extracción de colores
├── includes/                 # Archivos incluidos
│   └── club_header.php      # Header común
├── uploads/                  # Archivos subidos
└── models/                   # Modelos de datos
    ├── Club.php
    ├── Entrenador.php
    └── Equipo.php
\`\`\`

## 🔧 **Configuración**

### Base de Datos
Editar `config/db.php`:
\`\`\`php
$servername = "localhost";
$username = "tu_usuario";
$password = "tu_contraseña";
$dbname = "minibasket_pro";
\`\`\`

### Personalización del Club
1. Acceder como administrador
2. Ir a "Gestionar Club"
3. Subir logotipo (los colores se extraerán automáticamente)
4. Configurar nombre del club

## 🎨 **Sistema de Colores**

El sistema extrae automáticamente los colores dominantes del logotipo del club y los aplica a toda la interfaz:

- **Color Primario**: Color principal del club
- **Color Secundario**: Color complementario
- **Color Acento**: Color para destacar elementos

## 📱 **Funcionalidades por Rol**

### 👨‍💼 **Administrador**
- ✅ Gestión completa del club
- ✅ Administración de entrenadores
- ✅ Gestión de equipos y temporadas
- ✅ Administración de todos los jugadores
- ✅ Configuración del sistema
- ✅ Reportes y estadísticas

### 👨‍🏫 **Entrenador**
- ✅ Gestión de jugadores de sus equipos
- ✅ Visualización de sus equipos asignados
- ✅ Acceso a estadísticas de sus jugadores
- 🔄 Planificación de entrenamientos (próximamente)
- 🔄 Gestión de partidos (próximamente)

## 🔄 **Próximas Funcionalidades**

- 📊 Sistema de estadísticas avanzadas
- 🏀 Gestión de partidos y resultados
- 📅 Calendario de entrenamientos
- 👨‍👩‍👧‍👦 Gestión de padres/tutores
- 🏆 Sistema de torneos
- 📧 Notificaciones y comunicados
- 📄 Reportes en PDF
- 📱 App móvil

## 🛠️ **Soporte Técnico**

### Archivos de Diagnóstico
- `test_database.php` - Verificar conexión BD
- `test_color_extraction.php` - Probar extracción de colores
- `login_debug_corregido.php` - Debug del login

### Logs de Error
Los errores se registran en el log de PHP del servidor.

## 📄 **Licencia**
Este proyecto está desarrollado para uso educativo y comercial.

## 🤝 **Contribuciones**
Las contribuciones son bienvenidas. Por favor, crear un issue antes de enviar pull requests.

---

**Desarrollado con ❤️ para la comunidad del baloncesto**
