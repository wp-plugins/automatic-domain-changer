<?php
/*
Plugin Name: Automatic Domain Changer
Plugin URI: http://www.nuagelab.com/wordpress-plugins/auto-domain-change
Description: Automatically changes the domain of a WordPress blog
Author: NuageLab <wordpress-plugins@nuagelab.com>
Version: 1.0.1
License: GPLv2 or later
Author URI: http://www.nuagelab.com/wordpress-plugins
*/

// --

/**
 * Automatic Domain Changer class
 *
 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
 */
class auto_domain_change{
	private static $_instance = null;

	/**
	 * Bootstrap
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public static function boot()
	{
		if (self::$_instance === null) {
			self::$_instance = new auto_domain_change();
			self::$_instance->setup();
			return true;
		}
		
		return false;
	} // boot()


	/**
	 * Setup plugin
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function setup()
	{
		global $current_blog;

		// Add admin menu
		add_action('admin_menu', array(&$this, 'add_admin_menu'));

		// Add options
		add_option('auto_domain_change-https', false);
		add_option('auto_domain_change-www', true);

		// Load text domain
		load_plugin_textdomain('auto-domain-change', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

		// Check if the domain was changed
		if (is_admin()) {
			$this->check_domain_change();
			
			// Handle some actions
			if (isset($_POST['action'])) {
				require_once(ABSPATH .'wp-includes/pluggable.php');
				
				if (wp_verify_nonce($_POST['nonce'],$_POST['action'])) {
					$parts = explode('+',$_POST['action']);
					switch ($parts[0]) {
						case 'backup-database':
							return $this->do_backup();
						default:
							// ignore
							break;
					}
				}
			}			
		}
	} // setup()


	/**
	 * Check if domain has changed, and display admin notice if necessary
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	private
	 */
	private function check_domain_change()
	{
		if (!isset($_SERVER['HTTP_HOST'])) return false;

		$old_domain = get_option('auto_domain_change-domain');
		if (!$old_domain) {
			update_option('auto_domain_change-domain', $_SERVER['HTTP_HOST']);
			return;
		}

		if (($old_domain != $_SERVER['HTTP_HOST']) && (!isset($_POST['new-domain']))) {
			if ((isset($_GET['dismiss-domain-change'])) && ($_GET['dismiss-domain-change'])) {
				update_option('auto_domain_change-dismiss', $_SERVER['HTTP_HOST']);
			} else if (strtolower($_SERVER['HTTP_HOST']) != strtolower(get_option('auto_domain_change-dismiss'))) {
				add_action('admin_notices', array(&$this, 'add_admin_notice'));
			}
		}
	} // check_domain_change()


