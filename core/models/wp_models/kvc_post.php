<?php

class KvcPost extends KvcModel {

    var $table = '{prefix}posts';
    var $primary_key = 'ID';
    var $order = 'post_date DESC';
    var $display_field = 'post_title';
    var $has_many = array(
        'Comment' => array(
            'class' => 'KvcComment',
            'foreign_key' => 'comment_post_ID'
        ),
        'Meta' => array(
            'class' => 'KvcPostMeta',
            'foreign_key' => 'post_id'
        )
    );
    
}

?>