<?php

/**
 * Override the ElggFile so that
 */
class QuestionsTaskPluginFile extends ElggFile
{
    protected function initialiseAttributes()
    {
        parent::initialise_attributes();
        $this->attributes['subtype'] = "task_question_file";
        $this->attributes['class'] = "ElggFile";
    }

    public function __construct($guid = null)
    {
        if ($guid && !is_object($guid)) {
            // Loading entities via __construct(GUID) is deprecated, so we give it the entity row and the
            // attribute loader will finish the job. This is necessary due to not using a custom
            // subtype (see above).
            $guid = get_entity_as_row($guid);
        }
        parent::__construct($guid);
    }
}

class ResponsesTaskPluginFile extends ElggFile
{
    protected function initialiseAttributes()
    {
        parent::initialise_attributes();
        $this->attributes['subtype'] = "task_response_file";
        $this->attributes['class'] = "ElggFile";
    }

    public function __construct($guid = null)
    {
        if ($guid && !is_object($guid)) {
            // Loading entities via __construct(GUID) is deprecated, so we give it the entity row and the
            // attribute loader will finish the job. This is necessary due to not using a custom
            // subtype (see above).
            $guid = get_entity_as_row($guid);
        }
        parent::__construct($guid);
    }
}

class ZipsTaskPluginFile extends ElggFile   //SW
{
    protected function initialiseAttributes()
    {
        parent::initialise_attributes();
        $this->attributes['subtype'] = "task_zips_file";
        $this->attributes['class'] = "ElggFile";
    }

    public function __construct($guid = null)
    {
        if ($guid && !is_object($guid)) {
            // Loading entities via __construct(GUID) is deprecated, so we give it the entity row and the
            // attribute loader will finish the job. This is necessary due to not using a custom
            // subtype (see above).
            $guid = get_entity_as_row($guid);
        }
        parent::__construct($guid);
    }
}

function task_init()
{

// Extend system CSS with our own styles, which are defined in the task/css view
    elgg_extend_view('css/elgg', 'task/css');

// Register a page handler, so we can have nice URLs
    elgg_register_page_handler('task', 'task_page_handler');

// Register entity type
    elgg_register_entity_type('object', 'task');

// Register a URL handler for task posts
    elgg_register_plugin_hook_handler('entity:url', 'object', 'task_url');

// Register a URL handler for task_answer posts
    elgg_register_plugin_hook_handler('entity:url', 'object', 'task_answer_url');

// Advanced permissions
    elgg_register_plugin_hook_handler('permissions_check', 'object', 'task_permissions_check');

// Not comments to river for task_answer objects
    elgg_register_plugin_hook_handler('creating', 'river', 'task_answer_not_comments_to_river');

// Show tasks in groups
    add_group_tool_option('task', elgg_echo('task:enable_group_tasks'));
    elgg_extend_view('groups/tool_latest', 'task/group_module');

    elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'task_owner_block_menu');

    // Register library
    elgg_register_library('task', elgg_get_plugins_path() . 'task/lib/task_lib.php');

    run_function_once("task_question_file_add_subtype_run_once");
    run_function_once("task_response_file_add_subtype_run_once");
    run_function_once("task_zips_file_add_subtype_run_once"); //SW

}

function task_question_file_add_subtype_run_once()
{
    add_subtype("object", "task_question_file", "QuestionsTaskPluginFile");
}

function task_response_file_add_subtype_run_once()
{
    add_subtype("object", "task_response_file", "ResponsesTaskPluginFile");
}

function task_zips_file_add_subtype_run_once()
{  //SW
    add_subtype("object", "task_zips_file", "ZipsTaskPluginFile");
}

function task_permissions_check($hook, $type, $return, $params)
{
    $user_guid = elgg_get_logged_in_user_guid();
    $group_guid = $params['entity']->container_guid;
    $group = get_entity($group_guid);
    $group_owner_guid = $group->owner_guid;
    $operator = false;
    if (($group_owner_guid == $user_guid) || (check_entity_relationship($user_guid, 'group_admin', $group_guid))) {
        $operator = true;
    }

    if ((($params['entity']->getSubtype() == 'task') || ($params['entity']->getSubtype() == 'task_question') || ($params['entity']->getSubtype() == 'task_question_file') || ($params['entity']->getSubtype() == 'task_answer') || ($params['entity']->getSubtype() == 'task_response_file') || ($params['entity']->getSubtype() == 'rubric_rating')) && ($operator)) {
        return true;
    }

}

function task_answer_not_comments_to_river($hook, $type, $params, $return)
{
    if ($params['subtype'] == 'task_answer') {
        return false;
    }
}

/**
 * Add a menu item to the user ownerblock
 */
function task_owner_block_menu($hook, $type, $return, $params)
{
    if (elgg_instanceof($params['entity'], 'group')) {
        if ($params['entity']->task_enable != "no") {
            $url = "task/group/{$params['entity']->guid}/all";
            $item = new ElggMenuItem('task', elgg_echo('task:group'), $url);
            $return[] = $item;
        }
    }
    return $return;
}


/**
 * Task page handler; allows the use of fancy URLs
 *
 * @param array $page from the page_handler function
 * @return true|false depending on success
 */
