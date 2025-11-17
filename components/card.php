<?php
// components/card.php
function renderCard($props = []) {
    $defaults = [
        'title' => '',
        'content' => '',
        'footer' => '',
        'classes' => ''
    ];
    
    $props = array_merge($defaults, $props);
    
    return '
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 ' . $props['classes'] . '">
        ' . ($props['title'] ? '
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">' . $props['title'] . '</h3>
        </div>' : '') . '
        
        <div class="p-6">' . $props['content'] . '</div>
        
        ' . ($props['footer'] ? '
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">' . $props['footer'] . '</div>' : '') . '
    </div>
    ';
}
?>