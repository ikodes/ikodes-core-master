<?php

class KvcObjectRegistry {

    var $__objects = array();

    static function &get_instance() {
        static $instance = array();
        if (!$instance) {
            $kvc_object_registry = new KvcObjectRegistry();
            $instance[0] =& $kvc_object_registry;
        }
        return $instance[0];
    }

    static function &get_object($key) {
        $_this =& KvcObjectRegistry::get_instance();
        $key = KvcInflector::camelize($key);
        $return = false;
        if (isset($_this->__objects[$key])) {
            $return =& $_this->__objects[$key];
        }
        return $return;
    }
    
    static function add_object($key, &$object) {
        $_this =& KvcObjectRegistry::get_instance();
        $key = KvcInflector::camelize($key);
        if (!isset($_this->__objects[$key])) {
            $_this->__objects[$key] =& $object;
            return true;
        }
        return false;
    }

}

?>
