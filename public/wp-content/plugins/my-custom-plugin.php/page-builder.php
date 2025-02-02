<?php
/**
 * Plugin Name: My Custom Plugin
 * Description: Plugin base para extender WordPress con endpoints personalizados y funcionalidades custom para un editor de páginas.
 * Version: 1.0.0
 * Author: Tu Nombre
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Evitar acceso directo.
}

// Registro del CPT
function register_page_builder_cpt() {
    $labels = array(
      'name' => 'Páginas',
      'singular_name' => 'Página',
      'add_new' => 'Añadir nueva',
      'add_new_item' => 'Añadir nueva página',
      'edit_item' => 'Editar página',
      'new_item' => 'Nueva página',
      'view_item' => 'Ver página',
      'search_items' => 'Buscar páginas',
    );

    $args = array(
      'labels' => $labels,
      'public' => true,
      'has_archive' => true,
      'rewrite' => array('slug' => 'paginas'),
      'show_in_rest' => true,
      'supports' => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('page_builder', $args);
}
add_action('init', 'register_page_builder_cpt');

// Hook de activación: registra el CPT y flushea las reglas.
function my_custom_plugin_activate() {
    register_page_builder_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'my_custom_plugin_activate');

// Endpoints personalizados en la WP REST API
add_action('rest_api_init', function () {
  register_rest_route('custom/v1', '/pages', array(
    'methods' => 'GET',
    'callback' => 'get_page_builder_pages',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('custom/v1', '/pages/(?P<id>\d+)', array(
    'methods' => 'POST',
    'callback' => 'update_page_builder_content',
    'permission_callback' => 'current_user_can',
  ));

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
        'config' => get_post_meta(get_the_ID(), 'page_config', true),
      );
    }
    wp_reset_postdata();
  }
  return rest_ensure_response($pages);
}

function update_page_builder_content($request) {
  $id = $request['id'];
  $config = $request->get_param('config');

  update_post_meta($id, 'page_config', $config);
  return rest_ensure_response(array('success' => true));
}

function my_custom_plugin_hello($request) {
  return rest_ensure_response(array('message' => 'Hola, este es el endpoint de prueba.'));
}
