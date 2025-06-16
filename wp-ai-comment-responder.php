<?php
/*
Plugin Name: WP AI Comment Responder
Plugin URI: https://estudarti.com.br/landing/plugin-wp-ai-comment-responder
Description: Automatically respond to WordPress comments using OpenAI's GPT-4o-mini model. This plugin leverages AI to handle and reply to comments intelligently and efficiently, helping you maintain engagement without manual work.
Version: 1.4.5
Author: Adam Silva
Author URI: https://www.estudarti.com.br
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-ai-comment-responder
Domain Path: /languages
*/

require_once plugin_dir_path( __FILE__ ) . '/libraries/license-sdk/License.php';
require_once plugin_dir_path( __FILE__ ) . '/libraries/license-sdk/config.php';
require_once 'wpcr_options_page.php';
require_once 'wpcr_assistants.php';
require_once 'wpcr_tutor.php';


$license_active = get_option('wpcr_license_active', false);
require 'libraries/plugin-update-checker/plugin-update-checker.php';

if ($license_active) {	
	wpcr_update_run();
}
		
	use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
	
	function wpcr_update_run() {

		$myUpdateChecker = PucFactory::buildUpdateChecker(
			'https://bitbucket.org/adamsilva/wp-ai-comment-responder/',
			__FILE__,
			'wp-ai-comment-responder'
		);
	
	
		$myUpdateChecker->setAuthentication(array(
			'consumer_key' => 'RyzRnkXCFChQKHwVeY',
			'consumer_secret' => 'SQQCNgbCzv6DP7RLqkfQPyrMxyYb5dRM',
		));
	
	
		$myUpdateChecker->setBranch('master');
	}

