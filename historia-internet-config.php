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
Version: 1.0.10
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

/**
 * ==============================
 *  Admin: Publicar (Desplegar)
 * ==============================
 */

// Config por defecto; puedes sobreescribir en wp-config.php si quieres.
if (!defined('ENFLUJO_GH_OWNER')) {
  define('ENFLUJO_GH_OWNER', 'enflujo');
}
if (!defined('ENFLUJO_GH_REPO')) {
  define('ENFLUJO_GH_REPO', 'enflujo-historia-internet');
}
if (!defined('ENFLUJO_GH_WORKFLOW')) {
  define('ENFLUJO_GH_WORKFLOW', 'despliegue.yml');
}
if (!defined('ENFLUJO_GH_REF')) {
  define('ENFLUJO_GH_REF', 'main');
}

// Obtiene el token de GitHub con prioridad: opción guardada -> constante -> variable de entorno
function enflujo_historia_obtener_token_github()
{
  $opcion = get_option('enflujo_gh_token');
  if (!empty($opcion)) return $opcion;
  if (defined('GITHUB_TOKEN') && GITHUB_TOKEN) return GITHUB_TOKEN;
  $env = getenv('GITHUB_TOKEN');
  if ($env) return $env;
  return null;
}

// Compatibilidad: alias del nombre anterior utilizado en el proyecto
if (!function_exists('enflujo_historia_get_github_token')) {
  function enflujo_historia_get_github_token() {
    return enflujo_historia_obtener_token_github();
  }
}

add_action('admin_menu', function () {
  // Página de nivel superior
  add_menu_page(
    'Publicar',
    'Publicar',
    'manage_options',
    'enflujo-publicar',
    'enflujo_historia_render_publicar_page',
    'dashicons-cloud-upload',
    3
  );
});

