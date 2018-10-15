<?php

abstract class KvcLoader {

    protected $admin_controller_names = array();
    protected $admin_controller_capabilities = array();
    protected $core_path = '';
    protected $dispatcher = null;
    protected $file_includer = null;
    protected $model_names = array();
    protected $public_controller_names = array();
    protected $query_vars = array();

    function __construct() {
    
        if (!defined('MVC_CORE_PATH')) {
            define('MVC_CORE_PATH', iKVC_PLUGIN_PATH.'core/');
        }
        
        $this->core_path = MVC_CORE_PATH;

        $this->query_vars = array('kvc_controller','kvc_action','kvc_id','kvc_extra','kvc_layout');
        
        $this->load_core();
        $this->load_plugins();
        
        $this->file_includer = new KvcFileIncluder();
        $this->file_includer->include_all_app_files('config/bootstrap.php');
        $this->file_includer->include_all_app_files('config/routes.php');

        $this->dispatcher = new KvcDispatcher();

        $this->plugin_name = KvcObjectRegistry::get_object('plugin_name');
        if (! isset($this->plugin_name)) {
            $this->plugin_name = '';
        }

    }

    
    protected function load_core() {
        
        $files = array(
            'kvc_error',
            'kvc_configuration',
            'kvc_directory',
            'kvc_dispatcher',
            'kvc_file',
            'kvc_file_includer',
            'kvc_model_registry',
            'kvc_object_registry',
            'kvc_settings_registry',
            'kvc_plugin_loader',
            'kvc_templater',
            'kvc_inflector',
            'kvc_router',
            'kvc_settings',
            'controllers/kvc_controller',
            'controllers/kvc_admin_controller',
            'controllers/kvc_public_controller',
            'functions/functions',
            'models/kvc_database_adapter',
            'models/kvc_database',
            'models/kvc_data_validation_error',
            'models/kvc_data_validator',
            'models/kvc_model_object',
            'models/kvc_model',
            'models/wp_models/kvc_comment',
            'models/wp_models/kvc_comment_meta',
            'models/wp_models/kvc_post_adapter',
            'models/wp_models/kvc_post',
            'models/wp_models/kvc_post_meta',
            'models/wp_models/kvc_user',
            'models/wp_models/kvc_user_meta',
            'helpers/kvc_helper',
            'helpers/kvc_form_tags_helper',
            'helpers/kvc_form_helper',
            'helpers/kvc_html_helper',
            'shells/kvc_shell',
            'shells/kvc_shell_dispatcher'
        );
        
        foreach ($files as $file) {
            require_once $this->core_path.$file.'.php';
        }
        
    }
    
    protected function load_plugins() {
    
        $plugins = $this->get_ordered_plugins();
        $plugin_app_paths = array();
        foreach ($plugins as $plugin) {
            $plugin_app_paths[$plugin] = rtrim(WP_PLUGIN_DIR, '/').'/'.$plugin.'/app/';
        }

        KvcConfiguration::set(array(
            'Plugins' => $plugins,
            'PluginAppPaths' => $plugin_app_paths
        ));

        $this->plugin_app_paths = $plugin_app_paths;
    
    }
    
    protected function get_ordered_plugins() {
    
        $plugins = get_option('kvc_plugins', array());
        $plugin_app_paths = array();
        
        // Allow plugins to be loaded in a specific order by setting a PluginOrder config value like
        // this ('all' is an optional token; it includes all unenumerated plugins):
        // KvcConfiguration::set(array(
        //      'PluginOrder' => array('my-first-plugin', 'my-second-plugin', 'all', 'my-last-plugin')
        // );
        $plugin_order = KvcConfiguration::get('PluginOrder');
        if (!empty($plugin_order)) {
            $ordered_plugins = array();
            $index_of_all = array_search('all', $plugin_order);
            if ($index_of_all !== false) {
                $first_plugins = array_slice($plugin_order, 0, $index_of_all - 1);
                $last_plugins = array_slice($plugin_order, $index_of_all);
                $middle_plugins = array_diff($plugins, $first_plugins, $last_plugins);
                $plugins = array_merge($first_plugins, $middle_plugins, $last_plugins);
            } else {
                $unordered_plugins = array_diff($plugins, $plugin_order);
                $plugins = array_merge($plugin_order, $unordered_plugins);
            }
        }
        
        return $plugins;
        
    }
    
