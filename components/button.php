<?php
// components/button.php
function renderButton($props = []) {
    $defaults = [
        'text' => 'Button',
        'type' => 'button',
        'variant' => 'primary',
        'classes' => '',
        'onclick' => ''
    ];
    
    $props = array_merge($defaults, $props);
    
    $variants = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow-md',
        'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white'
    ];
    
    $class = 'w-full py-3 px-4 rounded-lg font-semibold transition duration-200 transform hover:scale-[1.02] ' . $variants[$props['variant']] . ' ' . $props['classes'];
    
    return '
    <button 
        type="' . $props['type'] . '" 
        class="' . $class . '"
        onclick="' . $props['onclick'] . '"
    >
        ' . $props['text'] . '
    </button>
    ';
}
?>