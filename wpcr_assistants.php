<?php
$options = get_option('wpcr_options');
    $api_key = isset($options['wpcr_openai_api_key']) ? $options['wpcr_openai_api_key'] : '';
    $assistant_id = isset($options['wpcr_assistant_id'])  ? $options['wpcr_assistant_id'] : '';
    $assistant_name = isset($options['wpcr_assistant_name'])  ? $options['wpcr_assistant_name'] : '';      
    $use_assistant = isset($options['wpcr_use_assistant']) ? $options['wpcr_use_assistant'] : '';  

function wpcr_add_log($message) {
    $upload_dir = wp_upload_dir();
    $log_file_path = $upload_dir['basedir'] . '/wp-comment-responder-logs.txt';
    $time_stamp = date("Y-m-d H:i:s");
    $formatted_message = "[$time_stamp] $message" . PHP_EOL;
    file_put_contents($log_file_path, $formatted_message, FILE_APPEND);
}

$license_active = get_option('wpcr_license_active', false);
if($license_active && $api_key != ''){
    add_action('comment_post', 'wpcr_schedule_auto_reply', 10, 2);
}

function wpcr_schedule_auto_reply($comment_id, $comment_approved) {
    if (1 === $comment_approved) {        
        wp_schedule_single_event(time() + 10, 'wpcr_process_comment_reply', array($comment_id));
    }
}

add_action('wpcr_process_comment_reply', 'wpcr_process_comment_reply_function');

function wpcr_process_comment_reply_function($comment_id) {    

    $comment = get_comment($comment_id);
    if (!$comment) {        
        return;
    }    

    $response = wpcr_get_openai_response($comment->comment_content, $comment_id);

}

function wpcr_get_openai_response($comment_content, $comment_id) {
    $options = get_option('wpcr_options');
    $api_key = $options['wpcr_openai_api_key'];
    $use_assistant = isset($options['wpcr_use_assistant']) ? $options['wpcr_use_assistant'] : '';
    $assistant_id = $options['wpcr_assistant_id'];
    $enable_file_search = isset($options['wpcr_enable_file_search']) ? $options['wpcr_enable_file_search'] : '';
    $enable_code_interpreter = isset($options['wpcr_enable_code_interpreter']) ? $options['wpcr_enable_code_interpreter'] : '';

    if (!empty($use_assistant)) {
        // Assistants API Logic        

        // Step 1: Creates a Thread
        $thread_response = wp_remote_post("https://api.openai.com/v1/threads", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ),
            'body' => json_encode(array()),
            'tools' => array(
                'type' => $enable_file_search ? 'file_search' : '',
                'type' => $enable_code_interpreter ? 'code_interpreter' : ''
            )        
        ));

        if (is_wp_error($thread_response)) {
            wpcr_add_log("Erro ao criar o thread: " . $thread_response->get_error_message());
            return false;
        }

        $thread_body = json_decode(wp_remote_retrieve_body($thread_response), true);
        $thread_id = $thread_body['id'] ?? null;

        if (!$thread_id) {
            wpcr_add_log("Erro: ID do thread não encontrado. Estrutura recebida: " . print_r($thread_body, true));        
            return false;
        }    

        // Step 2: Add message to the Thread
        $message_response = wp_remote_post("https://api.openai.com/v1/threads/$thread_id/messages", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ),
            'body' => json_encode(array(
                'role' => 'user',
                'content' => $comment_content
            ))
        ));

        if (is_wp_error($message_response)) {
            wpcr_add_log("Erro ao enviar mensagem ao thread: " . $message_response->get_error_message());
            return false;
        }    

        // Step 3: Execute Thread with the assistant_id
        $run_response = wp_remote_post("https://api.openai.com/v1/threads/$thread_id/runs", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ),
            'body' => json_encode(array(
                'assistant_id' => $assistant_id,
            ))
        ));

        if (is_wp_error($run_response)) {
            wpcr_add_log("Erro ao executar o thread: " . $run_response->get_error_message());
            return false;
        }

        $run_body = json_decode(wp_remote_retrieve_body($run_response), true);
        $run_id = $run_body['id'] ?? null;

        if (!$run_id) {
            wpcr_add_log("Erro: ID da execução (run) não encontrado. Estrutura recebida: " . print_r($run_body, true));
            return false;
        }    

        // Schedule first verification with the execution status
        wp_schedule_single_event(time() + 10, 'wpcr_check_run_status', array($thread_id, $run_id, $comment_id));

        return true; 
    } else {
        // Completions API Logic        

        $messages = array(
            array("role" => "system", "content" => "Você é um assistente útil. Escreva com no máximo 250 tokens."),
            array("role" => "user", "content" => $comment_content)
        );

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4o-mini', // Substitua pelo modelo desejado
                'messages' => $messages,
                'max_tokens' => 250,
            )),
            'timeout' => 30 // Aumentar o tempo limite para 30 segundos
        );

        $completion_response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);

        if (is_wp_error($completion_response)) {
            wpcr_add_log("Erro ao obter completions: " . $completion_response->get_error_message());
            return false;
        }

        $completion_body = json_decode(wp_remote_retrieve_body($completion_response), true);

        if (!$completion_body || empty($completion_body['choices'])) {
            wpcr_add_log("Erro: Resposta da OpenAI não encontrada.");
            return false;
        }

        // Schedule verification of the completions API        
        wp_schedule_single_event(time() + 10, 'wpcr_check_completion_status', array($completion_body, $comment_id));

        return true;
    }
}

