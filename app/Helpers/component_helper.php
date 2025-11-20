<?php

/**
 * Component Helper
 *
 * Provides reusable UI component functions for consistent styling across the application.
 * All functions return class strings that can be easily customized or extended.
 */

if (!function_exists('ui_input')) {
    /**
     * Get classes for standard form inputs
     *
     * @param string $variant 'default', 'auth', 'search'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_input(string $variant = 'default', array $custom = []): string
    {
        $classes = [
            'default' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
            'auth' => 'appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200',
            'search' => 'w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
        ];

        $base = $classes[$variant] ?? $classes['default'];

        if (!empty($custom)) {
            $base .= ' ' . implode(' ', $custom);
        }

        return $base;
    }
}

if (!function_exists('ui_select')) {
    /**
     * Get classes for select/dropdown elements
     *
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_select(array $custom = []): string
    {
        $base = 'w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';
        if (!empty($custom)) {
            $base .= ' ' . implode(' ', $custom);
        }
        return $base;
    }
}

if (!function_exists('ui_button')) {
    /**
     * Get classes for buttons
     *
     * @param string $variant 'primary', 'secondary', 'success', 'danger', 'warning', 'ghost'
     * @param string $size 'sm', 'md', 'lg'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_button(string $variant = 'primary', string $size = 'md', array $custom = []): string
    {
        $bases = [
            'primary'   => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 text-white',
            'secondary' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500 text-white',
            'ghost' => 'bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 focus:ring-indigo-500',
        ];

        $sizes = [
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-6 py-3 text-base',
        ];

        $common = 'inline-flex items-center font-medium rounded-lg shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

        $classes = $common . ' ' . ($bases[$variant] ?? $bases['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);

        if (!empty($custom)) {
            $classes .= ' ' . implode(' ', $custom);
        }

        return $classes;
    }
}

if (!function_exists('ui_card')) {
    /**
     * Get classes for card containers
     *
     * @param string $variant 'default', 'elevated', 'bordered', 'flat'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_card(string $variant = 'default', array $custom = []): string
    {
        $classes = [
            'default' => 'bg-white rounded-lg shadow-sm border border-gray-200',
            'elevated' => 'bg-white rounded-xl shadow-md border border-gray-200',
            'bordered' => 'bg-white rounded-lg border-2 border-gray-300',
            'flat' => 'bg-white rounded-lg',
        ];

        $base = $classes[$variant] ?? $classes['default'];

        if (!empty($custom)) {
            $base .= ' ' . implode(' ', $custom);
        }

        return $base;
    }
}

if (!function_exists('ui_badge')) {
    /**
     * Get classes for badges/pills
     *
     * @param string $variant 'primary', 'secondary', 'success', 'danger', 'warning', 'info'
     * @param string $size 'sm', 'md', 'lg'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_badge(string $variant = 'primary', string $size = 'md', array $custom = []): string
    {
        $variants = [
            'primary' => 'bg-indigo-100 text-indigo-800',
            'secondary' => 'bg-gray-100 text-gray-800',
            'success' => 'bg-green-100 text-green-800',
            'danger' => 'bg-red-100 text-red-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'info' => 'bg-blue-100 text-blue-800',
        ];

        $sizes = [
            'sm' => 'px-2 py-0.5 text-xs',
            'md' => 'px-2.5 py-1 text-sm',
            'lg' => 'px-3 py-1.5 text-base',
        ];

        $common = 'inline-flex items-center font-medium rounded-full';

        $classes = $common . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);

        if (!empty($custom)) {
            $classes .= ' ' . implode(' ', $custom);
        }

        return $classes;
    }
}

if (!function_exists('ui_alert')) {
    /**
     * Get classes for alert/notification boxes
     *
     * @param string $variant 'success', 'error', 'warning', 'info'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_alert(string $variant = 'info', array $custom = []): string
    {
        $classes = [
            'success' => 'bg-green-50 border-green-200 text-green-800',
            'error' => 'bg-red-50 border-red-200 text-red-800',
            'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
            'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        ];

        $common = 'border rounded-lg p-4';
        $base = $common . ' ' . ($classes[$variant] ?? $classes['info']);

        if (!empty($custom)) {
            $base .= ' ' . implode(' ', $custom);
        }

        return $base;
    }
}

if (!function_exists('ui_icon_box')) {
    /**
     * Get classes for icon container boxes
     *
     * @param string $color 'blue', 'green', 'red', 'yellow', 'indigo', 'purple', 'gray'
     * @param string $size 'sm', 'md', 'lg'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_icon_box(string $color = 'indigo', string $size = 'md', array $custom = []): string
    {
        $colors = [
            'blue' => 'bg-blue-100',
            'green' => 'bg-green-100',
            'red' => 'bg-red-100',
            'yellow' => 'bg-yellow-100',
            'indigo' => 'bg-indigo-100',
            'purple' => 'bg-purple-100',
            'gray' => 'bg-gray-100',
        ];

        $sizes = [
            'sm' => 'w-8 h-8',
            'md' => 'w-12 h-12',
            'lg' => 'w-16 h-16',
        ];

        $common = 'rounded-lg flex items-center justify-center';
        $classes = $common . ' ' . ($colors[$color] ?? $colors['indigo']) . ' ' . ($sizes[$size] ?? $sizes['md']);

        if (!empty($custom)) {
            $classes .= ' ' . implode(' ', $custom);
        }

        return $classes;
    }
}

if (!function_exists('ui_icon_color')) {
    /**
     * Get color class for icons
     *
     * @param string $color 'blue', 'green', 'red', 'yellow', 'indigo', 'purple', 'gray'
     * @param string $shade '500', '600', '700'
     * @return string
     */
    function ui_icon_color(string $color = 'indigo', string $shade = '600'): string
    {
        return "text-{$color}-{$shade}";
    }
}

