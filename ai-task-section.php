<?php
/**
 * Template for AI Task Section
 * File: templates/ai-task-section.php
 */

$ai_task_title = get_post_meta($post->ID, '_tutor_ai_task_title', true);
$ai_task_description = get_post_meta($post->ID, '_tutor_ai_task_description', true);

if (empty($ai_task_title)) {
    $ai_task_title = __('AI Learning Assistant', 'tutor-ai-tasks');
}
?>

<div class="tutor-ai-task-section">
    <div class="tutor-ai-task-header">
        <h3 class="tutor-ai-task-title">
            <span class="ai-icon">🤖</span>
            <?php echo esc_html($ai_task_title); ?>
        </h3>
        <?php if (!empty($ai_task_description)): ?>
            <p class="tutor-ai-task-description"><?php echo esc_html($ai_task_description); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="tutor-ai-chat-container">
        <div class="tutor-ai-chat-messages" id="tutor-ai-chat-messages">
            <div class="ai-welcome-message">
                <div class="message ai-message">
                    <div class="message-content">
                        <?php _e('Hello! I\'m here to help you with this lesson. Feel free to ask me any questions!', 'tutor-ai-tasks'); ?>
                    </div>
                    <div class="message-time"><?php echo current_time('H:i'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="tutor-ai-chat-input-area">
            <div class="chat-input-container">
                <textarea 
                    id="tutor-ai-chat-input" 
                    placeholder="<?php _e('Type your message here...', 'tutor-ai-tasks'); ?>"
                    rows="1"
                ></textarea>
                <button id="tutor-ai-send-btn" class="tutor-ai-send-button">
                    <span class="send-icon">📤</span>
                    <span class="send-text"><?php _e('Send', 'tutor-ai-tasks'); ?></span>
                </button>
            </div>
            <div class="chat-status" id="tutor-ai-status" style="display: none;">
                <span class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <?php _e('AI is typing...', 'tutor-ai-tasks'); ?>
            </div>
        </div>
    </div>
</div>
