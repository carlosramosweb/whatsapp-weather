<?php
/*---------------------------------------------------------
Plugin Name: WhatsApp Meteorico
Plugin URI: https://profiles.wordpress.org/carlosramosweb/#content-plugins
Author: carlosramosweb
Author URI: https://criacaocriativa.com
Donate link: https://donate.criacaocriativa.com
Description: Whatsapp Weather - Esse plugin é uma versão BETA.
Text Domain: whatsapp-weather
Domain Path: /languages/
Version: 1.0.0
Requires at least: 3.5.0
Tested up to: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html 
------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Whatsapp_Weather' ) ) {	
	class Whatsapp_Weather {
		//..
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init_functions' ) );
		}
		//=>
		public function init_functions() {
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links_settings' ) );

			add_action( 'admin_menu', array( $this, 'register_admin_item_menu' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'register_admin_item_submenu' ), 10, 2 );
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ) );
			add_action( 'wp_head', array( $this, 'check_page_post_type' ) );
		}
		//=>
		public static function activate_plugin() {
			$whatsapp_weather_settings = get_option( 'wp_whatsapp_weather_settings' );
			if ( empty( $whatsapp_weather_settings ) ) {
				$whatsapp_weather_settings = array(
					'enabled'				=> "yes",
					'number_links_post'		=> 10,
					'max_count_url'			=> 10,
					'access_order'			=> "orderly",
				);
				update_option( 'wp_whatsapp_weather_settings', $whatsapp_weather_settings );
			}
		}
		//=>
		public static function deactivate_plugin() {
			//delete_option( 'wp_whatsapp_weather_settings' );
		}
		//=>
		public function check_page_post_type() {
			if ( ! is_admin() ) {
				if ( is_singular( 'whatsapp-weather' ) ) {
					$this->get_url_page_post_type( get_the_ID() );
				}
			}	
		}
		//=>
		public function get_url_page_post_type( $post_id ) {
			$whatsapp_weather_settings = get_option( 'wp_whatsapp_weather_settings' );
			$field_urls = get_post_meta( $post_id, '_field_urls', true );

			if ( count( $field_urls ) != "" ) {
				$i = 0;
				if ( $whatsapp_weather_settings['access_order'] == "orderly" ) {
					foreach ( $field_urls as $key => $field_url ) {
						if ( $field_url['count'] < $whatsapp_weather_settings['max_count_url'] & $i == 0 ) {
							$field_urls[$key] = ['url' => $field_url['url'], 'count' => ( $field_url['count'] + 1 ) ];
							update_post_meta( $post_id, '_field_urls', $field_urls );	
							$i++;
							echo '<meta http-equiv="refresh" content="0;url=' . $field_url['url'] . '"/>';
						}
					}
				}
				if ( $whatsapp_weather_settings['access_order'] == "rand" ) {
					$rand = array_rand( $field_urls );
					if ( $field_urls[$rand]['count'] < $whatsapp_weather_settings['max_count_url'] ) {
						$field_urls[$rand] = ['url' => $field_urls[$rand]['url'], 'count' => ( $field_urls[$rand]['count'] + 1 ) ];
						update_post_meta( $post_id, '_field_urls', $field_urls );	
						echo '<meta http-equiv="refresh" content="0;url=' . $field_urls[$rand]['url'] . '"/>';
					}
				}
			}
		}
		//=>
		public static function plugin_links_settings( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=whatsapp-weather' ) . '" title="Configuracões" class="edit">Configuracões</a>',
			);
			return array_merge( $action_links, $links );
		}
		//=>
		public static function register_admin_item_menu() {
			add_menu_page(
		        'Whatsapp Weather',
		        'Whatsapp Weather',
		        'manage_options',
		        'whatsapp-weather',
		        array( $this, 'whatsapp_weather_page_admin_callback' ),
		        'dashicons-admin-links',
		        25
		    );
		}
		//=>
		public static function register_admin_item_submenu() {
		    add_submenu_page( 
		    	'whatsapp-weather', 
		    	'Meus Links', 
		    	'Meus Links',
			    'manage_options', 
			    'edit.php?post_type=whatsapp-weather'
			);
		    add_submenu_page( 
		    	'whatsapp-weather', 
		    	'Adicionar Novo', 
		    	'Adicionar Novo',
			    'manage_options', 
			    'post-new.php?post_type=whatsapp-weather'
			);
		}
		//=>
		public static function register_post_type() {
		    $args = array(
		        'public'    			=> true,
		        'label'     			=> 'Posts WhatsApp',
		        'exclude_from_search'	=> true,
		        'public_queryable'		=> true,
		        'show_ui'				=> true,
		        'show_in_menu'			=> false,
		        'show_in_nav_menus'		=> false,
		        'show_in_admin_bar'		=> false,
		        'capability_type' 		=> 'post',
		        'hierarchical' 			=> false,
		        'supports'				=> array( 'title', 'author' ), 
		        // 'title', 'editor', 'comments', 'revisions', 'trackbacks', 'author', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields', and 'post-formats'
		    );
		    register_post_type( 'whatsapp-weather', $args );
		}
		//=>
		public static function register_meta_box() {
		    add_meta_box( 
		    	'whatsapp_weather_box_id',
		        'Lista de URLs desse Post',
		        array( $this, 'meta_box_fields_callback' ),
		        'whatsapp-weather',
		        'normal', 
		        'high',
		        null
		    );
		}
		//=>
		public function save_meta_box( $post_id ) {
			$post_field_url = $_POST['field-url'];
			if ( ! empty( $post_field_url ) ) {
				$field_urls = get_post_meta( $post_id, '_field_urls', true );
				if ( empty( $field_urls )  ) {
					$field_url_args = array(
						'url' 	=> $post_field_url,
						'count' => 0,
					);
					$field_urls = array(
						'0' => $field_url_args
					);
					update_post_meta( $post_id, '_field_urls', $field_urls );
				} else {
					$field_url_args = array(
						'url' 	=> $post_field_url,
						'count' => 0,
					);
					array_push( $field_urls, $field_url_args );
					update_post_meta( $post_id, '_field_urls', $field_urls );
				}
			}
		}
		//=>
		public function meta_box_fields_callback( $post ) {
			if ( ! empty( $post->ID ) ) {
				$field_urls = get_post_meta( $post->ID, '_field_urls', true );
			}
			$whatsapp_weather_settings = get_option( 'wp_whatsapp_weather_settings' );
			?>
			<div id="whatsapp-weather-fields">
				<div class="whatsapp-weather-field-url">
					<?php if ( count( $field_urls ) >= $whatsapp_weather_settings['number_links_post'] ) { ?>
					<div class="field-label">
						<label for="field-url">
							<strong>Esse Post atingiu o limite máximo de <?php echo $whatsapp_weather_settings['number_links_post']; ?> URLs cadastradas.</strong>
						</label>
					</div>
					<?php } else { ?>
					<div class="field-label">
						<label for="field-url">
							<strong>Digite uma URL completa:</strong>
						</label>
					</div>
					<div class="field-input">
						<span class="dashicons dashicons-admin-site dashicons-field-url"></span>
						<input type="url" class="field-url" name="field-url" placeholder="https://chat.whatsapp.com/código">
					</div>
					<div class="field-button">
						<button name="save" type="submit" class="button button-primary insert" id="publish">
							<span class="dashicons dashicons-plus"></span>
						</button>
					</div>
					<?php } ?>
					<div style="clear: both;"></div>
				</div>
				<br/><br/>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column">
								<span>URL</span>
							</th>
							<th scope="col" class="manage-column" style="width: 15%; text-align: center;">
								Contador
							</th>
							<th scope="col" class="manage-column" style="width: 10%; text-align: center;">
								Apagar
							</th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php
						if ( count( $field_urls ) == "" ) { ?>
							<tr>
								<td class="title column-title has-row-actions column-primary page-title">
									<strong>Nenhuma URL encontrada nesse POST.</strong>
								</td>
							</tr>
						<?php } else {
							foreach ( $field_urls as $key => $field_url ) { ?>
							<tr>
								<td class="title column-title has-row-actions column-primary page-title">
									<strong><?php echo $field_url['url']; ?></strong>
								</td>
								<td style="text-align: center;">
									<?php echo $field_url['count']; ?>
								</td>
								<td style="text-align: center;">
									<?php
									$update = "yes";
									$wpnonce = esc_attr( wp_create_nonce( 'whatsapp-weather-remove' ) );
									$remove_url = "admin.php?page=whatsapp-weather&tab=remove_url&key={$key}&post_id={$post->ID}&_update={$update}&_wpnonce={$wpnonce}"
									
									?>
									<a href="<?php echo esc_url( admin_url( $remove_url ) ); ?>" class="button remove">
										<span class="dashicons dashicons-no"></span>
									</a>
								</td>
							</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
                <br/>
                <span>
                	<strong>Obs:</strong> Limite Máximo de <?php echo $whatsapp_weather_settings['number_links_post']; ?> URLs por Post.
                </span>
			</div>
			<style type="text/css">
				#whatsapp-weather-fields .field-label {
					display: block;
					width: 100%;
					padding: 10px 0;
				}
				#whatsapp-weather-fields .field-input {
					display: inline-block;
					float: left;
					width: 92%;
				}
				#whatsapp-weather-fields .field-input .dashicons-field-url {
					position: absolute;
					color: darkgrey;
					margin: 6px;
				}
				#whatsapp-weather-fields input {
					display: block;
					padding-left: 30px;
					width: 100%;
				}
				#whatsapp-weather-fields .field-button {
					display: inline-block;
					float: right;
					text-align: right;
					width: 7%;
				}
				#whatsapp-weather-fields .insert{
					background: #00669b;
					border-color: #00669b;
					box-shadow: none;
					color: #fff;
					padding: 0px;
				}
				#whatsapp-weather-fields .remove{
					background: #a00;
					border-color: #a00;
					box-shadow: none;
					color: #fff;
					padding: 0px;
				}
				#whatsapp-weather-fields a:hover{
					opacity: 0.5;
				}
				#whatsapp-weather-fields a span, #whatsapp-weather-fields button span{
					padding: 5px 10px 0;
					height: auto;
				}
			</style>
			<?php
		}
		//=>
		public function whatsapp_weather_page_admin_callback() {
		        global $wpdb;
				$whatsapp_weather_settings = get_option( 'wp_whatsapp_weather_settings' );

				if( isset( $_POST['_update'] ) && isset( $_POST['_wpnonce'] ) ) {
					$message = "error";
					$_update = sanitize_text_field( $_POST['_update'] );
					$_wpnonce = sanitize_text_field( $_POST['_wpnonce'] );

					if( isset( $_wpnonce ) && isset( $_update ) ) {
						if ( ! wp_verify_nonce( $_wpnonce, "whatsapp-weather-update" ) ) {
							$message = "error";	
						} else {
							$whatsapp_weather_settings['enabled'] = isset( $_POST['enabled'] ) ? $_POST['enabled'] : 'no';
							$whatsapp_weather_settings['number_links_post'] = isset( $_POST['number_links_post'] ) ? $_POST['number_links_post'] : '10';
							$whatsapp_weather_settings['max_count_url'] = isset( $_POST['max_count_url'] ) ? $_POST['max_count_url'] : '10';
							$whatsapp_weather_settings['access_order'] = isset( $_POST['access_order'] ) ? $_POST['access_order'] : 'orderly';	

							update_option( 'wp_whatsapp_weather_settings', $whatsapp_weather_settings );
							$message = "updated";	
						}
					}
				}
				$whatsapp_weather_settings = get_option( 'wp_whatsapp_weather_settings' );

				if( isset( $_GET['tab'] ) ) {
					$tab = esc_attr( $_GET['tab'] );
					if ( $tab == "remove_url") {
						if( isset( $_GET['_update'] ) && isset( $_GET['_wpnonce'] ) ) {
							$update = sanitize_text_field( $_GET['_update'] );
							$wpnonce = sanitize_text_field( $_GET['_wpnonce'] );

							if( isset( $wpnonce ) && isset( $update ) ) {
								if ( wp_verify_nonce( $wpnonce, "whatsapp-weather-remove" ) ) {
									$post_id = $_GET['post_id'];
									$key = $_GET['key'];
									$field_urls = get_post_meta( $post_id, '_field_urls', true );
									if ( $field_urls ) {
										unset( $field_urls[$key] );
										sort( $field_urls );
										update_post_meta( $post_id, '_field_urls', $field_urls );
										?>
										<div id="wpwrap">
										    <h1>Whatsapp Meteorico</h1>
										    <p><strong>Atenção:</strong> Estamos trabalhando em sua solicitação.<p/>
										    <hr/>
										    Removendo URL chave ( <?php echo $key; ?> ) do POST ( <?php echo $post_id; ?> )...
										</div>
				    					<?php
										echo "<script>window.history.back();</script>";
										exit();
									}

								}
							}
						}

					}
				}
		        ?>

				<div id="wpwrap">
				    <h1>Whatsapp Meteorico</h1>
				    <p>Abaixo você pode configure o plugin preenchendo os dados gerais.<p/>	

				    <?php if( isset( $message ) ) { ?>
				        <div class="wrap">

				    		<?php if( $message == "updated" ) { ?>
				            <div id="message" class="updated notice is-dismissible" style="margin-left: 0px;">
				                <p>Sucesso! Os dados foram atualizações com sucesso!</p>
				                <button type="button" class="notice-dismiss">
				                    <span class="screen-reader-text">
				                        Dispensar este aviso.
				                    </span>
				                </button>
				            </div>
				            <?php } ?>

				            <?php if( $message == "error" ) { ?>
				            <div id="message" class="updated error is-dismissible" style="margin-left: 0px;">
				                <p>Erro! Não conseguimos fazer as atualizações!</p>
				                <button type="button" class="notice-dismiss">
				                    <span class="screen-reader-text">
				                        Dispensar este aviso.
				                    </span>
				                </button>
				            </div>
				        	<?php } ?>

				    	</div>
				    <?php } ?>
				    
				    <div class="wrap ">
			            <nav class="nav-tab-wrapper wc-nav-tab-wrapper">
			           		<a href="<?php echo esc_url( admin_url( 'admin.php?page=whatsapp-weather' ) ); ?>" class="nav-tab <?php if( $tab == "" ) { echo "nav-tab-active"; }; ?>">
								Configurações
			                </a>
			           		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=whatsapp-weather' ) ); ?>" class="nav-tab">
								Ver Lista de links
			                </a>
			            </nav>

			            <!--form-->
			        	<form method="POST" id="mainform" name="mainform" enctype="multipart/form-data">
			                <!---->
			                <table class="form-table">
			                    <tbody>
			                        <!---->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Habilita/Desabilitar:
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="enabled" value="yes" <?php if( $whatsapp_weather_settings['enabled'] == "yes" ) { echo 'checked="checked"'; } ?>>
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Total de links por Post:
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="number" required name="number_links_post" value="<?php echo $whatsapp_weather_settings['number_links_post']; ?>"  style=" min-width:400px; width:auto;">
			                                </label>
			                                <br/>
			                                <span>
			                                	<strong>Obs:</strong> Padrão é 10.
			                                </span>
			                            </td>
			                        </tr>
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Máximo de Acesso por URL:
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="number" required name="max_count_url" value="<?php echo $whatsapp_weather_settings['max_count_url']; ?>"  style=" min-width:400px; width:auto;">
			                                </label>
			                                <br/>
			                                <span>
			                                	<strong>Obs:</strong> Padrão é 10.
			                                </span>
			                            </td>
			                        </tr>
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Ordem de Acesso:
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                	<?php $access_order = $whatsapp_weather_settings['access_order']; ?>
			                                	<select name="access_order" style=" min-width:400px; width:auto;">
			                                		<option value="orderly" <?php if( $access_order == "orderly") { echo "selected"; } ?>>Ordenado</option>
			                                		<option value="rand" <?php if( $access_order == "rand") { echo "selected"; } ?>>Randômico</option>
			                                	</select>
			                                </label>
			                                <br/>
			                                <span>
			                                	<strong>Obs:</strong> Padrão é Ordenado.
			                                </span>
			                            </td>
			                        </tr>
			                        <!---->
			                   </tbody>
			                </table>
			                <!---->
			                <hr/>
			                <div class="submit">
			                    <button class="button-primary" type="submit">Salvar Alterações</button>
			                    <input type="hidden" name="_update" value="yes">
			                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'whatsapp-weather-update' ) ); ?>">
			                </div>
			                <!---->  
			            </form>
			        	<!---->    
				     </div>    
				</div>
			<?php
		}
		//..
	}
	new Whatsapp_Weather();
}