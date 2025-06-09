<?php
session_start();

// Verificar si ya hay una sesión activa
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    // Usuario ya logueado, redirigir al dashboard correspondiente
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: entrenador/dashboard.php");
    }
    exit();
}

// Si no hay sesión activa, redirigir al login
header("Location: login.php");
exit();
?>
