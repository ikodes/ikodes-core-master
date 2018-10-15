<?php

class KvcUser extends KvcModel {

    var $table = '{prefix}users';
    var $primary_key = 'ID';
    var $order = 'user_login';
    var $display_field = 'user_login';
    var $has_many = array(
        'Comment' => array(
            'class' => 'KvcComment',
            'foreign_key' => 'user_id'
        ),
        'Post' => array(
            'class' => 'KvcPost',
            'foreign_key' => 'post_author'
        )
    );
    
}

?>