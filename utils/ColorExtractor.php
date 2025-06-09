<?php
class ColorExtractor {
    
    /**
     * Extrae los colores dominantes de una imagen
     */
    public static function extractColors($imagePath, $numColors = 3) {
        if (!file_exists($imagePath)) {
            throw new Exception("La imagen no existe: " . $imagePath);
        }
        
        // Verificar que GD esté disponible
        if (!extension_loaded('gd')) {
            throw new Exception("La extensión GD no está disponible");
        }
        
        // Cargar la imagen según su tipo
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                throw new Exception("Tipo de imagen no soportado: " . $mimeType);
        }
        
        if (!$image) {
            throw new Exception("No se pudo cargar la imagen");
        }
        
        // Redimensionar la imagen para procesar menos píxeles (más rápido)
        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = 150;
        $newHeight = intval($height * ($newWidth / $width));
        
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Extraer colores
        $colors = [];
        
        for ($x = 0; $x < $newWidth; $x += 2) {
            for ($y = 0; $y < $newHeight; $y += 2) {
                $rgb = imagecolorat($resizedImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Ignorar colores muy claros o muy oscuros
                $brightness = ($r + $g + $b) / 3;
                if ($brightness > 240 || $brightness < 15) {
                    continue;
                }
                
                $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
                
                if (isset($colors[$hex])) {
                    $colors[$hex]++;
                } else {
                    $colors[$hex] = 1;
                }
            }
        }
        
        // Limpiar memoria
        imagedestroy($image);
        imagedestroy($resizedImage);
        
        // Ordenar por frecuencia
        arsort($colors);
        
        // Obtener los colores más dominantes
        $dominantColors = array_slice(array_keys($colors), 0, $numColors);
        
        // Si no hay suficientes colores, usar colores por defecto
        while (count($dominantColors) < $numColors) {
            $defaultColors = ['#f97316', '#2563eb', '#16a34a', '#9333ea', '#dc2626'];
            $dominantColors[] = $defaultColors[count($dominantColors) % count($defaultColors)];
        }
        
        return $dominantColors;
    }
    
    /**
     * Convierte un color RGB a HSL para mejor análisis
     */
    public static function rgbToHsl($r, $g, $b) {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $diff = $max - $min;
        
        // Luminosidad
        $l = ($max + $min) / 2;
        
        if ($diff == 0) {
            $h = $s = 0; // Gris
        } else {
            // Saturación
            $s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);
            
            // Matiz
            switch ($max) {
                case $r:
                    $h = (($g - $b) / $diff) + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = (($b - $r) / $diff) + 2;
                    break;
                case $b:
                    $h = (($r - $g) / $diff) + 4;
                    break;
            }
            $h /= 6;
        }
        
        return [$h * 360, $s * 100, $l * 100];
    }
    
    /**
     * Selecciona los mejores colores para la interfaz
     */
    public static function selectBestColors($colors) {
        $result = [
            'primario' => $colors[0] ?? '#f97316',
            'secundario' => '#2563eb', // Por defecto
            'acento' => '#16a34a' // Por defecto
        ];
        
        // Intentar encontrar colores complementarios
        if (count($colors) >= 2) {
            $result['secundario'] = $colors[1];
        }
        
        if (count($colors) >= 3) {
            $result['acento'] = $colors[2];
        }
        
        return $result;
    }
    
    /**
     * Genera variaciones de un color (más claro, más oscuro)
     */
    public static function generateColorVariations($hexColor) {
        // Convertir hex a RGB
        $hex = ltrim($hexColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Generar variaciones
        $variations = [];
        
        // Más claro (50, 100, 200, 300)
        for ($i = 1; $i <= 4; $i++) {
            $factor = 1 + ($i * 0.15);
            $newR = min(255, intval($r * $factor));
            $newG = min(255, intval($g * $factor));
            $newB = min(255, intval($b * $factor));
            $variations["light-{$i}"] = sprintf("#%02x%02x%02x", $newR, $newG, $newB);
        }
        
        // Más oscuro (600, 700, 800, 900)
        for ($i = 1; $i <= 4; $i++) {
            $factor = 1 - ($i * 0.15);
            $newR = max(0, intval($r * $factor));
            $newG = max(0, intval($g * $factor));
            $newB = max(0, intval($b * $factor));
            $variations["dark-{$i}"] = sprintf("#%02x%02x%02x", $newR, $newG, $newB);
        }
        
        return $variations;
    }
}
?>
