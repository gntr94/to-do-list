<?php
/*
Plugin Name: To-Do List Plugin
Description: A simple To-Do List plugin for WordPress.
Version: 1.0
Author: Gyunter Hasan
*/

function todo_list_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tasks';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'todo_list_plugin_activate');

function todo_list_plugin_menu() {
    add_menu_page(
        'To-Do List',
        'To-Do List',
        'manage_options',
        'todo-list',
        'todo_list_plugin_page',
        'dashicons-clipboard',
        20
    );
}
add_action('admin_menu', 'todo_list_plugin_menu');

function todo_list_plugin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tasks';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_task'])) {
            // Add new task
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);
            
            $wpdb->insert($table_name, array(
                'title' => $title,
                'description' => $description
            ));
            wp_safe_redirect(admin_url('admin.php?page=todo-list'));
            exit; // Make sure to exit the script after redirecting
        } elseif (isset($_POST['update_task'])) {
            // Update existing task
            $task_id = intval($_POST['task_id']);
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);
            
            $wpdb->update($table_name, array(
                'title' => $title,
                'description' => $description
            ), array('id' => $task_id));
            wp_safe_redirect(admin_url('admin.php?page=todo-list'));
            exit;
        }
    }
    
    if (isset($_GET['delete_id'])) {
        // Delete task
        $task_id = intval($_GET['delete_id']);
        $wpdb->delete($table_name, array('id' => $task_id));
        wp_safe_redirect(admin_url('admin.php?page=todo-list'));
        exit;
    }    
    
     $tasks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    echo '<div class="wrap">';
    echo '<h1>To-Do List</h1>';
    
    if (isset($_GET['edit_id'])) {
        $task_id = intval($_GET['edit_id']);
        $task = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $task_id");
        
        if ($task) {
            echo '<h2>Edit Task</h2>';
            echo '<form class="todo-list-form" method="POST">';
            echo '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
            echo '<input type="text" name="title" value="' . esc_attr($task->title) . '" required>';
            echo '<textarea cols="45" rows="2" maxlength="65525" name="description">' . esc_attr($task->description) . '</textarea>';
            echo '<input type="submit" name="update_task" value="Update Task">';
            echo '</form>';
            
            return;
        }
    }
    
    // Display form for adding new tasks
    echo '<h2>Add New Task</h2>';
    echo '<form class="todo-list-form" method="POST">';
    echo '<input type="text" name="title" placeholder="Title" required>';
    echo '<textarea cols="45" rows="2" maxlength="65525" name="description" placeholder="Description"></textarea>';
    echo '<input type="submit" name="add_task" value="Add Task">';
    echo '</form>';
    
    // Display list of tasks
    echo '<h2>Tasks</h2>';
    echo '<table class="todo-list-table">';
    echo '<tr><th>Title</th><th>Description</th><th>Actions</th></tr>';
    foreach ($tasks as $task) {
        echo '<tr>';
        echo '<td>' . esc_html($task->title) . '</td>';
        echo '<td>' . esc_html($task->description) . '</td>';
        echo '<td>';
        echo '<a href="?page=todo-list&edit_id=' . intval($task->id) . '">Edit</a> | ';
        echo '<a href="?page=todo-list&delete_id=' . intval($task->id) . '">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</div>';
}

function todo_list_frontend_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tasks';
    $output = '';

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_task'])) {
            // Add new task
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);

            $wpdb->insert($table_name, array(
                'title' => $title,
                'description' => $description
            ));
            wp_redirect(add_query_arg(array(), home_url()));
            exit;
        } elseif (isset($_POST['update_task'])) {
            // Update existing task
            $task_id = intval($_POST['task_id']);
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);

            $wpdb->update($table_name, array(
                'title' => $title,
                'description' => $description
            ), array('id' => $task_id));
            wp_redirect(add_query_arg(array(), home_url()));
            exit;
        }
    }

    // Handle GET requests
    if (isset($_GET['delete_id'])) {
        // Delete task
        $task_id = intval($_GET['delete_id']);
        $wpdb->delete($table_name, array('id' => $task_id));
        wp_redirect(add_query_arg(array(), home_url()));
        exit;
    }

    // Get the list of tasks
    $tasks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    // Display the form for adding new tasks
    $output .= '<div class="plugin-wrap">';
    $output .= '<h2 class="todo-list-title">To-Do List</h2>';
    $output .= '<h2>Add New Task</h2>';
    $output .= '<form method="POST">';
    $output .= '<input type="text" name="title" placeholder="Title" required>';
    $output .= '<textarea cols="45" rows="2" maxlength="65525" name="description" placeholder="Description"></textarea>';
    $output .= '<input type="submit" name="add_task" value="Add Task">';
    $output .= '</form>';

    // Display list of tasks
    $output .= '<h2>Tasks</h2>';
    $output .= '<table class="todo-list-table">';
    $output .= '<tr><th>Title</th><th>Description</th><th>Actions</th></tr>';
    foreach ($tasks as $task) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($task->title) . '</td>';
        $output .= '<td>' . esc_html($task->description) . '</td>';
        $output .= '<td>';
        $output .= '<a href="?edit_id=' . intval($task->id) . '">Edit</a> | ';
        $output .= '<a href="?delete_id=' . intval($task->id) . '">Delete</a>';
        $output .= '</td>';
        $output .= '</tr>';
    }
    $output .= '</table>';

    // Handle editing tasks
    if (isset($_GET['edit_id'])) {
        $task_id = intval($_GET['edit_id']);
        $task = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $task_id");

        if ($task) {
            // Display form for editing the task
            $output .= '<h2>Edit Task</h2>';
            $output .= '<form method="POST">';
            $output .= '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
            $output .= '<input type="text" name="title" value="' . esc_attr($task->title) . '" required>';
            $output .= '<textarea cols="45" rows="2" maxlength="65525" name="description">' . esc_attr($task->description) . '</textarea>';
            $output .= '<input type="submit" name="update_task" value="Update Task">';
            $output .= '</form>';
        }
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('todo_list', 'todo_list_frontend_shortcode');

// Enqueue admin.css for the admin area
function todo_list_plugin_admin_styles() {
    wp_enqueue_style(
        'todo-list-plugin-admin-styles',
        plugin_dir_url(__FILE__) . 'admin.css',
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'admin.css')
    );
}
add_action('admin_enqueue_scripts', 'todo_list_plugin_admin_styles');

// Enqueue public.css for the frontend
function todo_list_plugin_frontend_styles() {
    wp_enqueue_style(
        'todo-list-plugin-frontend-styles',
        plugin_dir_url(__FILE__) . 'public.css',
        array(), // Dependencies (if any)
        filemtime(plugin_dir_path(__FILE__) . 'public.css')
    );
}
add_action('wp_enqueue_scripts', 'todo_list_plugin_frontend_styles');