	/**
	 * Add admin notice action; added by check_domain_change()
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function add_admin_notice()
	{
		echo '<div class="update-nag">
			'.sprintf(__('The domain name of your WordPress blog appears to have changed! <a href="%1$s">Click here to update your config</a> or <a href="%2$s">dismiss</a>.','auto-domain-change'),
				'/wp-admin/tools.php?page='.basename(__FILE__),
				add_query_arg('dismiss-domain-change','1')
			).'
		</div>';
	} // add_admin_notice()


	/**
	 * Add admin menu action; added by setup()
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function add_admin_menu()
	{
		add_management_page(__("Change Domain",'auto-domain-change'), __("Change Domain",'auto-domain-change'), 'update_core', basename(__FILE__), array(&$this, 'admin_page'));
	} // add_admin_menu()


	/**
	 * Admin page action; added by add_admin_menu()
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function admin_page()
	{
		if (isset($_POST['https-domain'])) {
			update_option('auto_domain_change-https', $_POST['https-domain']);
		}
		if (isset($_POST['www-domain'])) {
			update_option('auto_domain_change-www', $_POST['www-domain']);
		}
		if (isset($_POST['action'])) {
			if (wp_verify_nonce($_POST['nonce'],$_POST['action'])) {
				$parts = explode('+',$_POST['action']);
				switch ($parts[0]) {
					case 'change-domain':
						if (!$_POST['accept-terms']) {
							$error_terms = true;
						} else {
							return $this->do_change($_POST['old-domain'], $_POST['new-domain']);
						}
						break;
					default:
						// ignore
				}
			}
		}

		if (!isset($error_terms)) $error_terms = false;

		echo '<div class="wrap">';

		echo '<div id="icon-tools" class="icon32"><br></div>';
		echo '<h2>'.__('Change Domain','auto-domain-change').'</h2>';
		echo '<form method="post">';

		$action = 'change-domain+'.uniqid();
		
		wp_nonce_field($action,'nonce');
		echo '<input type="hidden" name="action" value="'.$action.'" />';

		echo '<table class="form-table">';
		echo '<tbody>';
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="old-domain">'.__('Change domain from: ','auto-domain-change').'</label></th>';
		echo '<td>http://<input class="regular-text" type="text" name="old-domain" id="old-domain" value="'.esc_html(get_option('auto_domain_change-domain')).'" /></td>';
		echo '</tr>';

		echo '<tr valign="top">';
		echo '<th scope="row"><label for="new-domain">'.__('Change domain to: ','auto-domain-change').'</label></th>';
		echo '<td>http://<input class="regular-text" type="text" name="new-domain" id="new-domain" value="'.esc_html($_SERVER['HTTP_HOST']).'" /></td>';
		echo '</tr>';

		echo '<tr valign="top">';
		echo '<td colspan="2"><input type="checkbox" name="https-domain" id="https-domain" value="1" '.
			(get_option('auto_domain_change-https') ? 'checked="checked"' : ''). ' /> <label for="https-domain">'.__('Also change secure <code>https</code> links','auto-domain-change').'</label></td>';
		echo '</tr>';

		echo '<tr valign="top">';
		echo '<td colspan="2"><input type="checkbox" name="www-domain" id="www-domain" value="1" '.
			(get_option('auto_domain_change-www') ? 'checked="checked"' : ''). ' /> <label for="www-domain">'.__('Change both <code>www.old-domain.com</code> and <code>old-domain.com</code> links','auto-domain-change').'</label></td>';
		echo '</tr>';

		echo '<tr valign="top">';
		echo '<td colspan="2"><input type="checkbox" name="accept-terms" id="accept-terms" value="1" /> <label for="accept-terms"'.($error_terms?' style="color:red;font-weight:bold;"':'').'>'.__('I have backed up my database and will assume the responsability of any data loss or corruption.','auto-domain-change').'</label></td>';
		//echo '<td colspan="2"><input type="checkbox" name="accept-terms" id="accept-terms" value="1" /> <label for="accept-terms"'.($error_terms?' style="color:red;font-weight:bold;"':'').'>'.__('I have <a id="adc-backup-button" href="">backed up my database</a> and will assume the responsability of any data loss or corruption.','auto-domain-change').'</label></td>';
		echo '</tr>';

		echo '</tbody></table>';

		echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="'.esc_html(__('Change domain','auto-domain-change')).'"></p>';

		echo '</form>';
		
		echo '<form method="post" id="adc-backup-db">';
		$action = 'backup-database+'.uniqid();
		wp_nonce_field($action,'nonce');
		echo '<input type="hidden" name="action" value="'.$action.'" />';
		echo '</form>';
		echo <<<EOD
<script>
(function($){
	$('#adc-backup-button').click(function(ev){
		ev.preventDefault();
		$('#adc-backup-db').submit();
	});
})(jQuery);
</script>	
EOD;
		echo '</div>';
	} // admin_page()


	/**
	 * Change domain. This is where the magic happens.
	 * Called by admin_page() upon form submission.
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	private
	 */
	private function do_change($old, $new)
	{
		global $wpdb;

		@set_time_limit(0);

		echo '<div class="wrap">';

		echo '<div id="icon-tools" class="icon32"><br></div>';
		echo '<h2>Changing domain</h2>';
		echo '<pre>';
		printf(__('Old domain: %1$s','auto-domain-changer').'<br>', $old);
		printf(__('New domain: %1$s','auto-domain-changer').'<br>', $new);
		echo '<hr>';

		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME);
		mysql_query('SET NAMES '.DB_CHARSET);
		if (function_exists('mysql_set_charset')) mysql_set_charset(DB_CHARSET);

		$ret = mysql_query('SHOW TABLES;');
		$tables = array();
		while ($row = mysql_fetch_assoc($ret)) {
			$row = array_values($row);
			$tables [] = reset($row);
		}

		foreach ($tables as $t) {
			// Skip if the table name doesn't match the wordpress prefix
			if (substr($t,0,strlen($wpdb->prefix)) != $wpdb->prefix) continue;

			// Get table indices
			$ret = mysql_query('SHOW INDEX FROM '.$t);
			$id = null;
			while ($row = mysql_fetch_assoc($ret)) {
				if ($row['Key_name'] == 'PRIMARY') {
					$id = $row['Column_name'];
					break;
				} else if ($row['Non_unique'] == 0) {
					$id = $row['Column_name'];
				}
			}
			if ($id === null) {
				// No unique index found, skip table.
				printf(__('Skipping table %1$s because no unique id','auto-domain-change').'<br/>', $t);
				continue;
			}

			printf(__('Processing table %1$s','auto-domain-change').'<br/>', $t);


			// Process all rows
			$ret = mysql_query('SELECT * FROM '.$t);
			while ($row = mysql_fetch_assoc($ret)) {
				$fields = array();
				$sets = array();
				
				// Process all columns
				foreach ($row as $k=>$v) {
					// Save original value
					$ov = $v;
					
					// Process value
					$v = $this->processValue($v, $old, $new);

					// If value changed, replace it
					if ($ov != $v) {
						$sets[] = '`'.$k.'`="'.mysql_real_escape_string($v).'"';
					}
				}

				// Update table if we have something to set
				if (count($sets) > 0) {
					$sql = 'UPDATE '.$t.' SET '.implode(',',$sets).' WHERE `'.$id.'`='.$row[$id].' LIMIT 1;';
					mysql_query($sql);
				}
			}
		}

