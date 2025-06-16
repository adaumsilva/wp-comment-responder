<?php
// Adiciona a página de opções ao menu de administração
add_action('admin_menu', 'wpcr_add_admin_menu');
add_action('admin_init', 'wpcr_settings_init');

function wpcr_add_admin_menu() {
    add_options_page(
        'WP AI Comment Responder', 
        'WP AI Comment Responder', 
        'manage_options', 
        'wp_comment_responder', 
        'wpcr_options_page'
    );
}

function wpcr_settings_init() {
    register_setting('wpcr_options_group', 'wpcr_options', 'wpcr_options_validate');

    add_settings_section(
        'wpcr_settings_section', 
        __('Configurações do WP AI Comment Responder', 'wpcr'), 
        'wpcr_settings_section_callback', 
        'wpcr_options_group'
    );

    add_settings_field(
        'wpcr_license_key', 
        __('License Key', 'wpcr'), 
        'wpcr_license_key_render', 
        'wpcr_options_group', 
        'wpcr_settings_section'
    );

    $license_active = get_option('wpcr_license_active', false);

    if ($license_active) {
        add_settings_field(
            'wpcr_openai_api_key', 
            __('OpenAI API Key', 'wpcr'), 
            'wpcr_openai_api_key_render', 
            'wpcr_options_group', 
            'wpcr_settings_section'
        );

        add_settings_field(
            'wpcr_use_assistant', 
            __('Usar Assistente?', 'wpcr'), 
            'wpcr_use_assistant_render', 
            'wpcr_options_group', 
            'wpcr_settings_section'
        );

        add_settings_field(
            'wpcr_assistant_id', 
            __('Assistant ID', 'wpcr'), 
            'wpcr_assistant_id_render', 
            'wpcr_options_group', 
            'wpcr_settings_section'
        );

        add_settings_field(
            'wpcr_assistant_name', 
            __('Nome do Assistente', 'wpcr'), 
            'wpcr_assistant_name_render', 
            'wpcr_options_group', 
            'wpcr_settings_section'
        );

        add_settings_field(
            'wpcr_enable_file_search', 
            __('Habilitar File Search', 'wpcr'), 
            'wpcr_enable_file_search_render', 
            'wpcr_options_group', 
            'wpcr_settings_section'
        );

        add_settings_field(
            'wpcr_enable_code_interpreter', 
            __('Habilitar Code Interpreter', 'wpcr'), 
            'wpcr_enable_code_interpreter_render', 
            'wpcr_options_group', 
            'wpcr_settings_section'
        );

        

    }
}

// Função para renderizar o campo License Key
function wpcr_license_key_render() {
    $options = get_option('wpcr_options');
    $license_key = isset($options['wpcr_license_key']) ? esc_attr($options['wpcr_license_key']) : '';
    ?>
    <input type="text" name="wpcr_options[wpcr_license_key]" value="<?php echo $license_key; ?>" />
    <span class="description"><?php _e('Insira sua chave de licença.', 'wpcr'); ?></span>
    <?php
}

// Função para renderizar o campo OpenAI API Key
function wpcr_openai_api_key_render() {
    $options = get_option('wpcr_options');
    $api_key = isset($options['wpcr_openai_api_key']) ? esc_attr($options['wpcr_openai_api_key']) : '';
    ?>
    <input type="text" name="wpcr_options[wpcr_openai_api_key]" value="<?php echo $api_key; ?>" />
    <span class="description"><?php _e('Obtenha sua chave de API <a href="https://platform.openai.com/api-keys" target="_blank">clicando aqui</a>.', 'wpcr'); ?></span>
    <?php
}