if (!function_exists('ui_label')) {
    /**
     * Get classes for form labels
     *
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_label(array $custom = []): string
    {
        $base = 'block text-sm font-medium text-gray-700 mb-1';

        if (!empty($custom)) {
            $base .= ' ' . implode(' ', $custom);
        }

        return $base;
    }
}

if (!function_exists('ui_modal_overlay')) {
    /**
     * Get classes for modal overlay
     *
     * @return string
     */
    function ui_modal_overlay(): string
    {
        return 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 transition-opacity duration-300';
    }
}

if (!function_exists('ui_modal_content')) {
    /**
     * Get classes for modal content box
     *
     * @param string $size 'sm', 'md', 'lg', 'xl'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_modal_content(string $size = 'md', array $custom = []): string
    {
        $sizes = [
            'sm' => 'max-w-sm',
            'md' => 'max-w-md',
            'lg' => 'max-w-lg',
            'xl' => 'max-w-xl',
            '2xl' => 'max-w-2xl',
        ];

        $common = 'bg-white rounded-lg shadow-xl w-full transform transition-all duration-300';
        $classes = $common . ' ' . ($sizes[$size] ?? $sizes['md']);

        if (!empty($custom)) {
            $classes .= ' ' . implode(' ', $custom);
        }

        return $classes;
    }
}

if (!function_exists('ui_table')) {
    /**
     * Get classes for tables
     *
     * @param string $part 'wrapper', 'table', 'thead', 'tbody', 'th', 'td'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_table(string $part = 'table', array $custom = []): string
    {
        $classes = [
            'wrapper' => 'overflow-x-auto',
            'table' => 'min-w-full divide-y divide-gray-200',
            'thead' => 'bg-gray-50',
            'tbody' => 'bg-white divide-y divide-gray-200',
            'th' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
            'td' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900',
        ];

        $base = $classes[$part] ?? '';

        if (!empty($custom)) {
            $base .= ' ' . implode(' ', $custom);
        }

        return $base;
    }
}

if (!function_exists('ui_link')) {
    /**
     * Get classes for links
     *
     * @param string $variant 'primary', 'secondary', 'danger'
     * @param array $custom Additional custom classes
     * @return string
     */
    function ui_link(string $variant = 'primary', array $custom = []): string
    {
        $variants = [
            'primary' => 'text-indigo-600 hover:text-indigo-700',
            'secondary' => 'text-gray-600 hover:text-gray-700',
            'danger' => 'text-red-600 hover:text-red-700',
        ];

        $common = 'font-medium transition duration-200';
        $classes = $common . ' ' . ($variants[$variant] ?? $variants['primary']);

        if (!empty($custom)) {
            $classes .= ' ' . implode(' ', $custom);
        }

        return $classes;
    }
}

if (!function_exists('ui_stat_card')) {
    /**
     * Generate a complete stat card component
     *
     * @param string $label The stat label
     * @param string $value The stat value
     * @param string $icon Font Awesome icon class (e.g., 'users', 'file-alt')
     * @param string $color Color variant ('blue', 'green', 'indigo', etc.)
     * @return string HTML markup
     */
    function ui_stat_card(string $label, string $value, string $icon, string $color = 'indigo'): string
    {
        $iconBoxClass = ui_icon_box($color, 'md');
        $iconColorClass = ui_icon_color($color, '600');
        $cardClass = ui_card('default', ['p-6', 'hover:shadow-md', 'transition-shadow', 'duration-200']);

        return <<<HTML
        <div class="{$cardClass}">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="{$iconBoxClass}">
                        <i class="fas fa-{$icon} {$iconColorClass} text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">{$label}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{$value}</p>
                </div>
            </div>
        </div>
        HTML;
    }
}

if (!function_exists('ui_form_group')) {
    /**
     * Generate a complete form group with label and input
     *
     * @param array $config Configuration array
     * @return string HTML markup
     */
    function ui_form_group(array $config): string
    {
        $id = $config['id'] ?? '';
        $name = $config['name'] ?? $id;
        $label = $config['label'] ?? '';
        $type = $config['type'] ?? 'text';
        $placeholder = $config['placeholder'] ?? '';
        $required = $config['required'] ?? false;
        $icon = $config['icon'] ?? null;
        $variant = $config['variant'] ?? 'default';
        $value = $config['value'] ?? '';

        $labelClass = ui_label();
        $inputClass = ui_input($variant);
        $requiredAttr = $required ? 'required' : '';
        $valueAttr = $value ? "value=\"{$value}\"" : '';

        $iconHtml = $icon ? "<i class=\"fas fa-{$icon} mr-1\"></i>" : '';

        if ($type === 'select') {
            $options = $config['options'] ?? [];
            $optionsHtml = '';
            foreach ($options as $optValue => $optLabel) {
                $selected = ($value == $optValue) ? 'selected' : '';
                $optionsHtml .= "<option value=\"{$optValue}\" {$selected}>{$optLabel}</option>";
            }

            $inputClass = ui_select();

            return <<<HTML
            <div>
                <label for="{$id}" class="{$labelClass}">
                    {$iconHtml}{$label}
                </label>
                <select id="{$id}" name="{$name}" class="{$inputClass}" {$requiredAttr}>
                    {$optionsHtml}
                </select>
            </div>
            HTML;
        }

        return <<<HTML
        <div>
            <label for="{$id}" class="{$labelClass}">
                {$iconHtml}{$label}
            </label>
            <input type="{$type}" 
                   id="{$id}" 
                   name="{$name}"
                   class="{$inputClass}"
                   placeholder="{$placeholder}"
                   {$valueAttr}
                   {$requiredAttr}>
        </div>
        HTML;
    }
}