		update_option('auto_domain_change-domain', $new);
		echo '</pre>';
		echo '<hr>';
		echo '<form method="post"><input type="submit" value="'.esc_html(__('Back','auto-domain-change')).'" />';
	} // do_change()
	
	
	private function processValue($v, $old, $new) 
	{
		$sfalse = serialize(false);
		$jfalse = json_encode(false);
		$jnull = json_encode(null);
	
		$sv = @unserialize($v);
		$jv = @json_decode($v);
		$serialized = $json = false;
		if (($sv !== false) || ($sv == $sfalse)) {
			// Column value was serialized
			$v = $sv;
			$serialized = true;
		} else if (($jv !== null) && ($jv != $v) && ($jv != $jfalse) && ($jv != $jnull)) {
			// Column value was JSON encoded
			$v = $jv;
			$json = true;
		} /*else {
			// Column value was not serialized
		}*/

		if (($serialized) && (is_string($v))) {
			// Reprocess in case of double serialize done by sketchy plugins
			$v = $this->processValue($v, $old, $new);
		} else {
			// Replace
			$this->replace($v, $old, $new);
		}

		// Reserialize if needed
		if ($serialized) $v = serialize($v);
		elseif ($json) $v = json_encode($v);
		
		return $v;
	}
	
	/**
	 * Change domain. This is where the magic happens.
	 * Called by admin_page() upon form submission.
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	private
	 */
	private function do_backup()
	{
		global $wpdb;

		@set_time_limit(0);

		$fn = preg_replace('/[^a-zA-Z0-9_\-]/', '_', preg_replace(',http(|s)://,i','',get_bloginfo('url'))).'-'.date('Ymd-His').'.sql';
		
		header('Content-Type: text/plain; charset="UTF-8"');
		header('Content-Disposition: attachment; filename="'.$fn.'"');

		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME);
		mysql_query('SET NAMES '.DB_CHARSET);
		if (function_exists('mysql_set_charset')) mysql_set_charset(DB_CHARSET);

		$ret = mysql_query('SHOW TABLES;');
		$tables = array();
		while ($row = mysql_fetch_assoc($ret)) {
			$row = array_values($row);
			$tables [] = reset($row);
		}

		foreach ($tables as $t) {
			// Skip if the table name doesn't match the wordpress prefix
			if (substr($t,0,strlen($wpdb->prefix)) != $wpdb->prefix) continue;

			// Get table indices
			$ret = mysql_query('SHOW CREATE TABLE '.$t);
			$id = null;
			while ($row = mysql_fetch_assoc($ret)) {
				$ct = $row['Create Table'];
				echo $ct.';'.PHP_EOL;
			}

			// Process all rows
			$ret = mysql_query('SELECT * FROM '.$t);
			while ($row = mysql_fetch_assoc($ret)) {
				$ak = array();
				$av = array();
				foreach ($row as $k=>$v) {
					$ak[] = '`'.mysql_real_escape_string($k).'`';
					if ($v === null) $av[] = 'NULL';
						else $av[] = '"'.mysql_real_escape_string($v).'"';
				}
				printf('INSERT INTO `%1$s` (%2$s) VALUES (%3$s);'.PHP_EOL, $t, implode(',',$ak), implode(',', $av));
			}
			echo PHP_EOL;
			echo PHP_EOL;
		}
		
		die;
	} // do_backup()


	/**
	 * Replace domain in data.
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @param	mixed		$v		Data to search and replace in
	 * @param	string		$old	Old domain name
	 * @param	string		$new	New domain name
	 * @return	mixed				Modified data
	 * @access	private
	 */
	private function replace(&$v, $old, $new)
	{
		$protocols = array('http');
		if (get_option('auto_domain_change-https')) $protocols[] = 'https';
		$domains = array($old=>$new);
		if (get_option('auto_domain_change-www')) {
			$hold = preg_replace('/^www\./i', '', $old);
			if (strtolower($hold) != strtolower($old)) $domains[$hold] = $new;
			$hold = 'www.'.$hold;
			if (strtolower($hold) != strtolower($old)) $domains[$hold] = $new;
		}

		if ((is_array($v)) || (is_object($v))) {
			foreach ($v as &$vv) {
				$this->replace($vv, $old, $new);
			}
		} else if (is_string($v)) {
			foreach ($protocols as $protocol) {
				foreach ($domains as $o=>$n) {
					$v = preg_replace(','.$protocol.'://'.preg_quote($o,',').',i',$protocol.'://'.$n, $v);
				}
			}
		}

		return $v;
	} // replace()
} // auto_domain_change class


// Initialize
auto_domain_change::boot();
