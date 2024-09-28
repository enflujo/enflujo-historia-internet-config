<?php
/**
 * @package Historia del Internet Configuración
 * @version 1.0.0
 */
/*
Plugin Name: Historia del Internet Configuración
Plugin URI: https://github.com/enflujo/enflujo-historia-internet-config
Description: Esta extensión configura los modelos y configuración en WordPress para el proyecto Historia del Internet.
Author: Laboratorio EnFlujo
Version: 1.0.0
Author URI: https://enflujo.com
*/

function historia_configurar_tema()
{
  add_theme_support('post-thumbnails');
}

function historia_registrar_colecciones()
{

  register_post_type(
    'linea_tiempo',
    array(
      'labels' => array(
        'name' => __('Línea de tiempo', 'enflujo'),
        'singular_name' => __('Evento', 'enflujo'),
        'add_new' => __(text: 'Añadir evento', domain: 'enflujo'),
        'edit_item' => __(text: 'Editar evento', domain: 'enflujo')
      ),
      'public' => true,
      'has_archive' => true,
      'menu_position' => 4,
      'menu_icon' => 'dashicons-chart-line',
      'supports' => array('title', 'editor', 'thumbnail'),
      'rewrite' => array('slug' => 'eventos')
    )
  );
}

add_action('after_setup_theme', 'historia_configurar_tema');
add_action('init', 'historia_registrar_colecciones');