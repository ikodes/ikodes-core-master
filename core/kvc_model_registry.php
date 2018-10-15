<?php

class KvcModelRegistry {

    var $__models = array();

    static function &get_instance() {
        static $instance = array();
        if (!$instance) {
            $kvc_model_registry = new KvcModelRegistry();
            $instance[0] =& $kvc_model_registry;
        }
        return $instance[0];
    }

    static function &get_model($key) {
        $_this =& self::get_instance();
        $key = KvcInflector::camelize($key);
        $return = false;
        if (isset($_this->__models[$key])) {
            $return =& $_this->__models[$key];
        } else if (class_exists($key)) {
            $_this->__models[$key] = new $key();
            $return =& $_this->__models[$key];
        }
        return $return;
    }

    static function &get_models() {
        $_this =& self::get_instance();
        $return =& $_this->__models;
        return $return;
    }
    
    static function add_model($key, &$model) {
        $_this =& self::get_instance();
        $key = KvcInflector::camelize($key);
        if (!isset($_this->__models[$key])) {
            $_this->__models[$key] = $model;
            return true;
        }
        return false;
    }

}

?>