add_action('wpcr_check_completion_status', 'wpcr_check_completion_status_function', 10, 2);

function wpcr_check_completion_status_function($completion_body, $comment_id) {    

    if (!$completion_body || empty($completion_body['choices']) || !isset($completion_body['choices'][0]['message']['content'])) {
        wpcr_add_log("Erro: Resposta da OpenAI não encontrada.");
        return false;
    }

    $assistant_reply = $completion_body['choices'][0]['message']['content'];

    // Remove odd characters and format response.
    $cleaned_reply = preg_replace('/【\d+:\d+†source】/', '', $assistant_reply);

    if ($cleaned_reply !== "1") {
        $original_comment = get_comment($comment_id);

        if (!$original_comment) {
            wpcr_add_log("Erro: Comentário original não encontrado.");
            return false;
        }

        // Get the admin ID
        $admin_user_id = get_option('admin_email');
        $admin_user = get_user_by('email', $admin_user_id);

        if ($admin_user && !is_wp_error($admin_user)) {
            $first_name = $admin_user->first_name ? $admin_user->first_name : 'Administrador';
        } else {
            $first_name = 'Administrador';
        }

        $new_comment_id = wp_insert_comment(array(
            'comment_post_ID' => $original_comment->comment_post_ID,
            'comment_content' => $cleaned_reply,
            'comment_author' => $first_name,
            'comment_author_email' => $admin_user_id,
            'comment_parent' => $comment_id,
            'comment_approved' => 1,
        ));

        if ($new_comment_id && !is_wp_error($new_comment_id)) {            
            return $cleaned_reply; // Retorna a resposta correta
        } else {
            wpcr_add_log("Erro ao inserir o comentário de resposta: " . print_r($new_comment_id, true));
            return false;
        }
    }

    return true;
}

add_action('wpcr_check_run_status', 'wpcr_check_run_status_function', 10, 3);

function wpcr_check_run_status_function($thread_id, $run_id, $comment_id) {
    $options = get_option('wpcr_options');
    $api_key = $options['wpcr_openai_api_key'];
    $assistant_name = $options['wpcr_assistant_name'];        

    $steps_response = wp_remote_get("https://api.openai.com/v1/threads/$thread_id/runs/$run_id/steps", array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        )
    ));

    if (is_wp_error($steps_response)) {
        wpcr_add_log("Erro ao verificar os passos da execução: " . $steps_response->get_error_message());
        return false;
    }

    $steps_body = json_decode(wp_remote_retrieve_body($steps_response), true);    

    $steps_status = $steps_body['data'] ?? [];
    $all_steps_completed = true;

    foreach ($steps_status as $step) {
        if ($step['status'] !== 'succeeded' && $step['status'] !== 'completed') {
            $all_steps_completed = false;
            break;
        }
    }
    

    if ($all_steps_completed) {
        $final_message_response = wp_remote_get("https://api.openai.com/v1/threads/$thread_id/messages", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            )
        ));

        if (is_wp_error($final_message_response)) {
            wpcr_add_log("Erro ao recuperar a mensagem final: " . $final_message_response->get_error_message());
            return false;
        }

        $final_message_body = json_decode(wp_remote_retrieve_body($final_message_response), true);        

        $messages = $final_message_body['data'] ?? [];
        $assistant_reply = null;

        foreach ($messages as $message) {
            if ($message['role'] === 'assistant' && isset($message['content'][0]['text']['value'])) {
                $assistant_reply = $message['content'][0]['text']['value'];
                break;
            }
        }

        if (!$assistant_reply) {
            wpcr_add_log("Erro: Resposta final do assistente não encontrada.");
            return false;
        }
        

        $original_comment = get_comment($comment_id);

        if (!$original_comment) {            
            return false;
        }        

        $cleaned_reply = preg_replace('/【\d+:\d+†source】/', '', $assistant_reply);

        if ($cleaned_reply !== "1") {
            $new_comment_id = wp_insert_comment(array(
                'comment_post_ID' => $original_comment->comment_post_ID,
                'comment_content' => $cleaned_reply,
                'comment_author' => $assistant_name,
                'comment_author_email' => get_option('admin_email'),
                'comment_parent' => $comment_id,
                'comment_approved' => 1,
            ));
        }

        if ($new_comment_id && !is_wp_error($new_comment_id)) {            
            return $cleaned_reply; // Return correct response
        } else {
            wpcr_add_log("Erro ao inserir o comentário de resposta: " . print_r($new_comment_id, true));
            return false;
        }
    } else {
        // Re-schedule cron if the status is not completed    
        wp_schedule_single_event(time() + 10, 'wpcr_check_run_status', array($thread_id, $run_id, $comment_id));
        return false;
    }
}