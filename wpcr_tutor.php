<?php
/**
 * Integração com Tutor LMS para responder automaticamente às perguntas.
 */

// Executar apenas no carregamento do WordPress
add_action('plugins_loaded', 'wpcr_initialize_tutor_lms_integration');

function wpcr_initialize_tutor_lms_integration() {
    // Verificar se o Tutor LMS está ativo
    if (!in_array('tutor/tutor.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        wpcr_add_log("Tutor LMS não está ativo. Integração desativada.");
        return;
    }    

    // Hook para agendar resposta automática após a submissão de uma pergunta
    add_action('tutor_before_answer_to_question', 'wpcr_schedule_tutor_reply', 10, 1);

    /**
     * Agenda o processamento da resposta automática.
     *
     * @param int $question_id ID da pergunta submetida.
     */
    function wpcr_schedule_tutor_reply($question_id) {
        wpcr_add_log("Chamou hook.");
        wp_schedule_single_event(time() + 10, 'wpcr_process_tutor_reply', array($question_id));
    }

    // Hook para processar a resposta automática
    add_action('wpcr_process_tutor_reply', 'wpcr_process_tutor_reply_function');

    /**
     * Processa e publica a resposta automática no Tutor LMS.
     *
     * @param int $question_id ID da pergunta.
     */
    function wpcr_process_tutor_reply_function($question_id) {
        $options = get_option('wpcr_options');
        $api_key = isset($options['wpcr_openai_api_key']) ? $options['wpcr_openai_api_key'] : '';
        $assistant_id = isset($options['wpcr_assistant_id']) ? $options['wpcr_assistant_id'] : '';
        $assistant_name = isset($options['wpcr_assistant_name']) ? $options['wpcr_assistant_name'] : '';

        if (!$api_key || !$assistant_id) {
            wpcr_add_log("Erro: Chave API ou ID do assistente não configurados.");
            return;
        }

        // Recuperar detalhes da pergunta no Tutor LMS
        if (!function_exists('tutor_utils')) {
            wpcr_add_log("Erro: Funções do Tutor LMS não disponíveis.");
            return;
        }

        $question = tutor_utils()->get_question($question_id);
        if (!$question || empty($question->post_content)) {
            wpcr_add_log("Erro: Pergunta não encontrada ou sem conteúdo.");
            return;
        }

        // Obter resposta da API OpenAI
        $response = wpcr_get_openai_response($question->post_content, $question_id);
        if (!$response) {
            wpcr_add_log("Erro ao gerar resposta para a pergunta no Tutor LMS.");
            return;
        }

        // Publicar a resposta como um comentário na pergunta
        wp_insert_comment(array(
            'comment_post_ID' => $question->ID,  // ID da pergunta
            'comment_content' => $response,     // Resposta gerada
            'comment_type'    => 'tutor_answer', // Identifica como resposta no Tutor LMS
            'user_id'         => get_current_user_id(), // ID do usuário que responde
            'comment_approved' => 1,            // Publica imediatamente
        ));

        wpcr_add_log("Resposta à pergunta no Tutor LMS publicada com sucesso.");
    }
}