// Função para renderizar o campo Usar Assistente
function wpcr_use_assistant_render() {
    $options = get_option('wpcr_options');
    $use_assistant = isset($options['wpcr_use_assistant']) ? $options['wpcr_use_assistant'] : '';
    ?>
    <input type="checkbox" name="wpcr_options[wpcr_use_assistant]" <?php checked($use_assistant, 'on'); ?> />
    <span class="description"><?php _e('Marque para usar o assistente.', 'wpcr'); ?></span><br>
    <span class="description"><?php _e('Assistente são GPTs personalizados que você pode treinar com arquivos e instruções próprias para obter melhores respostas.', 'wpcr'); ?></span>
    <?php
}

// Função para renderizar o campo Assistant ID
function wpcr_assistant_id_render() {
    $options = get_option('wpcr_options');
    $assistant_id = isset($options['wpcr_assistant_id']) ? esc_attr($options['wpcr_assistant_id']) : '';
    ?>
    <input type="text" name="wpcr_options[wpcr_assistant_id]" value="<?php echo $assistant_id; ?>" />
    <span class="description"><?php _e('Crie seu assistente <a href="https://platform.openai.com/assistants/" target="_blank">clicando aqui</a>.', 'wpcr'); ?></span>
    <?php
}

// Função para renderizar o campo Nome do Assistente
function wpcr_assistant_name_render() {
    $options = get_option('wpcr_options');
    $assistant_name = isset($options['wpcr_assistant_name']) ? esc_attr($options['wpcr_assistant_name']) : '';
    ?>
    <input type="text" name="wpcr_options[wpcr_assistant_name]" value="<?php echo $assistant_name; ?>" />
    <span class="description"><?php _e('Insira o nome do assistente que será usado como autor dos comentários quando o assistente for usado.', 'wpcr'); ?></span>
    <?php
}

// Função para renderizar o campo Habilitar File Search
function wpcr_enable_file_search_render() {
    $options = get_option('wpcr_options');
    $enable_file_search = isset($options['wpcr_enable_file_search']) ? $options['wpcr_enable_file_search'] : '';
    ?>
    <input type="checkbox" name="wpcr_options[wpcr_enable_file_search]" <?php checked($enable_file_search, 'on'); ?> />
    <span class="description"><?php _e('Marque para habilitar a busca de arquivos.', 'wpcr'); ?></span>
    <?php
}

// Função para renderizar o campo Habilitar Code Interpreter
function wpcr_enable_code_interpreter_render() {
    $options = get_option('wpcr_options');
    $enable_code_interpreter = isset($options['wpcr_enable_code_interpreter']) ? $options['wpcr_enable_code_interpreter'] : '';
    ?>
    <input type="checkbox" name="wpcr_options[wpcr_enable_code_interpreter]" <?php checked($enable_code_interpreter, 'on'); ?> />
    <span class="description"><?php _e('Marque para habilitar o interpretador de código.', 'wpcr'); ?></span>
    <?php
}

// Função para verificar a licença e exibir o link para o log de erros
function wpcr_display_log_link() {
    $license_active = get_option('wpcr_license_active', false);

    if ($license_active) {
        $upload_dir = wp_upload_dir();
        $log_file_url = $upload_dir['baseurl'] . '/wp-comment-responder-logs.txt';
        echo '<p><a href="' . esc_url($log_file_url) . '" target="_blank">' . __('Ver Log de Erros do Plugin', 'wpcr') . '</a></p>';
    }
}

function wpcr_options_validate($input) {
    $old_license = get_option('wpcr_license_key');
    $new_license = $input['wpcr_license_key'];
    $license_valid = get_option('wpcr_license_active');

    // Verifica se a nova licença é diferente da antiga
    if ($new_license !== $old_license || $license_valid != 1) {
        // Tenta ativar a nova licença
        $activation_success = wpcr_activate_license($new_license);
        
        // Atualiza a nova licença na base de dados, mesmo se a ativação falhar
        update_option('wpcr_license_key', $new_license);

        if ($activation_success) {            
            update_option('wpcr_license_active', true);
        } else {
            wpcr_add_log("Falha na ativação da licença. Mantendo a licença inativa.");
            add_settings_error('wpcr_license_key', 'wpcr_license_key_error', __('Licença inválida ou erro na ativação.', 'wpcr'));
            update_option('wpcr_license_active', false); // Desativa se a nova licença não for válida
        }
    }
    return $input;
}

