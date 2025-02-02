<?php
/**
 * Plugin Name: My Custom Plugin
 * Description: Plugin para extender WordPress con endpoints para el editor de páginas.
 * Version: 1.0.0
 * Author: Tu Nombre
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Registrar endpoints en la REST API
add_action('rest_api_init', function () {
  // Endpoint para obtener todas las páginas
  register_rest_route('custom/v1', '/pages', array(
    'methods' => 'GET',
    'callback' => 'get_page_builder_pages',
    'permission_callback' => '__return_true',
  ));

  // Endpoint para actualizar la configuración de una página (por ID)
  register_rest_route('custom/v1', '/pages/(?P<id>\d+)', array(
    'methods' => 'POST',
    'callback' => 'update_page_builder_content',
    'permission_callback' => 'current_user_can',
  ));

  // Endpoint de prueba
  register_rest_route('custom/v1', '/hello', array(
    'methods' => 'GET',
    'callback' => 'my_custom_plugin_hello',
    'permission_callback' => '__return_true',
  ));
});

function get_page_builder_pages() {
  $args = array(
    'post_type' => 'page_builder',
    'post_status' => 'publish',
  );
  $query = new WP_Query($args);
  $pages = array();

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      $pages[] = array(
        'id'     => get_the_ID(),
        'title'  => get_the_title(),
        'slug'   => get_post_field('post_name', get_the_ID()),
        // La configuración se almacena en un meta field 'page_config'
        'config' => get_post_meta(get_the_ID(), 'page_config', true),
      );
    }
    wp_reset_postdata();
  }
  return rest_ensure_response($pages);
}

function update_page_builder_content($request) {
  $id = $request['id'];
  $config = $request->get_param('config'); // Recibe el JSON con la estructura de la página

  update_post_meta($id, 'page_config', $config);
  return rest_ensure_response(array('success' => true));
}

function my_custom_plugin_hello($request) {
  return rest_ensure_response(array('message' => 'Hola, este es el endpoint de prueba.'));
}

// Para asegurarte de que las reglas de reescritura se actualicen automáticamente al activar el plugin:
function my_custom_plugin_activate() {
  register_page_builder_cpt();
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'my_custom_plugin_activate');