function task_page_handler($page)
{
    if (isset($page[0])) {
        elgg_push_breadcrumb(elgg_echo('tasks'));
        $base_dir = elgg_get_plugins_path() . 'task/pages/task';
        switch ($page[0]) {
            case "add":
                set_input('container_guid', $page[1]);
                include "$base_dir/add.php";
                break;
            case "edit":
                set_input('taskpost', $page[1]);
                include "$base_dir/edit.php";
                break;
            case "view":
                set_input('guid', $page[1]);
                $task = get_entity($page[1]);
                $container = get_entity($task->container_guid);
                set_input('username', $container->username);
                set_input('edit_response', $page[2]);
                include "$base_dir/read.php";
                break;
            case "show_answer":
                set_input('taskpost', $page[1]);
                $task = get_entity($page[1]);
                $container = get_entity($task->container_guid);
                set_input('username', $container->username);
                set_input('user_guid', $page[2]);
                set_input('answer_time_user_response', $page[3]);
                set_input('offset', $page[4]);
                include "$base_dir/show_answer.php";
                break;
            case "qualify":
                set_input('taskpost', $page[1]);
                $task = get_entity($page[1]);
                $container = get_entity($task->container_guid);
                set_input('username', $container->username);
                set_input('user_guid', $page[2]);
                set_input('answer_time_user_response', $page[3]);
                set_input('offset', $page[4]);
                include "$base_dir/qualify.php";
                break;
            case 'group':
                set_input('container_guid', $page[1]);
                include "$base_dir/index.php";
            default:
                return false;
        }
    } else {
        forward();
    }
    return true;
}

/**
 * Populates the ->getUrl() method for task objects
 *
 * @param string $hook 'entity:url'
 * @param string $type 'object'
 * @param string $url The current URL
 * @param array $params Hook parameters
 * @return string task post URL
 **/
function task_url($hook, $type, $url, $params)
{
    $entity = $params['entity'];
    // Check that the entity is a rubric object
    if ($entity->getSubtype() !== 'task') {
        // This is not a task object, so there's no need to go further
        return;
    }
    $title = elgg_get_friendly_title($entity->title);
    $url = elgg_get_config('url');
    return $url . "task/view/" . $entity->getGUID() . "/" . $title;
}

/**
 * Populates the ->getUrl() method for task_answer objects
 *
 * @param string $hook 'entity:url'
 * @param string $type 'object'
 * @param string $url The current URL
 * @param array $params Hook parameters
 * @return string task_answer post URL
 **/
function task_answer_url($hook, $type, $url, $params)
{
    $entity = $params['entity'];
    // Check that the entity is a rubric object
    if ($entity->getSubtype() !== 'task_answer') {
        // This is not a task object, so there's no need to go further
        return;
    }
    $options = array(
        'relationship' => 'task_answer',
        'relationship_guid' => $entity->getGUID(),
        'inverse_relationship' => true,
        'type' => 'object',
        'subtype' => 'task'
    );
    $tasks = elgg_get_entities_from_relationship($options);
    if (!empty($tasks)) {
        $task = $tasks[0];
        $title = elgg_get_friendly_title($task->title);
        $url = elgg_get_config('url');
        return $url . 'task/view/' . $task->getGUID() . '/' . $title;
    } else {
        return false;
    }
}

// Task opened or closed?
function task_check_status($task)
{
    if ((strcmp($task->option_close_value, 'task_close_date') == 0)) {
        $now = time();
        if (($now >= $task->activate_time) && ($now < $task->close_time)) {
            return true;
        } else {
            if ($task->action == true) {
                $task->option_close_value = '';
                $task->action = false;
                $task->opened = true;
                return true;
            }
            return false;
        }
    } else {
        $task->action = false;
        return $task->opened;
    }
}

// Make sure the task initialisation function is called on initialisation
elgg_register_event_handler('init', 'system', 'task_init');

// Register actions
$action_base = elgg_get_plugins_path() . 'task/actions/task';
elgg_register_action("task/add", "$action_base/add.php");
elgg_register_action("task/edit", "$action_base/edit.php");
elgg_register_action("task/delete", "$action_base/delete.php");
elgg_register_action("task/open", "$action_base/open.php");
elgg_register_action("task/close", "$action_base/close.php");
elgg_register_action("task/change_responses_visibility", "$action_base/change_responses_visibility.php");
elgg_register_action("task/change_responses_comments_visibility", "$action_base/change_responses_comments_visibility.php");
elgg_register_action("task/change_grading_visibility", "$action_base/change_grading_visibility.php");
elgg_register_action("task/change_public_global_marks", "$action_base/change_public_global_marks.php");
elgg_register_action("task/answer", "$action_base/answer.php");
elgg_register_action("task/delete_answer", "$action_base/delete_answer.php");
elgg_register_action("task/qualify", "$action_base/qualify.php");
elgg_register_action("task/assign_marks", "$action_base/assign_marks.php");
elgg_register_action("task/assign_game_points", "$action_base/assign_game_points.php");
elgg_register_action("task/zip_all", "$action_base/zip_all.php");  //SW
elgg_register_action("task/get_zips", "$action_base/get_zips.php");  //SW
elgg_register_action("task/export_statistics_pdf", "$action_base/export_statistics_pdf.php"); 


?>
