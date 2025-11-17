<?php
// components/stats-card.php
function renderStatsCard($props = []) {
    $defaults = [
        'title' => '',
        'value' => '0',
        'subtitle' => '',
        'icon' => 'fas fa-chart-bar',
        'color' => 'blue',
        'progress' => 0
    ];
    
    $props = array_merge($defaults, $props);
    
    $colors = [
        'blue' => 'bg-blue-100 text-blue-600',
        'green' => 'bg-green-100 text-green-600',
        'purple' => 'bg-purple-100 text-purple-600',
        'orange' => 'bg-orange-100 text-orange-600'
    ];
    
    return '
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">' . $props['title'] . '</p>
                <p class="text-2xl font-bold text-gray-900">' . $props['value'] . '</p>
                <p class="text-sm text-gray-500">' . $props['subtitle'] . '</p>
            </div>
            <div class="p-3 ' . $colors[$props['color']] . ' rounded-lg">
                <i class="' . $props['icon'] . ' text-xl"></i>
            </div>
        </div>
        ' . ($props['progress'] > 0 ? '
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="' . str_replace('100', '600', $colors[$props['color']]) . ' h-2 rounded-full" style="width: ' . $props['progress'] . '%"></div>
            </div>
        </div>' : '') . '
    </div>
    ';
}
?>