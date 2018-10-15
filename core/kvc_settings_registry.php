<?php

class KvcSettingsRegistry {

    var $__settings = array();

    static private function &get_instance() {
        static $instance = array();
        if (!$instance) {
            $kvc_settings_registry = new KvcSettingsRegistry();
            $instance[0] =& $kvc_settings_registry;
        }
        return $instance[0];
    }

    static function &get_settings($key) {
        $_this =& self::get_instance();
        $key = KvcInflector::camelize($key);
        $return = false;
        if (isset($_this->__settings[$key])) {
            $return =& $_this->__settings[$key];
        } else if (class_exists($key)) {
            $_this->__settings[$key] = new $key();
            $return =& $_this->__settings[$key];
        }
        return $return;
    }
    
    public function add_settings($key, &$settings) {
        $_this =& self::get_instance();
        $key = KvcInflector::camelize($key);
        if (!isset($_this->__settings[$key])) {
            $_this->__settings[$key] = $settings;
            return true;
        }
        return false;
    }

}

?>
