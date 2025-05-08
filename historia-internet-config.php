<?php
/**
 * @package Historia del Internet Configuración
 * @version 1.0.7
 */
/*
Plugin Name: Historia del Internet Configuración
Plugin URI: https://github.com/enflujo/enflujo-historia-internet-config
GitHub Plugin URI: https://github.com/enflujo/enflujo-historia-internet-config
Primary Branch: main
Description: Esta extensión configura los modelos y configuración en WordPress para el proyecto Historia del Internet.
Author: Laboratorio EnFlujo
Version: 1.0.9
Author URI: https://enflujo.com
*/

require_once 'colecciones.php';
require_once 'modelos.php';
function historia_configurar_tema()
{
  add_theme_support('post-thumbnails');
}

function historia_registrar_colecciones()
{
  // register_post_type('linea_tiempo', historia_coleccion_linea());
  register_post_type('personajes', historia_coleccion_personajes());
  register_post_type('glosario', historia_coleccion_glosario());
  register_post_type('entrevistas', historia_coleccion_entrevistas());

  // if (is_plugin_active('pods/init.php')) {
  //   crearPods();
  //   registrarModeloPaginas();
  // }
}

add_action('after_setup_theme', 'historia_configurar_tema');

add_action('graphql_register_types', function () {
  register_graphql_field('Entrevista', 'ordenTranscripciones', [
    'type' => ['list_of' => 'Int'],
    'description' => 'Orden de transcripciones',
    'resolve' => function ($post) {
      global $wpdb;
      $field_id = 412; // Reemplaza este ID si cambia
      $results = $wpdb->get_col(
        $wpdb->prepare(
          "SELECT related_item_id FROM wp_podsrel WHERE field_id = %d AND item_id = %d ORDER BY weight",
          $field_id,
          $post->ID
        )
      );
      return array_map('intval', $results);
    }
  ]);

  register_graphql_field('Transcripcion', 'ordenAudios', [
    'type' => ['list_of' => 'Int'],
    'description' => 'Orden de audios',
    'resolve' => function ($post) {
      global $wpdb;
      $field_id = 413; // Reemplaza este ID si cambia
      $results = $wpdb->get_col(
        $wpdb->prepare(
          "SELECT related_item_id FROM wp_podsrel WHERE field_id = %d AND item_id = %d ORDER BY weight",
          $field_id,
          $post->ID
        )
      );
      return array_map('intval', $results);
    }
  ]);
});


// add_action('init', 'historia_registrar_colecciones');

