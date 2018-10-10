<?php
if (is_array($vars['posts']) && sizeof($vars['posts']) > 0) {
    foreach ($vars['posts'] as $post) {

        echo elgg_view_entity($post);
    }
}
?>