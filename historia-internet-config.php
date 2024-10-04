<?php
/**
 * @package Historia del Internet Configuración
 * @version 1.0.0
 */
/*
Plugin Name: Historia del Internet Configuración
Plugin URI: https://github.com/enflujo/enflujo-historia-internet-config
GitHub Plugin URI: https://github.com/enflujo/enflujo-historia-internet-config
Primary Branch: main
Description: Esta extensión configura los modelos y configuración en WordPress para el proyecto Historia del Internet.
Author: Laboratorio EnFlujo
Version: 1.0.01
Author URI: https://enflujo.com
*/

require_once 'colecciones.php';

function historia_configurar_tema()
{
  add_theme_support('post-thumbnails');
}

function historia_registrar_colecciones()
{
  register_post_type('linea_tiempo', historia_coleccion_linea());
  register_post_type('personajes', historia_coleccion_personajes());
}

add_action('after_setup_theme', 'historia_configurar_tema');
add_action('init', 'historia_registrar_colecciones');
