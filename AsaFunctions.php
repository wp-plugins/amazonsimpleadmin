<?php
/**
 * @param string $path
 * @param string $plugin
 * @return string
 */
function asa_plugins_url($path = '', $plugin = '') {
    if (getenv('ASA_APPLICATION_ENV') == 'development') {
        return get_bloginfo('wpurl') . '/wp-content/plugins/amazonsimpleadmin/' . $path;
    }
    return plugins_url($path, $plugin);
}
 