function enflujo_historia_render_publicar_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
  }

  $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'publicar';

  // Guardar configuración si envían el formulario
  if ($tab === 'configuracion' && isset($_POST['enflujo_settings_submit'])) {
    check_admin_referer('enflujo_deploy_settings');
    $owner = isset($_POST['enflujo_gh_owner']) ? sanitize_text_field($_POST['enflujo_gh_owner']) : '';
    $repo = isset($_POST['enflujo_gh_repo']) ? sanitize_text_field($_POST['enflujo_gh_repo']) : '';
    $workflow = isset($_POST['enflujo_gh_workflow']) ? sanitize_text_field($_POST['enflujo_gh_workflow']) : '';
    $ref = isset($_POST['enflujo_gh_ref']) ? sanitize_text_field($_POST['enflujo_gh_ref']) : '';
    $token = isset($_POST['enflujo_gh_token']) ? sanitize_text_field($_POST['enflujo_gh_token']) : '';

    if ($owner)
      update_option('enflujo_gh_owner', $owner);
    if ($repo)
      update_option('enflujo_gh_repo', $repo);
    if ($workflow)
      update_option('enflujo_gh_workflow', $workflow);
    if ($ref)
      update_option('enflujo_gh_ref', $ref);
    if ($token)
      update_option('enflujo_gh_token', $token);

    echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>';
  }

  // Nonce para las llamadas AJAX relacionadas con Publicar
  $nonce = wp_create_nonce('enflujo_publicar_nonce');
  $last_run = get_option('enflujo_last_deploy_at');

  // Cargar valores actuales (opciones con defaults)
  $owner_val = get_option('enflujo_gh_owner', defined('ENFLUJO_GH_OWNER') ? ENFLUJO_GH_OWNER : 'enflujo');
  $repo_val = get_option('enflujo_gh_repo', defined('ENFLUJO_GH_REPO') ? ENFLUJO_GH_REPO : 'enflujo-historia-internet');
  $workflow_val = get_option('enflujo_gh_workflow', defined('ENFLUJO_GH_WORKFLOW') ? ENFLUJO_GH_WORKFLOW : 'despliegue.yml');
  $ref_val = get_option('enflujo_gh_ref', defined('ENFLUJO_GH_REF') ? ENFLUJO_GH_REF : 'main');
  $token_present = enflujo_historia_obtener_token_github() ? true : false;
  $base_url = admin_url('admin.php?page=enflujo-publicar');
  $url_publicar = esc_url(add_query_arg('tab', 'publicar', $base_url));
  $url_config = esc_url(add_query_arg('tab', 'configuracion', $base_url));
  ?>
  <div class="wrap">
    <h1>Publicar</h1>
    <p>Desde aquí puedes disparar el despliegue (build de Astro) mediante GitHub Actions.</p>

    <h2 class="nav-tab-wrapper">
      <a href="<?php echo $url_publicar; ?>" class="nav-tab <?php echo $tab === 'publicar' ? 'nav-tab-active' : ''; ?>">Publicar</a>
      <a href="<?php echo $url_config; ?>" class="nav-tab <?php echo $tab === 'configuracion' ? 'nav-tab-active' : ''; ?>">Configuración</a>
    </h2>

    <?php if ($tab === 'configuracion'): ?>
      <h2>Configuración de GitHub</h2>
      <form method="post" action="">
      <?php wp_nonce_field('enflujo_deploy_settings'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="enflujo_gh_owner">Owner</label></th>
          <td><input name="enflujo_gh_owner" id="enflujo_gh_owner" type="text" class="regular-text"
              value="<?php echo esc_attr($owner_val); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="enflujo_gh_repo">Repo</label></th>
          <td><input name="enflujo_gh_repo" id="enflujo_gh_repo" type="text" class="regular-text"
              value="<?php echo esc_attr($repo_val); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="enflujo_gh_workflow">Workflow (archivo .yml)</label></th>
          <td><input name="enflujo_gh_workflow" id="enflujo_gh_workflow" type="text" class="regular-text"
              value="<?php echo esc_attr($workflow_val); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="enflujo_gh_ref">Branch/Ref</label></th>
          <td><input name="enflujo_gh_ref" id="enflujo_gh_ref" type="text" class="regular-text"
              value="<?php echo esc_attr($ref_val); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="enflujo_gh_token">GitHub Token</label></th>
          <td>
            <input name="enflujo_gh_token" id="enflujo_gh_token" type="password" class="regular-text"
              value="<?php echo esc_attr(get_option('enflujo_gh_token', '')); ?>" />
            <label style="margin-left:10px;">
              <input type="checkbox" id="enflujo_gh_token_toggle" /> Mostrar
            </label>
            <p class="description">Se guarda en opciones de WordPress y sólo es visible para administradores.</p>
          </td>
        </tr>
      </table>
        <p>
          <button type="submit" name="enflujo_settings_submit" class="button button-secondary">Guardar cambios</button>
        </p>
      </form>

    <?php else: // Tab Publicar ?>

      <?php if (!$token_present): ?>
        <div class="notice notice-error">
          <p>
            Falta configurar el token de GitHub. Ve a la pestaña Configuración y agrega el token.
          </p>
        </div>
      <?php endif; ?>

      <p>
        <button id="enflujo-publicar-boton" class="button" style="background:#00a32a;border-color:#008a20;color:#fff;" 
          <?php echo !$token_present ? 'disabled' : ''; ?>
          title="<?php echo !$token_present ? 'Configura el token de GitHub en la pestaña Configuración' : 'Publicar sitio'; ?>">
          Publicar sitio
        </button>
        <span id="enflujo-publicar-estado" style="margin-left: 10px;"></span>
      </p>

      <div id="enflujo-progreso" style="display:none; max-width:480px;">
        <div style="background:#f1f1f1; border:1px solid #ccc; height:16px; position:relative;">
          <div id="enflujo-progreso-barra" style="background:#2271b1; width:10%; height:100%; transition: width .3s;"></div>
        </div>
        <small id="enflujo-progreso-texto">Iniciando despliegue…</small>
      </div>

      <hr />
      <p>
        <strong>Último despliegue:</strong>
        <span id="enflujo-ultimo-despliegue">
          <?php echo $last_run ? esc_html($last_run) : 'No hay registros.'; ?>
        </span>
      </p>
    <?php endif; ?>

    <script type="text/javascript">
      (function () {
  // Variables de interfaz
  const urlAjax = '<?php echo admin_url('admin-ajax.php'); ?>';
  const noncePublicar = '<?php echo esc_js($nonce); ?>';
  const entradaToken = document.getElementById('enflujo_gh_token');
  const alternarToken = document.getElementById('enflujo_gh_token_toggle');
  const boton = document.getElementById('enflujo-publicar-boton');
  const estadoEl = document.getElementById('enflujo-publicar-estado');
  const progreso = document.getElementById('enflujo-progreso');
  const barra = document.getElementById('enflujo-progreso-barra');
  const textoProgreso = document.getElementById('enflujo-progreso-texto');
  const ultimoEl = document.getElementById('enflujo-ultimo-despliegue');

        let temporizadorConsulta = null;
        if (alternarToken && entradaToken) {
          alternarToken.addEventListener('change', function () {
            entradaToken.type = this.checked ? 'text' : 'password';
          });
        }
        // Establece el progreso visual
        function establecerProgreso(porcentaje, texto) {
          if (!progreso || !barra || !textoProgreso) return;
          progreso.style.display = 'block';
          barra.style.width = Math.max(5, Math.min(100, porcentaje)) + '%';
          if (texto) textoProgreso.textContent = texto;
        }

        // Formatea una fecha ISO para mostrar
        function formatearHora(iso) {
          try { return new Date(iso).toLocaleString(); } catch (e) { return iso; }
        }

        // Consulta el estado del último flujo en GitHub Actions
        function consultarEstado() {
          fetch(urlAjax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'enflujo_publicar_estado', _wpnonce: noncePublicar })
          }).then(r => r.json()).then(data => {
            if (!data || !data.success) {
              if (estadoEl) estadoEl.textContent = (data && data.message) ? data.message : 'Error consultando estado';
              return;
            }
            const s = data.data;
            // s: {status, conclusion, html_url, created_at, updated_at}
            if (s.status === 'queued') { 
              establecerProgreso(15, 'En cola…'); 
              if (boton) { boton.disabled = true; boton.title = 'Ya hay un despliegue en cola. Espera a que termine.'; }
            }
            else if (s.status === 'in_progress') { 
              establecerProgreso(60, 'Compilando…'); 
              if (boton) { boton.disabled = true; boton.title = 'Ya hay un despliegue en progreso. Espera a que termine.'; }
            }
            else if (s.status === 'completed') {
              establecerProgreso(100, s.conclusion === 'success' ? 'Completado' : 'Falló');
              if (estadoEl) estadoEl.innerHTML = s.conclusion === 'success' ?
                'Despliegue completado ✓' : 'Despliegue falló ✗';
              if (s.updated_at && ultimoEl) { ultimoEl.textContent = formatearHora(s.updated_at); }
              if (boton) { boton.disabled = false; boton.title = 'Publicar sitio'; }
              if (temporizadorConsulta) { clearInterval(temporizadorConsulta); temporizadorConsulta = null; }
            }
          }).catch(() => {
            if (estadoEl) estadoEl.textContent = 'Error de red al consultar estado';
          });
        }

        // Dispara el flujo de publicación
        function iniciarDespliegue() {
          if (estadoEl) estadoEl.textContent = 'Enviando solicitud…';
          establecerProgreso(10, 'Iniciando…');
          if (boton) boton.disabled = true;
          fetch(urlAjax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'enflujo_publicar_disparar', _wpnonce: noncePublicar })
          }).then(r => r.json()).then(data => {
            if (!data || !data.success) {
              // Mensaje de error
              const msg = (data && data.message) ? data.message : 'No se pudo iniciar el despliegue';
              if (estadoEl) estadoEl.textContent = msg;
              // Si el servidor negó por despliegue existente, mantener deshabilitado y explicar
              if (data && data.data && data.data.html_url) {
                if (boton) { boton.disabled = true; boton.title = 'Hay un despliegue activo. Ver estado en GitHub.'; }
              } else {
                if (boton) { boton.disabled = false; boton.title = 'Publicar sitio'; }
              }
              return;
            }
            if (estadoEl) estadoEl.innerHTML = 'Despliegue iniciado. <a target="_blank" href="' + data.data.html_url + '">Ver en GitHub</a>';
            establecerProgreso(25, 'Solicitado…');
            if (temporizadorConsulta) clearInterval(temporizadorConsulta);
            temporizadorConsulta = setInterval(consultarEstado, 5000);
            setTimeout(consultarEstado, 2000);
          }).catch(() => {
            if (estadoEl) estadoEl.textContent = 'Error de red al iniciar despliegue';
            if (boton) boton.disabled = false;
          });
        }

        // Sólo si estamos en la pestaña Publicar (existe el botón) añadimos eventos y polling
        if (boton) {
          boton.addEventListener('click', iniciarDespliegue);
          setTimeout(() => { consultarEstado(); }, 500);
        }
      })();
    </script>
  </div>
  <?php
}

