<?php

// create_admin_hash.php (¡Elimina o asegura este archivo después de usarlo!)

$username = 'entrenador'; // Elige tu nombre de usuario administrador
$password = '4dm1n'; // ¡CAMBIA ESTO por una contraseña fuerte\!

$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Nombre de Usuario: " . htmlspecialchars($username) . "<br>";
echo "Contraseña Hasheada: " . htmlspecialchars($password_hash) . "<br>";
echo "Copia la contraseña hasheada y úsala en la siguiente sentencia SQL para insertar tu administrador:<br>";
echo "INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES ('" . htmlspecialchars($username) . "', '" . htmlspecialchars($password_hash) . "', 'entrenador');";

// ¡IMPORTANTE\! Una vez que hayas ejecutado este script y copiado el hash,
// elimina este archivo de tu servidor o asegúralo para que no sea accesible públicamente.


?>