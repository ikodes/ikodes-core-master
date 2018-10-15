<?php

function kvc_plugin_app_path($plugin) {
    $plugin_app_paths = KvcConfiguration::get('PluginAppPaths');
    return $plugin_app_paths[$plugin];
}

function kvc_plugin_app_url($plugin) {
    $abspath = rtrim(ABSPATH, '/').'/';
    $site_url = rtrim(site_url(), '/').'/';
    $url = str_replace($abspath, $site_url, kvc_plugin_app_path($plugin));
    return $url;
}

function kvc_model($model_name) {
    $model_underscore = KvcInflector::underscore($model_name);
    $file_includer = new KvcFileIncluder();
    $file_includer->include_first_app_file('models/'.$model_underscore.'.php');
    if (class_exists($model_name)) {
        return new $model_name();
    }

    throw new Exception('Unable to load the "'.$model_name.'" model.');
}

function kvc_setting($settings_name, $setting_key) {
    $settings_name = 'kvc_'.KvcInflector::underscore($settings_name);
    $option = get_option($settings_name);
    if (isset($option[$setting_key])) {
        return $option[$setting_key];
    }
    return null;
}

function kvc_render_to_string($view, $vars=array()) {
    $view_pieces = explode('/', $view);
    $model_tableized = $view_pieces[0];
    $model_camelized = KvcInflector::camelize($model_tableized);
    $controller_name = $model_camelized.'Controller';
    if (!class_exists($controller_name)) {
        $controller_name = 'KvcPublicController';
    }
    $controller = new $controller_name();
    $controller->init();
    $controller->set($vars);
    $string = $controller->render_to_string($view);
    return $string;
}

function kvc_public_url($options) {
    return KvcRouter::public_url($options);
}

function kvc_admin_url($options) {
    return KvcRouter::admin_url($options);
}

function kvc_css_url($plugin, $filename, $options=array()) {

    $defaults = array(
        'add_extension' => true
    );

    $options = array_merge($defaults, $options);
    
    if ($options['add_extension']) {
        if (!preg_match('/\.[\w]{2,4}/', $filename)) {
            $filename .= '.css';
        }
    }
    
    return kvc_plugin_app_url($plugin).'public/css/'.$filename;
    
}

function kvc_js_url($plugin, $filename, $options=array()) {

    $defaults = array(
        'add_extension' => true
    );

    $options = array_merge($defaults, $options);
    
    if ($options['add_extension']) {
        if (!preg_match('/\.[\w]{2,4}/', $filename)) {
            $filename .= '.js';
        }
    }
    
    return kvc_plugin_app_url($plugin).'public/js/'.$filename;
    
}

function kvc_add_plugin($plugin) {
    $added = false;
    $plugins = kvc_get_plugins();
    if (!in_array($plugin, $plugins)) {
        $plugins[] = $plugin;
        $added = true;
    }
    update_option('kvc_plugins', $plugins);
    return $added;
}

function kvc_remove_plugin($plugin) {
    $removed = false;
    $plugins = kvc_get_plugins();
    if (in_array($plugin, $plugins)) {
        foreach ($plugins as $key => $existing_plugin) {
            if ($plugin == $existing_plugin) {
                unset($plugins[$key]);
                $removed = true;
            }
        }
        $plugins = array_values($plugins);
    }
    update_option('kvc_plugins', $plugins);
    return $removed;
}

function kvc_get_plugins() {
    $plugins = get_option('kvc_plugins', array());
    return $plugins;
}

function is_kvc_page() {
    global $kvc_params;
    if (!empty($kvc_params['controller'])) {
        return true;
    }
    return false;
}

?>