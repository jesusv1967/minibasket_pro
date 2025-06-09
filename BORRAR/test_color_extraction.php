<?php
// Archivo para probar la extracción de colores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'utils/ColorExtractor.php';

echo "<h2>Prueba de Extracción de Colores del Logotipo</h2>";

try {
    // USAR LA MISMA CONFIGURACIÓN QUE FUNCIONA
    $servername = "localhost";
    $db_username = "root";
    $db_password = "1nt3l3kSYS%";
    $dbname = "minibasket_pro";
    
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Verificar si las columnas de colores existen
    echo "<h3>1. Verificando estructura de la tabla club:</h3>";
    $result = $conn->query("DESCRIBE club");
    $columnas_existentes = [];
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f5f5f5;'><th>Campo</th><th>Tipo</th><th>Por defecto</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $columnas_existentes[] = $row['Field'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar si existen las columnas de colores
    $columnas_colores = ['color_primario', 'color_secundario', 'color_acento', 'colores_extraidos'];
    $columnas_faltantes = array_diff($columnas_colores, $columnas_existentes);
    
    if (!empty($columnas_faltantes)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3 style='color: #856404; margin: 0 0 10px 0;'>⚠ Columnas de colores faltantes</h3>";
        echo "<p style='margin: 0 0 10px 0;'>Las siguientes columnas no existen en la tabla club:</p>";
        echo "<ul style='margin: 0 0 15px 20px;'>";
        foreach ($columnas_faltantes as $columna) {
            echo "<li>" . htmlspecialchars($columna) . "</li>";
        }
        echo "</ul>";
        echo "<p style='margin: 0;'><strong>Solución:</strong> Ejecuta el archivo <a href='ejecutar_actualizacion_club.php' style='color: #007cba;'>ejecutar_actualizacion_club.php</a> para añadir estas columnas.</p>";
        echo "</div>";
        
        // No continuar si faltan columnas críticas
        if (in_array('color_primario', $columnas_faltantes)) {
            echo "<p style='color: red;'>No se puede continuar sin las columnas de colores. Por favor, ejecuta la actualización primero.</p>";
            echo "<div style='margin-top: 30px;'>";
            echo "<a href='ejecutar_actualizacion_club.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>";
            echo "🔧 Ejecutar Actualización";
            echo "</a>";
            echo "</div>";
            exit;
        }
    } else {
        echo "<p style='color: green;'>✓ Todas las columnas de colores existen</p>";
    }
    
    echo "<h3>2. Buscando logotipo del club:</h3>";
    
    // Obtener el logotipo actual del club
    $stmt = $conn->prepare("SELECT logotipo FROM club LIMIT 1");
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $club_data = $result->fetch_assoc();
        $logotipo = $club_data['logotipo'];
        
        if ($logotipo && file_exists('uploads/' . $logotipo)) {
            echo "<p style='color: green;'>✓ Logotipo encontrado: " . htmlspecialchars($logotipo) . "</p>";
            
            // Mostrar el logotipo
            echo "<div style='margin: 20px 0;'>";
            echo "<img src='uploads/" . htmlspecialchars($logotipo) . "' alt='Logotipo' style='max-width: 300px; border: 1px solid #ddd; padding: 10px; border-radius: 8px;'>";
            echo "</div>";
            
            echo "<h3>3. Extrayendo colores...</h3>";
            
            try {
                // Extraer colores
                $colores_dominantes = ColorExtractor::extractColors('uploads/' . $logotipo, 5);
                
                echo "<h4>Colores dominantes encontrados:</h4>";
                echo "<div style='display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;'>";
                foreach ($colores_dominantes as $i => $color) {
                    echo "<div style='text-align: center;'>";
                    echo "<div style='width: 60px; height: 60px; background-color: $color; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 5px; border-radius: 8px;'></div>";
                    echo "<small style='font-size: 0.75rem;'>" . htmlspecialchars($color) . "</small>";
                    echo "</div>";
                }
                echo "</div>";
                
                // Seleccionar los mejores colores
                $colores_seleccionados = ColorExtractor::selectBestColors($colores_dominantes);
                
                echo "<h4>Colores seleccionados para la interfaz:</h4>";
                echo "<div style='display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;'>";
                
                foreach ($colores_seleccionados as $tipo => $color) {
                    echo "<div style='text-align: center;'>";
                    echo "<div style='width: 80px; height: 80px; background-color: $color; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 10px; border-radius: 8px;'></div>";
                    echo "<strong>" . ucfirst($tipo) . "</strong><br>";
                    echo "<small style='font-size: 0.875rem;'>" . htmlspecialchars($color) . "</small>";
                    echo "</div>";
                }
                echo "</div>";
                
                // Generar variaciones del color primario
                echo "<h4>Variaciones del color primario:</h4>";
                $variaciones = ColorExtractor::generateColorVariations($colores_seleccionados['primario']);
                
                echo "<div style='display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;'>";
                foreach ($variaciones as $nombre => $color) {
                    echo "<div style='text-align: center;'>";
                    echo "<div style='width: 50px; height: 50px; background-color: $color; border: 1px solid #ddd; margin-bottom: 5px; border-radius: 4px;'></div>";
                    echo "<small style='font-size: 0.75rem;'>" . htmlspecialchars($nombre) . "<br>" . htmlspecialchars($color) . "</small>";
                    echo "</div>";
                }
                echo "</div>";
                
                // Mostrar formulario para aplicar colores solo si las columnas existen
                if (empty($columnas_faltantes)) {
                    echo "<div style='margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;'>";
                    echo "<h4>Aplicar colores al club:</h4>";
                    echo "<form method='POST'>";
                    echo "<input type='hidden' name='aplicar_colores' value='1'>";
                    echo "<input type='hidden' name='color_primario' value='" . htmlspecialchars($colores_seleccionados['primario']) . "'>";
                    echo "<input type='hidden' name='color_secundario' value='" . htmlspecialchars($colores_seleccionados['secundario']) . "'>";
                    echo "<input type='hidden' name='color_acento' value='" . htmlspecialchars($colores_seleccionados['acento']) . "'>";
                    echo "<button type='submit' style='background: " . htmlspecialchars($colores_seleccionados['primario']) . "; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>";
                    echo "🎨 Aplicar estos colores al club";
                    echo "</button>";
                    echo "</form>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div style='background: #fee2e2; border: 1px solid #f87171; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #dc2626; margin: 0 0 10px 0;'>Error al extraer colores</h4>";
                echo "<p style='margin: 0; color: #7f1d1d;'>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
                
                // Verificar extensión GD
                if (!extension_loaded('gd')) {
                    echo "<div style='background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
                    echo "<h4 style='color: #92400e; margin: 0 0 10px 0;'>Extensión GD no disponible</h4>";
                    echo "<p style='margin: 0; color: #78350f;'>La extensión GD de PHP es necesaria para procesar imágenes. Contacta con tu administrador de sistema.</p>";
                    echo "</div>";
                }
            }
            
        } else {
            echo "<p style='color: orange;'>⚠ No se encontró el logotipo o el archivo no existe.</p>";
            if ($logotipo) {
                echo "<p>Archivo esperado: uploads/" . htmlspecialchars($logotipo) . "</p>";
                echo "<p>¿Existe el archivo? " . (file_exists('uploads/' . $logotipo) ? 'Sí' : 'No') . "</p>";
            }
            echo "<p>Sube un logotipo desde la <a href='admin/gestionar_club.php' style='color: #007cba;'>gestión del club</a> para probar la extracción de colores.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ No hay datos del club en la base de datos.</p>";
        echo "<p>Crea los datos del club desde la <a href='admin/gestionar_club.php' style='color: #007cba;'>gestión del club</a>.</p>";
    }
    
    // Procesar aplicación de colores (solo si las columnas existen)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aplicar_colores']) && empty($columnas_faltantes)) {
        echo "<h3>4. Aplicando colores al club:</h3>";
        
        $color_primario = $_POST['color_primario'];
        $color_secundario = $_POST['color_secundario'];
        $color_acento = $_POST['color_acento'];
        
        // Verificar que la consulta se puede preparar
        $sql = "UPDATE club SET color_primario = ?, color_secundario = ?, color_acento = ?, colores_extraidos = 1";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta de actualización: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $color_primario, $color_secundario, $color_acento);
        
        if ($stmt->execute()) {
            echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #2e7d32; margin: 0 0 10px 0;'>✓ Colores aplicados correctamente</h4>";
            echo "<p style='margin: 0;'>Los colores del club han sido actualizados:</p>";
            echo "<ul style='margin: 10px 0 0 20px;'>";
            echo "<li>Primario: <span style='background: $color_primario; color: white; padding: 2px 8px; border-radius: 4px;'>$color_primario</span></li>";
            echo "<li>Secundario: <span style='background: $color_secundario; color: white; padding: 2px 8px; border-radius: 4px;'>$color_secundario</span></li>";
            echo "<li>Acento: <span style='background: $color_acento; color: white; padding: 2px 8px; border-radius: 4px;'>$color_acento</span></li>";
            echo "</ul>";
            echo "<p style='margin: 10px 0 0 0;'><strong>Recarga la página para ver los cambios aplicados.</strong></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #c62828; margin: 0 0 10px 0;'>✗ Error al aplicar colores</h4>";
            echo "<p style='margin: 0;'>" . htmlspecialchars($stmt->error) . "</p>";
            echo "</div>";
        }
        
        $stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3 style='color: #c62828; margin: 0 0 10px 0;'>✗ Error</h3>";
    echo "<p style='margin: 0;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin/gestionar_club.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>";
echo "🏀 Ir a Gestionar Club";
echo "</a>";
echo "<a href='admin/dashboard.php' style='background: #16a34a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>";
echo "📊 Ir al Dashboard";
echo "</a>";
echo "<a href='ejecutar_actualizacion_club.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
echo "🔧 Actualizar Base de Datos";
echo "</a>";
echo "</div>";
?>
