<?php
/**
 * Plugin Name: IFOAM Network Mapping Exercise
 * Plugin URI: https://github.com/AlainBenbassat/ifoam_network_mapping_exercise
 * Description: Maps relationships with the members of the European Parliament and the European Commission
 * Version: 1.0
 * Author: Alain Benbassat
 * Author URI: https://www.businessandcode.eu/
 **/

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

function ifoam_network_mapping_exercise_on_activate() {
  require_once __DIR__ . '/src/NmePageCreator.php';

  $pageCreator = new NmePageCreator();
  $pageCreator->createPages();
}

function ifoam_network_mapping_exercise_add_short_code_main_page() {
  require_once __DIR__ . '/src/NmeMainPage.php';
  return NmeMainPage::get();
}

function ifoam_network_mapping_exercise_add_short_code_contact_page() {
  require_once __DIR__ . '/src/NmeContactPage.php';
  return NmeContactPage::get();
}

function ifoam_network_mapping_exercise_add_short_code_group_page() {
  require_once __DIR__ . '/src/NmeGroupPage.php';
  return NmeGroupPage::get();
}

register_activation_hook(__FILE__, 'ifoam_network_mapping_exercise_on_activate');

add_shortcode('network_mapping_exercise_main_page', 'ifoam_network_mapping_exercise_add_short_code_main_page');
add_shortcode('network_mapping_exercise_contact_page', 'ifoam_network_mapping_exercise_add_short_code_contact_page');
add_shortcode('network_mapping_exercise_group_page', 'ifoam_network_mapping_exercise_add_short_code_group_page');

