<?php

class KvcCommentMeta extends KvcModel {

    var $table = '{prefix}commentmeta';
    var $primary_key = 'meta_id';
    var $order = 'meta_key';
    var $display_field = 'meta_key';
    var $belongs_to = array(
        'Comment' => array(
            'class' => 'KvcComment',
            'foreign_key' => 'comment_id'
        )
    );
    
}

?>