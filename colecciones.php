<?php

// Íconos en https://developer.wordpress.org/resource/dashicons/

function historia_coleccion_linea()
{
  return array(
    'labels' => array(
      'name' => __('Línea de tiempo', 'enflujo'),
      'singular_name' => __('Evento', 'enflujo'),
      'add_new' => __(text: 'Añadir evento', domain: 'enflujo'),
      'edit_item' => __(text: 'Editar evento', domain: 'enflujo')
    ),
    'public' => true,
    'has_archive' => false,
    'menu_position' => 4,
    'menu_icon' => 'dashicons-chart-line',
    'supports' => array('title', 'editor', 'thumbnail'),
    'rewrite' => array('slug' => 'eventos'),
    'taxonomies' => array('category', 'post_tag'),
    'show_in_graphql' => true,
    'graphql_single_name' => 'evento',
    'graphql_plural_name' => 'eventos',
    'publicly_queryable' => true
  );
}

function historia_coleccion_personajes()
{
  return array(
    'labels' => array(
      'name' => __('Personajes', 'enflujo'),
      'singular_name' => __('Personaje', 'enflujo'),
      'add_new' => __(text: 'Añadir personaje', domain: 'enflujo'),
      'edit_item' => __(text: 'Editar personaje', domain: 'enflujo')
    ),
    'public' => true,
    'has_archive' => false,
    'menu_position' => 4,
    'menu_icon' => 'dashicons-groups',
    'supports' => array('title', 'editor', 'thumbnail'),
    'rewrite' => array('slug' => 'personajes'),
    'taxonomies' => array('category', 'post_tag'),
    'show_in_graphql' => true,
    'graphql_single_name' => 'personaje',
    'graphql_plural_name' => 'personajes',
    'publicly_queryable' => true
  );
}

function historia_coleccion_glosario()
{
  return array(
    'labels' => array(
      'name' => __('Glosario', 'enflujo'),
      'singular_name' => __('Término', 'enflujo'),
      'add_new' => __(text: 'Añadir término', domain: 'enflujo'),
      'edit_item' => __(text: 'Editar término', domain: 'enflujo')
    ),
    'public' => true,
    'has_archive' => false,
    'menu_position' => 4,
    'menu_icon' => 'dashicons-book',
    'supports' => array('title', 'editor', 'thumbnail'),
    'rewrite' => array('slug' => 'glosario'),
    'taxonomies' => array('category', 'post_tag'),
    'show_in_graphql' => true,
    'graphql_single_name' => 'termino',
    'graphql_plural_name' => 'glosario',
    'publicly_queryable' => true
  );
}

function historia_coleccion_transcripciones()
{
  return array(
    'labels' => array(
      'name' => __('Transcripciones', 'enflujo'),
      'singular_name' => __('Transcripión', 'enflujo'),
      'add_new' => __(text: 'Añadir transcripción', domain: 'enflujo'),
      'edit_item' => __(text: 'Editar transcripción', domain: 'enflujo'),
      'add_new_item' => __(text: 'Añadir transcripción', domain: 'enflujo'),
    ),
    'public' => true,
    'has_archive' => false,
    'menu_position' => 4,
    'menu_icon' => 'dashicons-media-text',
    'supports' => array('title', 'thumbnail'),
    'rewrite' => array('slug' => 'transcripciones'),
    'taxonomies' => array('category', 'post_tag'),
    'show_in_graphql' => true,
    'graphql_single_name' => 'transcripcion',
    'graphql_plural_name' => 'transcripciones',
    'publicly_queryable' => true
  );
}
