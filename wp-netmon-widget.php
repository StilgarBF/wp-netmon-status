<?php
/**
 * Plugin Name: WP Netmon-Widget
 * Plugin URI: http://blog.projekt2k.de
 * Description: Shows Netmon-Stats as a widget
 * Version: 1.0.0
 * Author: Tino Dietel
 * Author URI: http://blog.projekt2k.de
 * License: GPL2
 */

defined('ABSPATH') or die;

class netmon_widget extends WP_Widget
{

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct()
	{
		parent::__construct(
			'netmon_widget', // Base ID
			__( 'NetmonStatus'), // Name
			array( 'description' => __( 'Zeigt Netmon-Status in einem Widget' ), ) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance )
	{
		$url = rtrim($instance['netmonurl'], '/');
		$url = $url.'/api/rest/api.php?rquest=routerlist';

		if($result = $this->_callAPI($url))
		{
			$xml = simplexml_load_string($result);

			if(!$xml)
			{
				return;
			}

			$routers = $xml->routerlist->router;

			if(!$routers)
			{
				return;
			}


			$total = 0;
			$online = 0;
			$offline = 0;
			$unknown = 0;
			$clients = 0;

			foreach($routers AS $router)
			{
				$total++;
				$clients += $router->statusdata->client_count;

				switch ($router->statusdata->status)
				{
					case 'online':
						$online++;
						break;

					case 'offline':
						$offline++;
						break;
					
					default:
						$unknown++;
						break;
				}
			}
			?>
				<div class="widget widget_text netmon_status">
					<h3 class="widget-title">Netzstatus</h3>
					<?php
						if(!empty($instance['notetext']))
						{
							echo '<p>'.$instance['notetext'].'</p>';
						}
					?>
					<div class="textwidget" style="font-size: 1.8em">
						<div class="status_block"
							style="color:#007B0F;"
							title="Router online">
							<img src="<?php echo plugins_url( 'img/status_up_big.png', __FILE__ );?>" alt="Router online">
							<br /><?php echo $online; ?>
						</div><!--
						--><div class="status_block"
							style="color:#CB0000;"
							title="Router offline">
							<img src="<?php echo plugins_url( 'img/status_down_big.png', __FILE__ );?>" alt="Router offline">
							<br /><?php echo $offline; ?>
						</div><!--
						--><div class="status_block"
							style="color:#F8C901;"
							title="Routerstatus unklar">
							<img src="<?php echo plugins_url( 'img/status_pending_big.png', __FILE__ );?>" alt="Routerstatus unbekannt">
							<br /><?php echo $unknown; ?>
						</div><!--
						--><div class="status_block"
							style="color:#007B0F;"
							title="Freifunknutzer">
							<img src="<?php echo plugins_url( 'img/client-logo.png', __FILE__ );?>" alt="Clients">
							<br /><?php echo $clients; ?>
						</div>
					</div>
				</div>
				<style>
					.netmon_status .status_block {
						display:		inline-block;
						text-align:		center;
						width:			25%;
					}
				</style>
			<?php
		}
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance )
	{
		$netmonurl = ! empty( $instance['netmonurl'] ) ? $instance['netmonurl'] : __( 'NetmonPfad' );
		$notetext = ! empty( $instance['notetext'] ) ? $instance['notetext'] : '';
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'netmonurl' ); ?>"><?php _e( 'Pfad zu Netmon:' ); ?></label> 
		<input class="widefat"
			id="<?php echo $this->get_field_id( 'netmonurl' ); ?>"
			name="<?php echo $this->get_field_name( 'netmonurl' ); ?>"
			type="text"
			value="<?php echo esc_attr( $netmonurl ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'notetext' ); ?>"><?php _e( 'Optionaler Hinwesitext:' ); ?></label> 
		<input class="widefat"
			id="<?php echo $this->get_field_id( 'notetext' ); ?>"
			name="<?php echo $this->get_field_name( 'notetext' ); ?>"
			type="text"
			value="<?php echo esc_attr( $notetext ); ?>">
		</p>
		<?php 
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */	
	public function update( $new_instance, $old_instance )
	{
		$instance = array();
		$instance['netmonurl'] = ( !empty( $new_instance['netmonurl'] ) ) ? strip_tags( $new_instance['netmonurl'] ) : '';
		$instance['notetext'] = ( !empty( $new_instance['notetext'] ) ) ? strip_tags( $new_instance['notetext'] ) : '';

		return $instance;
	}

	/**
	 * fetch something with curl (GET only)
	 * @param string $url
	 * @param return string
	 */
	private function _callAPI($url)
	{
		$curl = curl_init();

		// Optional Authentication:
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "username:password");

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}
}

add_action( 'widgets_init', function()
{
	register_widget( 'netmon_widget' );
});
