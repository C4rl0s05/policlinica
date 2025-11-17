<?php
// components/logo.php
function renderLogo($size = 'medium', $showText = false) {
    $sizes = [
        'small' => 'w-10 h-10',
        'medium' => 'w-16 h-16',
        'large' => 'w-24 h-24',
        'xlarge' => 'w-32 h-32'
    ];
    
    $textSizes = [
        'small' => 'text-sm',
        'medium' => 'text-lg',
        'large' => 'text-xl',
        'xlarge' => 'text-2xl'
    ];
    
    // Ruta del logo - ajusta según donde esté tu imagen
    $logoPath = 'assets/images/logo-clinica.png';
    $logoExists = file_exists($logoPath);
    
    if ($logoExists) {
        $logoContent = '
        <div class="flex justify-center mb-4">
            <img src="' . $logoPath . '" 
                 alt="Policlínicas San Marcos" 
                 class="' . $sizes[$size] . ' object-contain">
        </div>
        ';
    } else {
        $logoContent = '
        <div class="flex justify-center mb-4">
            <div class="' . $sizes[$size] . ' bg-blue-600 rounded-full flex items-center justify-center text-white font-bold ' . $textSizes[$size] . '">
                <span>PSM</span>
            </div>
        </div>
        ';
    }
    
    if ($showText) {
        return '
        <div class="text-center">
            ' . $logoContent . '
            <h1 class="' . $textSizes[$size] . ' font-bold text-gray-800">Policlínicas San Marcos</h1>
            <p class="text-gray-600 text-sm">Sistema de Gestión de Citas</p>
        </div>
        ';
    }
    
    return $logoContent;
}
?>