// Acción AJAX para disparar el despliegue
add_action('wp_ajax_enflujo_publicar_disparar', function () {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Permiso denegado'], 403);
  }
  check_ajax_referer('enflujo_publicar_nonce');

  $token = enflujo_historia_obtener_token_github();
  if (!$token) {
    wp_send_json_error(['message' => 'Falta GITHUB_TOKEN en el servidor'], 500);
  }

  // Lee configuración desde opciones con fallback a constantes o defaults
  $owner = get_option('enflujo_gh_owner', defined('ENFLUJO_GH_OWNER') ? ENFLUJO_GH_OWNER : 'enflujo');
  $repo = get_option('enflujo_gh_repo', defined('ENFLUJO_GH_REPO') ? ENFLUJO_GH_REPO : 'enflujo-historia-internet');
  $workflow = get_option('enflujo_gh_workflow', defined('ENFLUJO_GH_WORKFLOW') ? ENFLUJO_GH_WORKFLOW : 'despliegue.yml');
  $ref = get_option('enflujo_gh_ref', defined('ENFLUJO_GH_REF') ? ENFLUJO_GH_REF : 'main');

  // 1) Verificar si ya hay una ejecución en curso (in_progress o queued) para evitar builds simultáneos
  $check_headers = [
    'headers' => [
      'Authorization' => 'Bearer ' . $token,
      'Accept' => 'application/vnd.github+json',
      'X-GitHub-Api-Version' => '2022-11-28',
      'User-Agent' => 'WordPress/enflujo-historia-internet'
    ],
    'timeout' => 30
  ];

  $runs_in_progress_url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}/runs?per_page=1&branch=" . rawurlencode($ref) . "&status=in_progress";
  $res_in_prog = wp_remote_get($runs_in_progress_url, $check_headers);
  if (!is_wp_error($res_in_prog) && wp_remote_retrieve_response_code($res_in_prog) === 200) {
    $data = json_decode(wp_remote_retrieve_body($res_in_prog), true);
    if (!empty($data['workflow_runs'])) {
      $run = $data['workflow_runs'][0];
      wp_send_json_error([
        'message' => 'Ya hay un despliegue en progreso.',
        'html_url' => isset($run['html_url']) ? $run['html_url'] : ''
      ], 409);
    }
  }

  $runs_queued_url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}/runs?per_page=1&branch=" . rawurlencode($ref) . "&status=queued";
  $res_queued = wp_remote_get($runs_queued_url, $check_headers);
  if (!is_wp_error($res_queued) && wp_remote_retrieve_response_code($res_queued) === 200) {
    $data = json_decode(wp_remote_retrieve_body($res_queued), true);
    if (!empty($data['workflow_runs'])) {
      $run = $data['workflow_runs'][0];
      wp_send_json_error([
        'message' => 'Ya hay un despliegue en cola.',
        'html_url' => isset($run['html_url']) ? $run['html_url'] : ''
      ], 409);
    }
  }

  // 2) Si no hay en curso, despachar el workflow
  $url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}/dispatches";

  $body = [
    'ref' => $ref,
    // 'inputs' => ['modo' => 'completo', 'actor' => 'wp'] // si defines inputs en el workflow
  ];

  $res = wp_remote_post($url, [
    'headers' => [
      'Authorization' => 'Bearer ' . $token,
      'Accept' => 'application/vnd.github+json',
      'X-GitHub-Api-Version' => '2022-11-28',
      'User-Agent' => 'WordPress/enflujo-historia-internet'
    ],
    'body' => wp_json_encode($body),
    'timeout' => 30
  ]);

  if (is_wp_error($res)) {
    wp_send_json_error(['message' => $res->get_error_message()], 500);
  }

  $code = wp_remote_retrieve_response_code($res);
  if ($code !== 204) {
    $msg = wp_remote_retrieve_body($res);
    wp_send_json_error(['message' => 'GitHub respondió ' . $code . ': ' . $msg], 500);
  }

  // Obtener último run para devolver un enlace útil
  $runs_url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}/runs?per_page=1&branch=" . rawurlencode($ref);
  $runs = wp_remote_get($runs_url, [
    'headers' => [
      'Authorization' => 'Bearer ' . $token,
      'Accept' => 'application/vnd.github+json',
      'X-GitHub-Api-Version' => '2022-11-28',
      'User-Agent' => 'WordPress/enflujo-historia-internet'
    ],
    'timeout' => 30
  ]);
  $html_url = '';
  if (!is_wp_error($runs)) {
    $data = json_decode(wp_remote_retrieve_body($runs), true);
    if (!empty($data['workflow_runs'][0]['html_url'])) {
      $html_url = $data['workflow_runs'][0]['html_url'];
    }
  }

  wp_send_json_success(['message' => 'Despliegue iniciado', 'html_url' => $html_url]);
});

