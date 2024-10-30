<?php

/**
 * La extensión "Pods" tiene que estar instalada.
 * Acá creamos los campos para el formulario de cada tipo de página o colección
 */

function crearGrupo($llave, $nombre)
{
  return array(
    'name' => $llave,
    'label' => $nombre,
    'description' => '',
    'weight' => 0,
    'roles_allowed' => 'administrator',
    'meta_box_context' => 'normal',
    'meta_box_priority' => 'default',
  );
}

function crearPod($llave, $nombre, $llaveSingular, $llavePlural)
{
  return array(
    'name' => $llave,
    'label' => $nombre,
    'description' => '',
    'type' => 'post_type',
    'storage' => 'meta',
    'object' => $llave,
    'wpgraphql_enabled' => '1',
    'wpgraphql_all_fields_enabled' => '1',
    'wpgraphql_pick_format' => 'connection',
    'wpgraphql_file_format' => 'connection',
    'wpgraphql_singular_name' => $llaveSingular,
    'wpgraphql_plural_name' => $llavePlural,
  );
}

function crearCamposSEO($nombrePod)
{
  $campos = array(
    'descripcion' => array(
      'name' => 'descripcion',
      'label' => 'Descripción',
      'description' => 'Descripción corta que se ve cuando se comparte el enlace. Idealmente alrededor de 160 caracteres, máximo 300.',
      'type' => 'paragraph',
      'paragraph_max_length' => '300',
      'roles_allowed' => 'administrator',
      'paragraph_trim' => '1',
      'paragraph_trim_lines' => '1',
      'paragraph_trim_p_brs' => '1',
      'paragraph_trim_extra_lines' => '1',
      'paragraph_allow_html' => '0',
      'paragraph_sanitize_html' => '1',
      'paragraph_oembed' => '0',
      'paragraph_wptexturize' => '0',
      'paragraph_convert_chars' => '0',
      'paragraph_wpautop' => '0',
      'paragraph_allow_shortcode' => '0',
    ),
  );

  $grupo = crearGrupo('seo', 'SEO');
  pods_register_group($grupo, $nombrePod, $campos);
}


function registrarModeloLineaTiempo()
{
  $campos = array(
    'fecha' => array(
      'name' => 'fecha',
      'label' => 'Fecha',
      'description' => 'Año del evento',
      'type' => 'date',
      'date_type' => 'format',
      'date_format' => 'y',
      'required' => '1',
      // 'wpgraphql_singular_name' => 'fecha',
      // 'wpgraphql_plural_name' => 'fechas',
    ),

    'tipo_de_acontecimiento' => array(
      'name' => 'tipo_de_acontecimiento',
      'label' => 'Tipo de acontecimiento',
      'description' => '',
      'type' => 'pick',
      'pick_object' => 'custom-simple',
      'pick_format_type' => 'single',
      'pick_format_single' => 'dropdown',
      'pick_custom' => 'colombia | Acontecimiento en Colombia
tecnologico | Acontecimiento tecnológico',
      'required' => '1',
      // 'wpgraphql_singular_name' => 'tipo',
      // 'wpgraphql_plural_name' => 'tipos',
    ),
  );

  $lineaTiempo = crearPod('linea_tiempo', 'Línea de tiempo', 'evento', 'eventos');
  $grupoMeta = crearGrupo('metadatos', 'Metadatos');

  pods_register_type($lineaTiempo['post_type'], $lineaTiempo['name'], $lineaTiempo);
  pods_register_group($grupoMeta, $lineaTiempo['name'], $campos);
}

function registrarModeloPersonajes()
{
  $personajes = crearPod('personajes', 'Personajes', 'personaje', 'personajes');
  // $grupoMeta = crearGrupo('metadatos', 'Metadatos');


  pods_register_type($personajes['post_type'], $personajes['name'], $personajes);
  crearCamposSEO($personajes['name']);
}

function registrarModeloGlosario()
{
  $glosario = crearPod('glosario', 'Glosario', 'termmino', 'terminos');
  // $grupoMeta = crearGrupo('metadatos', 'Metadatos');


  pods_register_type($glosario['post_type'], $glosario['name'], $glosario);
  crearCamposSEO($glosario['name']);
}

function registrarModeloPaginas()
{
  $campos = array(
    'icono_a' => array(
      'name' => 'icono_a',
      'label' => 'Icono A',
      'description' => 'Ícono que describe la página, se pueden ver todas las opciones en: https://fonts.google.com/specimen/Yarndings+12',
      'type' => 'text',
      'text_max_length' => '1',
    ),

    'icono_b' => array(
      'name' => 'icono_b',
      'label' => 'Icono B',
      'description' => 'Ícono cuando se hace "hover" en el menú',
      'type' => 'text',
      'text_max_length' => '1',
    ),
  );

  $paginas = crearPod('page', 'Pages', 'page', 'pages');
  $grupoMeta = crearGrupo('metadatos', 'Metadatos');
  pods_register_type($paginas['post_type'], $paginas['name'], $paginas);
  pods_register_group($grupoMeta, $paginas['name'], $campos);
}