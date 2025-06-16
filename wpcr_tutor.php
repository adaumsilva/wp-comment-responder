<?php
/**
 * Integration with Tutor LMS to automatically answer questions.
 */

// Run only after WordPress is loaded
add_action('plugins_loaded', 'wpcr_initialize_tutor_lms_integration');

function wpcr_initialize_tutor_lms_integration() {
    // Check if Tutor LMS is active
    if (!in_array('tutor/tutor.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        wpcr_add_log("Tutor LMS is not active. Integration disabled.");
        return;
    }    

    // Hook to schedule auto-reply after a question is submitted
    add_action('tutor_before_answer_to_question', 'wpcr_schedule_tutor_reply', 10, 1);

    /**
     * Schedules processing of the automatic response.
     *
     * @param int $question_id ID of the submitted question.
     */
    function wpcr_schedule_tutor_reply($question_id) {
        wpcr_add_log("Hook triggered.");
        wp_schedule_single_event(time() + 10, 'wpcr_process_tutor_reply', array($question_id));
    }

    // Hook to process the automatic reply
    add_action('wpcr_process_tutor_reply', 'wpcr_process_tutor_reply_function');

    /**
     * Processes and publishes the automatic response in Tutor LMS.
     *
     * @param int $question_id ID of the question.
     */
    function wpcr_process_tutor_reply_function($question_id) {
        $options = get_option('wpcr_options');
        $api_key = isset($options['wpcr_openai_api_key']) ? $options['wpcr_openai_api_key'] : '';
        $assistant_id = isset($options['wpcr_assistant_id']) ? $options['wpcr_assistant_id'] : '';
        $assistant_name = isset($options['wpcr_assistant_name']) ? $options['wpcr_assistant_name'] : '';

        if (!$api_key || !$assistant_id) {
            wpcr_add_log("Error: API key or assistant ID not configured.");
            return;
        }

        // Retrieve question details from Tutor LMS
        if (!function_exists('tutor_utils')) {
            wpcr_add_log("Error: Tutor LMS functions not available.");
            return;
        }

        $question = tutor_utils()->get_question($question_id);
        if (!$question || empty($question->post_content)) {
            wpcr_add_log("Error: Question not found or has no content.");
            return;
        }

        // Get response from OpenAI API
        $response = wpcr_get_openai_response($question->post_content, $question_id);
        if (!$response) {
            wpcr_add_log("Error generating response for the question in Tutor LMS.");
            return;
        }

        // Publish the response as a comment on the question
        wp_insert_comment(array(
            'comment_post_ID' => $question->ID,  // Question ID
            'comment_content' => $response,      // Generated response
            'comment_type'    => 'tutor_answer', // Marks it as an answer in Tutor LMS
            'user_id'         => get_current_user_id(), // ID of the user responding
            'comment_approved' => 1,             // Publish immediately
        ));

        wpcr_add_log("Response to Tutor LMS question published successfully.");
    }
}