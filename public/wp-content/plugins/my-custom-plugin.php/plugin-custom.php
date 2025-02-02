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

/**
 * Registro del Custom Post Type para páginas del Page Builder.
 */
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
      'show_in_rest' => true, // Expone el CPT en la WP REST API
      'supports' => array('title', 'editor', 'thumbnail'),
    );
  
    register_post_type('page_builder', $args);
}
add_action('init', 'register_page_builder_cpt');

/**
 * Agrega cabeceras CORS para permitir solicitudes cross-origin.
 */
function add_cors_http_headers() {
  // Puedes restringir en producción, por ejemplo: "http://localhost:4200"
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");
}

// Para peticiones OPTIONS (preflight) en la REST API
add_action('rest_api_init', function() {
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    add_cors_http_headers();
    exit;
  }
}, 15);

// Agregar las cabeceras CORS en cada respuesta de la REST API
add_action('rest_api_init', 'add_cors_http_headers', 15);

/**
 * Registro de endpoints personalizados en la WP REST API.
 */
add_action('rest_api_init', function () {
  // Endpoint para obtener todas las páginas creadas con el CPT 'page_builder'
  register_rest_route('custom/v1', '/pages', array(
    'methods' => 'GET',
    'callback' => 'get_page_builder_pages',
    'permission_callback' => '__return_true',
  ));
  
  // Endpoint para actualizar la configuración de una página específica (por ID)
  register_rest_route('custom/v1', '/pages/(?P<id>\d+)', array(
    'methods' => 'POST',
    'callback' => 'update_page_builder_content',
    'permission_callback' => '__return_true', // Para pruebas; ajustar en producción
  ));
  
  // Endpoint de prueba para verificar que el plugin funciona
  register_rest_route('custom/v1', '/hello', array(
    'methods' => 'GET',
    'callback' => 'my_custom_plugin_hello',
    'permission_callback' => '__return_true',
  ));
});

/**
 * Función para obtener las páginas creadas con el CPT 'page_builder'.
 */
function get_page_builder_pages() {
  $args = array(
    'post_type' => 'page_builder',
    'post_status' => 'publish',
  );
  $query = new WP_Query($args);
  $pages = array();
  
  if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
      $query->the_post();
      $pages[] = array(
        'id'     => get_the_ID(),
        'title'  => get_the_title(),
        'slug'   => get_post_field('post_name', get_the_ID()),
        // Se asume que la configuración del editor se guarda en un meta field llamado 'page_config'
        'config' => get_post_meta(get_the_ID(), 'page_config', true),
      );
    }
    wp_reset_postdata();
  }
  return rest_ensure_response($pages);
}

/**
 * Función para actualizar la configuración de una página.
 */
function update_page_builder_content($request) {
  $id = $request['id'];
  $config = $request->get_param('config'); // Recibe el JSON con la estructura de la página

  error_log("update_page_builder_content: id = $id, config = " . print_r($config, true));

  if ( empty($config) ) {
    error_log("No se proporcionó configuración.");
    return new WP_Error('no_config', 'No se proporcionó configuración', array('status' => 400));
  }
  
  // Verifica que el post exista
  if ( ! get_post($id) ) {
    error_log("El post con ID $id no existe.");
    return new WP_Error('invalid_post', 'El post con el ID proporcionado no existe', array('status' => 404));
  }
  
  // Opción: Forzar actualización, sin comprobar si es idéntica
  $updated = update_post_meta($id, 'page_config', $config);
  
  if ( false === $updated ) {
    error_log("No se pudo actualizar la configuración para el post $id.");
    return new WP_Error('update_failed', 'No se pudo actualizar la configuración', array('status' => 500));
  }
  
  return rest_ensure_response(array('success' => true, 'message' => 'Configuración actualizada'));
}


/**
 * Función de prueba para el endpoint /hello.
 */
function my_custom_plugin_hello($request) {
  return rest_ensure_response(array('message' => 'Hola, este es el endpoint de prueba.'));
}

/**
 * Hook de activación del plugin: registra el CPT y flushea las reglas de reescritura.
 */
function my_custom_plugin_activate() {
  register_page_builder_cpt();
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'my_custom_plugin_activate');
