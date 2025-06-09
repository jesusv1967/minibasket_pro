
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title : 'MiniBasket Pro'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: <?php echo $clubConfig->getColorPrimario(); ?>;
            --color-secundario: <?php echo $clubConfig->getColorSecundario(); ?>;
            --color-acento: <?php echo $clubConfig->getColorAcento(); ?>;
        }
        
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f9fafb;
        }
        
        /* Aplicar colores del club */
        .bg-club-gradient {
            background: linear-gradient(to right, var(--color-primario), var(--color-secundario));
        }
        
        .bg-club-primario {
            background-color: var(--color-primario);
        }
        
        .bg-club-secundario {
            background-color: var(--color-secundario);
        }
        
        .bg-club-acento {
            background-color: var(--color-acento);
        }
        
        .text-club-primario {
            color: var(--color-primario);
        }
        
        .text-club-secundario {
            color: var(--color-secundario);
        }
        
        .text-club-acento {
            color: var(--color-acento);
        }
        
        .border-club-primario {
            border-color: var(--color-primario);
        }
        
        .focus\:ring-club-primario:focus {
            --tw-ring-color: var(--color-primario);
        }
        
        .focus\:border-club-primario:focus {
            border-color: var(--color-primario);
        }
        
        /* Fallback CSS en caso de que Tailwind no cargue */
        .fallback-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .fallback-header { background: linear-gradient(to right, var(--color-primario), var(--color-secundario)); color: white; padding: 20px; margin-bottom: 20px; }
        .fallback-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .fallback-btn { background: var(--color-primario); color: white; padding: 10px 20px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin-right: 10px; }
        .fallback-btn-secondary { background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; }
        .fallback-alert-success { background: #dcfce7; border-left: 4px solid var(--color-acento); color: #166534; padding: 15px; margin-bottom: 20px; }
        .fallback-alert-error { background: #fee2e2; border-left: 4px solid #dc2626; color: #991b1b; padding: 15px; margin-bottom: 20px; }
        .fallback-form-group { margin-bottom: 20px; }
        .fallback-label { display: block; font-weight: 500; margin-bottom: 5px; }
        .fallback-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .fallback-file-input { padding: 10px 0; }
        .fallback-help-text { font-size: 0.875rem; color: #6b7280; margin-top: 5px; }
        .fallback-img-preview { max-width: 200px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; }
        .fallback-grid { display: grid; gap: 20px; }
        
        /* Mejoras para responsividad */
        @media (max-width: 768px) {
            .fallback-container {
                padding: 10px;
            }
            
            .fallback-card {
                padding: 15px;
            }
            
            .fallback-header {
                padding: 15px;
            }
            
            .fallback-btn, .fallback-btn-secondary {
                display: block;
                width: 100%;
                margin: 10px 0;
                text-align: center;
            }
            
            .hidden.sm\:inline {
                display: inline !important;
            }
        }
        
        /* Mejoras para dispositivos táctiles */
        @media (hover: none) {
            .fallback-btn, .fallback-btn-secondary {
                padding: 12px 20px; /* Botones más grandes para tocar */
            }
            
            input, select, textarea {
                font-size: 16px !important; /* Evitar zoom en iOS */
            }
        }
    </style>
</head>
<body>