function wpcr_activate_license($license_key) {
    global $sdk_license;
    $validate_license = $sdk_license->validate_status($license_key);    
    if ($validate_license['is_valid']) {
        
        try {
            $activate_license = $sdk_license->activate($license_key);
    
            if ($activate_license) {                
                schedule_license_validity_check();
                return true; // Licença ativada com sucesso
            } else {
                wpcr_add_log("Falha na ativação da licença na API.");
                return false;
            }
        } catch (Exception $e) {
            wpcr_add_log("Erro na ativação da licença: " . $e->getMessage());
            return false;
        }
        
    } else {
        wpcr_add_log("Erro na requisição de ativação: " . $validate_license['error']);
    }
}

function wpcr_options_page() {
    ?>
    <div class="wrap">
        <h1>WP AI Comment Responder</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wpcr_options_group'); ?>
            <?php do_settings_sections('wpcr_options_group'); ?>
            <?php submit_button(); ?>
            <?php wpcr_display_log_link(); ?>
        </form>
    </div>
    <?php
}

function wpcr_settings_section_callback() {
    echo __('Configure as opções para o WP AI Comment Responder.', 'wpcr');
}

/*function custom_cron_schedules($schedules) {
    $schedules['every_two_minutes'] = array(
        'interval' => 120, // 2 minutos em segundos
        'display' => __('Every 2 Minutes')
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_schedules');*/

// Função para agendar o evento de verificação de validade da licença
function schedule_license_validity_check() {
    if(wp_next_scheduled( 'unique_plugin_name_license_validity' ) ) {
        wp_schedule_event( time(), 'daily', 'unique_plugin_name_license_validity' );        
    }
}
  
  # Add validity function hook
  add_action('unique_plugin_name_license_validity', 'unique_validation_function');
  
  # Create the validity function called by the hook
  function unique_validation_function() {
    global $sdk_license;
    
    $license_key = get_option('wpcr_license_key');    
    $valid_status = $sdk_license->validate_status($license_key);    
    if ($license_key && $valid_status['error']) {
      update_option('wpcr_license_active', false);      
    }
    else if ($license_key && $valid_status['is_valid']) {
      update_option('wpcr_license_active', true);
    }
    return $valid_status['is_valid'];
  }

// Notificação de licença expirada
function wpcr_license_expired_notice() {
    if (get_option('wpcr_license_key') && !get_option('wpcr_license_active')) {
        echo '<div class="notice notice-error">';
        echo '<p>' . __('A licença do WP AI Comment Responder é inválida ou expirou. Por favor, renove para continuar usando o plugin.', 'wpcr') . '</p>';
        echo '</div>';
    }
}

// Garante que o aviso será exibido em todas as páginas do admin
add_action('admin_notices', 'wpcr_license_expired_notice');

add_action('admin_enqueue_scripts', 'wpcr_admin_scripts');
function wpcr_admin_scripts() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const useAssistantCheckbox = document.querySelector('input[name="wpcr_options[wpcr_use_assistant]"]');
            const assistantFields = document.querySelectorAll('input[name^="wpcr_options[wpcr_assistant_"], input[name="wpcr_options[wpcr_enable_file_search]"], input[name="wpcr_options[wpcr_enable_code_interpreter]"]');

            function toggleAssistantFields() {
                assistantFields.forEach(field => {
                    field.closest('tr').style.display = useAssistantCheckbox.checked ? '' : 'none';
                });
            }

            if (useAssistantCheckbox) {
                useAssistantCheckbox.addEventListener('change', toggleAssistantFields);
                toggleAssistantFields(); // Chama a função inicialmente para definir o estado correto no carregamento da página
            }
        });
    </script>
    <?php
}