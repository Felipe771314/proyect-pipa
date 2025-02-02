<?php
/**
 * Plugin Name: My Custom Plugin
 * Description: Plugin base para extender WordPress con endpoints personalizados y funcionalidades custom.
 * Version: 1.0.0
 * Author: Tu Nombre
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Evitar acceso directo.
}

// Registrar un endpoint personalizado en la WP REST API
add_action( 'rest_api_init', function () {
  register_rest_route( 'custom/v1', '/hello', array(
    'methods'             => 'GET',
    'callback'            => 'my_custom_plugin_hello',
    'permission_callback' => '__return_true',
  ));
});

function my_custom_plugin_hello( $request ) {
  return rest_ensure_response( array( 'message' => 'Hola, este es el endpoint de prueba.' ) );
}
