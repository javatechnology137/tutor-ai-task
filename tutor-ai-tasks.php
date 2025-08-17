<?php
/**
 * Plugin Name: Tutor LMS AI Task System
 * Plugin URI: https://techpeer.nh
 * Description: Adds AI-powered task system to Tutor LMS lessons with OpenAI integration
 * Version: 1.0.0
 * Author: Chukwuemeka Princewill
 * License: GPL v2 or later
 * Text Domain: tutor-ai-tasks
 * Requires at least: 5.0
 * Tested up to: 6.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TUTOR_AI_TASKS_VERSION', '1.0.0');
define('TUTOR_AI_TASKS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TUTOR_AI_TASKS_PLUGIN_PATH', plugin_dir_path(__FILE__));

class TutorAITasks {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Check if Tutor LMS is active
        if (!function_exists('tutor_lms')) {
            add_action('admin_notices', array($this, 'tutor_lms_required_notice'));
            return;
        }
        
        $this->load_dependencies();
        $this->setup_hooks();
    }
    
    private function load_dependencies() {
        require_once TUTOR_AI_TASKS_PLUGIN_PATH . 'includes/class-admin.php';
        require_once TUTOR_AI_TASKS_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once TUTOR_AI_TASKS_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once TUTOR_AI_TASKS_PLUGIN_PATH . 'includes/class-openai-api.php';
    }
    
    private function setup_hooks() {
        // Initialize components
        new TutorAITasks_Admin();
        new TutorAITasks_Frontend();
        new TutorAITasks_Ajax();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add meta box to lessons
        add_action('add_meta_boxes', array($this, 'add_lesson_meta_box'));
        add_action('save_post', array($this, 'save_lesson_meta'));
        
        // Add AI task section to lesson content
        add_action('tutor_lesson/single/lesson/content', array($this, 'add_ai_task_section'), 20);
    }
    
    public function activate() {
        // Create database table for chat sessions
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tutor_ai_chat_sessions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            messages longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX session_idx (session_id),
            INDEX user_lesson_idx (user_id, lesson_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function tutor_lms_required_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Tutor LMS AI Task System requires Tutor LMS to be installed and activated.', 'tutor-ai-tasks');
        echo '</p></div>';
    }
    
    public function enqueue_frontend_scripts() {
        if (is_single() && get_post_type() === 'lesson') {
            wp_enqueue_script(
                'tutor-ai-tasks-frontend',
                TUTOR_AI_TASKS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                TUTOR_AI_TASKS_VERSION,
                true
            );
            
            wp_enqueue_style(
                'tutor-ai-tasks-frontend',
                TUTOR_AI_TASKS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                TUTOR_AI_TASKS_VERSION
            );
            
            wp_localize_script('tutor-ai-tasks-frontend', 'tutorAITasks', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tutor_ai_tasks_nonce'),
                'lesson_id' => get_the_ID(),
                'session_id' => $this->generate_session_id(),
                'strings' => array(
                    'sending' => __('Sending...', 'tutor-ai-tasks'),
                    'error' => __('Error occurred. Please try again.', 'tutor-ai-tasks'),
                    'type_message' => __('Type your message here...', 'tutor-ai-tasks')
                )
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            global $post_type;
            if ($post_type === 'lesson') {
                wp_enqueue_script(
                    'tutor-ai-tasks-admin',
                    TUTOR_AI_TASKS_PLUGIN_URL . 'assets/js/admin.js',
                    array('jquery'),
                    TUTOR_AI_TASKS_VERSION,
                    true
                );
                
                wp_enqueue_style(
                    'tutor-ai-tasks-admin',
                    TUTOR_AI_TASKS_PLUGIN_URL . 'assets/css/admin.css',
                    array(),
                    TUTOR_AI_TASKS_VERSION
                );
            }
        }
    }
    
    public function add_lesson_meta_box() {
        add_meta_box(
            'tutor-ai-task-settings',
            __('AI Task Settings', 'tutor-ai-tasks'),
            array($this, 'render_lesson_meta_box'),
            'lesson',
            'normal',
            'high'
        );
    }
    
    public function render_lesson_meta_box($post) {
        wp_nonce_field('tutor_ai_task_meta_box', 'tutor_ai_task_nonce');
        
        $ai_task_enabled = get_post_meta($post->ID, '_tutor_ai_task_enabled', true);
        $ai_task_prompt = get_post_meta($post->ID, '_tutor_ai_task_prompt', true);
        $ai_task_title = get_post_meta($post->ID, '_tutor_ai_task_title', true);
        $ai_task_description = get_post_meta($post->ID, '_tutor_ai_task_description', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable AI Task', 'tutor-ai-tasks'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="tutor_ai_task_enabled" value="1" <?php checked($ai_task_enabled, '1'); ?>>
                        <?php _e('Enable AI Task for this lesson', 'tutor-ai-tasks'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Task Title', 'tutor-ai-tasks'); ?></th>
                <td>
                    <input type="text" name="tutor_ai_task_title" value="<?php echo esc_attr($ai_task_title); ?>" class="regular-text" placeholder="<?php _e('e.g., Cybersecurity with AI', 'tutor-ai-tasks'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Task Description', 'tutor-ai-tasks'); ?></th>
                <td>
                    <textarea name="tutor_ai_task_description" rows="3" cols="50" placeholder="<?php _e('Brief description of the task...', 'tutor-ai-tasks'); ?>"><?php echo esc_textarea($ai_task_description); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('AI System Prompt', 'tutor-ai-tasks'); ?></th>
                <td>
                    <textarea name="tutor_ai_task_prompt" rows="5" cols="50" placeholder="<?php _e('You are an AI assistant helping students with cybersecurity concepts. Explain cybersecurity and its benefits to the healthcare system...', 'tutor-ai-tasks'); ?>"><?php echo esc_textarea($ai_task_prompt); ?></textarea>
                    <p class="description"><?php _e('This prompt will guide the AI\'s responses for this lesson.', 'tutor-ai-tasks'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_lesson_meta($post_id) {
        if (!isset($_POST['tutor_ai_task_nonce']) || !wp_verify_nonce($_POST['tutor_ai_task_nonce'], 'tutor_ai_task_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $enabled = isset($_POST['tutor_ai_task_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_tutor_ai_task_enabled', $enabled);
        
        if (isset($_POST['tutor_ai_task_title'])) {
            update_post_meta($post_id, '_tutor_ai_task_title', sanitize_text_field($_POST['tutor_ai_task_title']));
        }
        
        if (isset($_POST['tutor_ai_task_description'])) {
            update_post_meta($post_id, '_tutor_ai_task_description', sanitize_textarea_field($_POST['tutor_ai_task_description']));
        }
        
        if (isset($_POST['tutor_ai_task_prompt'])) {
            update_post_meta($post_id, '_tutor_ai_task_prompt', sanitize_textarea_field($_POST['tutor_ai_task_prompt']));
        }
    }
    
    public function add_ai_task_section() {
        global $post;
        
        $ai_task_enabled = get_post_meta($post->ID, '_tutor_ai_task_enabled', true);
        
        if ($ai_task_enabled === '1') {
            $ai_task_title = get_post_meta($post->ID, '_tutor_ai_task_title', true);
            $ai_task_description = get_post_meta($post->ID, '_tutor_ai_task_description', true);
            
            include TUTOR_AI_TASKS_PLUGIN_PATH . 'templates/ai-task-section.php';
        }
    }
    
    private function generate_session_id() {
        return 'sess_' . uniqid() . '_' . time();
    }
}

// Initialize the plugin
new TutorAITasks();

// Admin Settings Class
class TutorAITasks_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('Tutor AI Tasks Settings', 'tutor-ai-tasks'),
            __('Tutor AI Tasks', 'tutor-ai-tasks'),
            'manage_options',
            'tutor-ai-tasks-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('tutor_ai_tasks_settings', 'tutor_ai_tasks_openai_api_key');
        register_setting('tutor_ai_tasks_settings', 'tutor_ai_tasks_model');
        register_setting('tutor_ai_tasks_settings', 'tutor_ai_tasks_max_tokens');
        register_setting('tutor_ai_tasks_settings', 'tutor_ai_tasks_temperature');
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Tutor AI Tasks Settings', 'tutor-ai-tasks'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('tutor_ai_tasks_settings'); ?>
                <?php do_settings_sections('tutor_ai_tasks_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('OpenAI API Key', 'tutor-ai-tasks'); ?></th>
                        <td>
                            <input type="password" name="tutor_ai_tasks_openai_api_key" value="<?php echo esc_attr(get_option('tutor_ai_tasks_openai_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your OpenAI API key. Get one from https://platform.openai.com/', 'tutor-ai-tasks'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('AI Model', 'tutor-ai-tasks'); ?></th>
                        <td>
                            <select name="tutor_ai_tasks_model">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('tutor_ai_tasks_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected(get_option('tutor_ai_tasks_model'), 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-4-turbo-preview" <?php selected(get_option('tutor_ai_tasks_model'), 'gpt-4-turbo-preview'); ?>>GPT-4 Turbo</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Max Tokens', 'tutor-ai-tasks'); ?></th>
                        <td>
                            <input type="number" name="tutor_ai_tasks_max_tokens" value="<?php echo esc_attr(get_option('tutor_ai_tasks_max_tokens', '1000')); ?>" min="1" max="4000">
                            <p class="description"><?php _e('Maximum number of tokens in the AI response.', 'tutor-ai-tasks'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Temperature', 'tutor-ai-tasks'); ?></th>
                        <td>
                            <input type="number" name="tutor_ai_tasks_temperature" value="<?php echo esc_attr(get_option('tutor_ai_tasks_temperature', '0.7')); ?>" min="0" max="2" step="0.1">
                            <p class="description"><?php _e('Controls randomness in AI responses (0-2). Lower values make responses more focused.', 'tutor-ai-tasks'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Frontend Display Class
class TutorAITasks_Frontend {
    
    public function __construct() {
        // Constructor content handled in main class
    }
}

// AJAX Handler Class
class TutorAITasks_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_tutor_ai_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_tutor_ai_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_tutor_ai_load_chat', array($this, 'load_chat_history'));
        add_action('wp_ajax_nopriv_tutor_ai_load_chat', array($this, 'load_chat_history'));
    }
    
    public function handle_chat_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'tutor_ai_tasks_nonce')) {
            wp_die('Security check failed');
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $lesson_id = intval($_POST['lesson_id']);
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($message) || empty($lesson_id)) {
            wp_send_json_error('Invalid parameters');
        }
        
        // Get lesson's AI prompt
        $system_prompt = get_post_meta($lesson_id, '_tutor_ai_task_prompt', true);
        if (empty($system_prompt)) {
            $system_prompt = "You are a helpful AI assistant helping students learn.";
        }
        
        // Load chat history
        $chat_history = $this->get_chat_history($session_id, $lesson_id);
        
        // Prepare messages for OpenAI
        $messages = array();
        $messages[] = array('role' => 'system', 'content' => $system_prompt);
        
        // Add previous conversation
        foreach ($chat_history as $chat) {
            $messages[] = array('role' => 'user', 'content' => $chat['user_message']);
            $messages[] = array('role' => 'assistant', 'content' => $chat['ai_response']);
        }
        
        // Add current message
        $messages[] = array('role' => 'user', 'content' => $message);
        
        // Call OpenAI API
        $openai = new TutorAITasks_OpenAI_API();
        $response = $openai->chat_completion($messages);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $ai_response = $response['choices'][0]['message']['content'];
            
            // Save to chat history
            $this->save_chat_message($session_id, $lesson_id, $message, $ai_response);
            
            wp_send_json_success(array(
                'response' => $ai_response,
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error('Failed to get AI response');
        }
    }
    
    public function load_chat_history() {
        if (!wp_verify_nonce($_POST['nonce'], 'tutor_ai_tasks_nonce')) {
            wp_die('Security check failed');
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $lesson_id = intval($_POST['lesson_id']);
        
        $chat_history = $this->get_chat_history($session_id, $lesson_id);
        
        wp_send_json_success($chat_history);
    }
    
    private function get_chat_history($session_id, $lesson_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tutor_ai_chat_sessions';
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT messages FROM $table_name WHERE session_id = %s AND lesson_id = %d",
                $session_id,
                $lesson_id
            )
        );
        
        if ($result && !empty($result->messages)) {
            return json_decode($result->messages, true);
        }
        
        return array();
    }
    
    private function save_chat_message($session_id, $lesson_id, $user_message, $ai_response) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tutor_ai_chat_sessions';
        $user_id = get_current_user_id();
        
        // Get existing messages
        $existing_messages = $this->get_chat_history($session_id, $lesson_id);
        
        // Add new message
        $existing_messages[] = array(
            'user_message' => $user_message,
            'ai_response' => $ai_response,
            'timestamp' => current_time('mysql')
        );
        
        $messages_json = json_encode($existing_messages);
        
        // Insert or update
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE session_id = %s AND lesson_id = %d",
                $session_id,
                $lesson_id
            )
        );
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                array('messages' => $messages_json),
                array('id' => $existing->id)
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'session_id' => $session_id,
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id,
                    'messages' => $messages_json
                )
            );
        }
    }
}

// OpenAI API Class
class TutorAITasks_OpenAI_API {
    
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    public function __construct() {
        $this->api_key = get_option('tutor_ai_tasks_openai_api_key');
    }
    
    public function chat_completion($messages) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $model = get_option('tutor_ai_tasks_model', 'gpt-3.5-turbo');
        $max_tokens = intval(get_option('tutor_ai_tasks_max_tokens', 1000));
        $temperature = floatval(get_option('tutor_ai_tasks_temperature', 0.7));
        
        $data = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        );
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
?>
