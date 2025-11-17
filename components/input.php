<?php
// components/input.php
function renderInput($props = []) {
    $defaults = [
        'label' => '',
        'name' => '',
        'type' => 'text',
        'placeholder' => '',
        'value' => '',
        'required' => false,
        'classes' => '',
        'icon' => ''
    ];
    
    $props = array_merge($defaults, $props);
    
    $requiredAttr = $props['required'] ? 'required' : '';
    $iconHtml = $props['icon'] ? '<i class="' . $props['icon'] . ' text-gray-400"></i>' : '';
    
    return '
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2" for="' . $props['name'] . '">
            ' . $props['label'] . '
        </label>
        <div class="relative">
            ' . ($props['icon'] ? '
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                ' . $iconHtml . '
            </div>
            ' : '') . '
            <input 
                type="' . $props['type'] . '" 
                id="' . $props['name'] . '" 
                name="' . $props['name'] . '" 
                value="' . htmlspecialchars($props['value']) . '" 
                placeholder="' . $props['placeholder'] . '" 
                ' . $requiredAttr . '
                class="w-full ' . ($props['icon'] ? 'pl-10 ' : '') . 'pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ' . $props['classes'] . '"
            >
        </div>
    </div>
    ';
}
?>