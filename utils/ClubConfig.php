<?php
class ClubConfig {
  private static $instance = null;
  private $conn = null;
  private $club_data = null;
  
  // Valores por defecto
  private $nombre = 'MiniBasket Pro';
  private $logotipo = '';
  private $color_primario = '#f97316'; // Naranja
  private $color_secundario = '#2563eb'; // Azul
  private $color_acento = '#16a34a'; // Verde
  private $colores_extraidos = false;
  
  private function __construct() {
      try {
          // Configuración de base de datos
          $servername = "localhost";
          $db_username = "root";
          $db_password = "1nt3l3kSYS%";
          $dbname = "minibasket_pro";
          
          // Crear conexión
          $this->conn = new mysqli($servername, $db_username, $db_password, $dbname);
          
          if ($this->conn->connect_error) {
              throw new Exception("Error de conexión: " . $this->conn->connect_error);
          }
          
          $this->conn->set_charset("utf8mb4");
          
          // Cargar datos del club
          $this->loadClubData();
          
      } catch (Exception $e) {
          error_log("Error en ClubConfig: " . $e->getMessage());
          // Usar valores por defecto si hay error
      }
  }
  
  public static function getInstance() {
      if (self::$instance == null) {
          self::$instance = new ClubConfig();
      }
      return self::$instance;
  }
  
  private function loadClubData() {
      try {
          if ($this->conn) {
              $stmt = $this->conn->prepare("SELECT id, nombre, logotipo, color_primario, color_secundario, color_acento, colores_extraidos FROM club LIMIT 1");
              $stmt->execute();
              $result = $stmt->get_result();
              
              if ($result->num_rows > 0) {
                  $this->club_data = $result->fetch_assoc();
                  $this->nombre = $this->club_data['nombre'];
                  $this->logotipo = $this->club_data['logotipo'];
                  $this->color_primario = $this->club_data['color_primario'];
                  $this->color_secundario = $this->club_data['color_secundario'];
                  $this->color_acento = $this->club_data['color_acento'];
                  $this->colores_extraidos = (bool)$this->club_data['colores_extraidos'];
              }
          }
      } catch (Exception $e) {
          error_log("Error cargando datos del club: " . $e->getMessage());
          // Mantener valores por defecto
      }
  }
  
  public function getNombre() {
      return $this->nombre;
  }
  
  public function getLogotipo() {
      return $this->logotipo;
  }
  
  public function getColorPrimario() {
      return $this->color_primario;
  }
  
  public function getColorSecundario() {
      return $this->color_secundario;
  }
  
  public function getColorAcento() {
      return $this->color_acento;
  }
  
  public function tieneColoresExtraidos() {
      return $this->colores_extraidos;
  }
  
  public function renderHeader($nombre_club, $titulo_pagina, $info_usuario, $logout_url) {
      echo '<header class="bg-club-gradient text-white shadow-lg fallback-header">';
      echo '    <div class="container mx-auto px-4 py-4 fallback-container">';
      echo '        <div class="flex flex-wrap justify-between items-center">';
      echo '            <div class="flex items-center mb-2 md:mb-0">';
      
      // Logo y nombre del club
      echo '                <div class="flex items-center">';
      if ($this->logotipo) {
          echo '                <div class="w-10 h-10 rounded-full overflow-hidden bg-white p-1 mr-3" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: white; padding: 4px; margin-right: 12px;">';
          echo '                    <img src="../uploads/' . htmlspecialchars($this->logotipo) . '" alt="Logo" class="w-full h-full object-contain" style="width: 100%; height: 100%; object-fit: contain;">';
          echo '                </div>';
      }
      echo '                    <div>';
      echo '                        <h1 class="text-lg font-bold" style="font-size: 1.125rem; font-weight: bold;">' . htmlspecialchars($nombre_club) . '</h1>';
      echo '                        <p class="text-xs opacity-90" style="font-size: 0.75rem; opacity: 0.9;">' . htmlspecialchars($titulo_pagina) . '</p>';
      echo '                    </div>';
      echo '                </div>';
      echo '            </div>';
      
      // Información de usuario y botón de logout
      echo '            <div class="flex items-center">';
      echo '                <span class="text-sm mr-4 hidden sm:inline" style="font-size: 0.875rem; margin-right: 16px; display: none;">' . htmlspecialchars($info_usuario) . '</span>';
      echo '                <a href="' . $logout_url . '" class="px-3 py-1 bg-white bg-opacity-20 hover:bg-opacity-30 rounded text-sm transition-all" style="padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 4px; font-size: 0.875rem;">Cerrar Sesión</a>';
      echo '            </div>';
      echo '        </div>';
      echo '    </div>';
      echo '</header>';
  }
}
?>