// Acción AJAX para consultar el estado del último despliegue
add_action('wp_ajax_enflujo_publicar_estado', function () {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Permiso denegado'], 403);
  }
  check_ajax_referer('enflujo_publicar_nonce');

  $token = enflujo_historia_obtener_token_github();
  if (!$token) {
    wp_send_json_error(['message' => 'Falta GITHUB_TOKEN en el servidor'], 500);
  }

  $owner = get_option('enflujo_gh_owner', defined('ENFLUJO_GH_OWNER') ? ENFLUJO_GH_OWNER : 'enflujo');
  $repo = get_option('enflujo_gh_repo', defined('ENFLUJO_GH_REPO') ? ENFLUJO_GH_REPO : 'enflujo-historia-internet');
  $workflow = get_option('enflujo_gh_workflow', defined('ENFLUJO_GH_WORKFLOW') ? ENFLUJO_GH_WORKFLOW : 'despliegue.yml');
  $ref = get_option('enflujo_gh_ref', defined('ENFLUJO_GH_REF') ? ENFLUJO_GH_REF : 'main');

  $runs_url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}/runs?per_page=1&branch=" . rawurlencode($ref);
  $res = wp_remote_get($runs_url, [
    'headers' => [
      'Authorization' => 'Bearer ' . $token,
      'Accept' => 'application/vnd.github+json',
      'X-GitHub-Api-Version' => '2022-11-28',
      'User-Agent' => 'WordPress/enflujo-historia-internet'
    ],
    'timeout' => 30
  ]);

  if (is_wp_error($res)) {
    wp_send_json_error(['message' => $res->get_error_message()], 500);
  }

  $code = wp_remote_retrieve_response_code($res);
  if ($code !== 200) {
    $msg = wp_remote_retrieve_body($res);
    wp_send_json_error(['message' => 'GitHub respondió ' . $code . ': ' . $msg], 500);
  }

  $data = json_decode(wp_remote_retrieve_body($res), true);
  $run = isset($data['workflow_runs'][0]) ? $data['workflow_runs'][0] : null;

  if (!$run) {
    wp_send_json_success(['status' => 'unknown']);
  }

  $payload = [
    'status' => $run['status'], // queued | in_progress | completed
    'conclusion' => isset($run['conclusion']) ? $run['conclusion'] : null, // success | failure | cancelled | null
    'html_url' => $run['html_url'],
    'created_at' => $run['created_at'],
    'updated_at' => $run['updated_at'],
  ];

  // Actualiza última fecha si terminó
  if ($payload['status'] === 'completed' && !empty($payload['updated_at'])) {
    update_option('enflujo_last_deploy_at', $payload['updated_at']);
  }

  wp_send_json_success($payload);
});

