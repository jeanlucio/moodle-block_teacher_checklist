<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'block_teacher_checklist_toggle_item_status' => array(
        'classname'   => 'block_teacher_checklist\external',
        'methodname'  => 'toggle_item_status',
        'description' => 'Muda o status de um item do checklist',
        'type'        => 'write',
        'ajax'        => true, // Importante: permite chamada via Javascript
    ),
);