    public function init() {

        $this->load_controllers();
        $this->load_libs();
        $this->load_models();
        $this->load_settings();
        $this->load_functions();
    
    }
    
    public function filter_post_link($permalink, $post) {
        if (substr($post->post_type, 0, 4) == 'kvc_') {
            $model_name = substr($post->post_type, 4);
            $controller = KvcInflector::tableize($model_name);
            $model_name = KvcInflector::camelize($model_name);
            $model = KvcModelRegistry::get_model($model_name);
            $object = $model->find_one_by_post_id($post->ID);
            if ($object) {
                $url = KvcRouter::public_url(array('object' => $object));
                if ($url) {
                    return $url;
                }
            }
        }
        return $permalink;
    }
    
    public function register_widgets() {
        foreach ($this->plugin_app_paths as $plugin_app_path) {
            $directory = $plugin_app_path.'widgets/';
            $widget_filenames = $this->file_includer->require_php_files_in_directory($directory);
  
            $path_segments_to_remove = array(WP_CONTENT_DIR, '/plugins/', '/app/');
            $plugin = str_replace($path_segments_to_remove, '', $plugin_app_path);

            foreach ($widget_filenames as $widget_file) {
                $widget_name = str_replace('.php', '', $widget_file);
                $widget_class = KvcInflector::camelize($plugin).'_'.KvcInflector::camelize($widget_name);
                register_widget($widget_class);
            }
        }
    }
    
    protected function load_controllers() {
    
        foreach ($this->plugin_app_paths as $plugin_app_path) {
        
            $admin_controller_filenames = $this->file_includer->require_php_files_in_directory($plugin_app_path.'controllers/admin/');
            $public_controller_filenames = $this->file_includer->require_php_files_in_directory($plugin_app_path.'controllers/');
            
            foreach ($admin_controller_filenames as $filename) {
                if (preg_match('/admin_([^\/]+)_controller\.php/', $filename, $match)) {
                    $controller_name = $match[1];
                    $this->admin_controller_names[] = $controller_name;
                    $capabilities = KvcConfiguration::get('admin_controller_capabilities');
                    if (empty($capabilities) || !isset($capabilities[$controller_name])) {
                        $capabilities = array($controller_name => 'administrator');
                    }
                    $this->admin_controller_capabilities[$controller_name] = $capabilities[$controller_name];
                }
            }
            
            foreach ($public_controller_filenames as $filename) {
                if (preg_match('/([^\/]+)_controller\.php/', $filename, $match)) {
                    $this->public_controller_names[] = $match[1];
                }
            }
        
        }
        
    }
    
    protected function load_libs() {
        
        foreach ($this->plugin_app_paths as $plugin_app_path) {
        
            $this->file_includer->require_php_files_in_directory($plugin_app_path.'libs/');
            
        }
        
    }
    
    protected function load_models() {
        
        $models = array();
        
        foreach ($this->plugin_app_paths as $plugin_app_path) {
        
            $model_filenames = $this->file_includer->require_php_files_in_directory($plugin_app_path.'models/');
            
            foreach ($model_filenames as $filename) {
                $models[] = KvcInflector::class_name_from_filename($filename);
            }
        
        }
        
        $this->model_names = array();
        
        foreach ($models as $model) {
            $this->model_names[] = $model;
            $model_class = KvcInflector::camelize($model);
            $model_instance = new $model_class();
            KvcModelRegistry::add_model($model, $model_instance);
        }
        
    }
    
    protected function load_settings() {
        
        $settings_names = array();
        
        foreach ($this->plugin_app_paths as $plugin_app_path) {
        
            $settings_filenames = $this->file_includer->require_php_files_in_directory($plugin_app_path.'settings/');
            
            foreach ($settings_filenames as $filename) {
                $settings_names[] = KvcInflector::class_name_from_filename($filename);
            }
        
        }
        
        $this->settings_names = $settings_names;
        
    }
    
    protected function load_functions() {
        
        foreach ($this->plugin_app_paths as $plugin_app_path) {
        
            $this->file_includer->require_php_files_in_directory($plugin_app_path.'functions/');
            
        }
    
    }

}

?>
