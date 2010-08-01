<?php
/*
Plugin Name: SMSCOIN VIP
Plugin URI: http://smscoin.com/software/engine/WordPress/
Description: Этот плагин позволяет обеспечить платный доступ к вашему сайту. В ответ на присланное смс-сообщение пользователю приходит короткий текстовый пароль (ключ), после активации которого пользователь переходит в группу VIP. И получает доступ к статьям которые не доступны для обычных пользователей.
Version: 1.2
Author:  SMSCOIN.COM
Author URI: http://smscoin.com/
*/
/*  Copyright 2009  SMSCOIN  */

###
#  События
###
add_action('activate_smscoin_rpayment/smscoin_rpayment.php', 'smscoin_rpayment_activation');
add_action('deactivate_smscoin_rpayment/smscoin_rpayment.php', 'smscoin_rpayment_deactivation');
add_action('smscoin_rpayment_cron', 'smscoin_rpayment_tariffs_cron');
add_action('admin_menu', 'smscoin_rpayment_add_pages');

add_filter('the_content', 'smscoin_rpayment_post_filter');

###
#  Localization
#  Локализация
###
$currentLocale = get_locale();
if(!empty($currentLocale)) {
	$moFile = dirname(__FILE__) . "/lang/smscoin_rpayment-" . $currentLocale . ".mo";
	if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('smscoin_rpayment', $moFile);
}

###
#  Activating cron function
#  Функция активации крона
###
function smscoin_rpayment_activation() {
	wp_schedule_event(time(), 'hourly', 'smscoin_rpayment_cron');
	smscoin_rpayment_create_table();
}
###
#  Deactivating cron function
##  Функция деактивации крона
###
function smscoin_rpayment_deactivation() {
	global $wpdb,$table_prefix;
	$table_name = $table_prefix . 'vkeys';
	wp_clear_scheduled_hook('smscoin_rpayment_cron');
	$wpdb->query("DROP TABLE `".$table_name."`");
	$wpdb->query("DROP TABLE `".$table_prefix."vvip`");
}
###
#  Creating script function
#  Функция создания скрипта
###
function smscoin_rpayment_add_script() {
	$wpurl = get_bloginfo('wpurl');
	$key_id = intval(get_option('smscoin_rpayment_key_id'));

	$str = '
		<link rel="stylesheet" href="'.$wpurl.'/wp-content/plugins/smscoin_rpayment/viewer.css" type="text/css" />
		<script src="'.$wpurl.'/wp-content/plugins/smscoin_rpayment/dropdown.js" type="text/javascript"></script>
		<script type="text/javascript">
			JSON_URL = "'.$wpurl.'/wp-content/plugins/smscoin_rpayment/data/local'.((function_exists('gzopen') && get_option('smscoin_rpayment_is_gzip') == 1)? '.json':'.js').'"
			SERVICE = "'.$key_id.'";
			INCLUDING_VAT = "'.__('including VAT','smscoin_rkey').'";
			WITHOUT_VAT = "'.__('without VAT','smscoin_rkey').'";
		</script>
		<script type="text/javascript">
		//<![CDATA[
		function hideAll() {
			var allDivs = document.getElementsByTagName(\'div\');
			for (var div in allDivs) {
				if (belongsToClass(allDivs[div], \'div_sms\')) {
					allDivs[div].style.display = \'none\';
				}
			}
		}
		//]]>
		</script>';

	return $str;
}

###
#  Creating table function
#  Функция создания таблицы
###
function smscoin_rpayment_create_table() {
	global $wpdb,$table_prefix;
	$table_name = $table_prefix . 'vkeys';
		$sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
			`k_status` tinyint(1) unsigned NOT NULL default '0',
			`k_key` int(10) unsigned NOT NULL default '0',
			`k_pair` varchar(16) character set utf8 NOT NULL,
			`k_country` varchar(2) character set utf8 NOT NULL,
			`k_provider` varchar(64) character set utf8 NOT NULL,
			`k_text` varchar(255) character set utf8 NOT NULL,
			`k_cost_local` decimal(6,2) NOT NULL,
			`k_created` int(10) unsigned NOT NULL default '0',
			`k_timeout` int(10) unsigned NOT NULL default '0',
			`k_limit_start` int(10) unsigned NOT NULL default '0',
			`k_limit_current` int(10) unsigned NOT NULL default '0',
			`k_first_access` int(10) unsigned NOT NULL default '0',
			`k_first_ip` varchar(32) character set utf8 NOT NULL,
			`k_first_from` varchar(255) character set utf8 NOT NULL,
			UNIQUE KEY `k_key` (`k_key`,`k_pair`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_prefix."vvip (
				uid int(10) unsigned NOT NULL default '0',
				pair_time int(10) unsigned NOT NULL default '0',
				UNIQUE KEY (uid)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		$wpdb->query($sql);
}

###
#  Creating page function
#  Функция создания страницы
###
function smscoin_rpayment_add_pages() {
	if (function_exists('add_menu_page')) {
		add_menu_page('SmsCoin VIP', 'SmsCoin VIP', 8, __FILE__, 'smscoin_rpayment_list', plugins_url('smscoin_rpayment/images/dot.png'));
	}
	if (function_exists('add_submenu_page')) {
		add_submenu_page(__FILE__, 'List1', __('List','smscoin_rpayment'), 8, __FILE__, 'smscoin_rpayment_list');
		add_submenu_page(__FILE__, 'Tarifs1', __('Tarifs','smscoin_rpayment'), 8, 'Tarifs1', 'smscoin_rpayment_tariffs');
		add_submenu_page(__FILE__, 'Settings1', __('Settings','smscoin_rpayment'), 8, 'Settings1', 'smscoin_rpayment_settings_page');
	}
}

###
#  Creating numbered links function
#  Функция создания номеров ссылок
###
function smscoin_vip_paging($sms_num_row,$rpp) {
	$ii=0;
	if($sms_num_row == 0) return '';
	$ret_str = '<div>'.__('Pages : ','smscoin_rpayment').' ';
	while( $sms_num_row>0 ) {
		$GP = $_GET+$_POST;
		unset($GP['sms_page']);
		unset($GP['page']);
		unset($GP['del']);
		unset($GP['edit']);
		$ret_str .= '<a href="admin.php?page=smscoin_rpayment/smscoin_rpayment.php&amp;sms_page='.($ii+1).'&amp;'.http_build_query($GP).'">['.($ii+1).']</a> ';
		$sms_num_row -= $rpp;
		$ii++;
	}
	return $ret_str.'</div>';
}

###
#  Creating page with password statistics function
#  Функция создания страницы статистики паролей
###
function smscoin_rpayment_list($key_id) {
	global $wpdb, $table_prefix;

	$str = '';
	$table_name = $table_prefix . 'vkeys';
	$str .= '<h1>'.__('List received sms','smscoin_rpayment').'</h1>';

	$smscoin_keys = array();
	if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") == $table_name) {
		# Table created
		# Таблица создана
		if(isset($_POST['action']) && trim($_POST['action']) == 'add') {
			# Reading parameters
			# Чтение параметров
			$key		=	intval($_POST["key"]);
			$pair  		= 	$_POST["pair"];
			$timeout   	= 	intval($_POST["timeout"]);
			$limit	 	= 	intval($_POST["limit"]);
			$content	= 	$_POST["content"];
			$country	= 	$_POST["country"];
			$cost_local = 	$_POST["cost_local"];
			$provider   =   $_POST["provider"];

			# Writing line to database
			# Запись строки в базу данных
			$fields = "1, ".addslashes($key).", '".addslashes($pair)."','".addslashes($country)."', '".addslashes($provider)."', '".addslashes($content)."', '".addslashes($cost_local)."',
			".addslashes(time()).", ".intval($timeout).", ".intval($limit).", ".intval($limit);

			$wpdb->query("INSERT INTO ".$table_name." (k_status, k_key, k_pair, k_country, k_provider, k_text, k_cost_local, k_created, k_timeout, k_limit_start, k_limit_current)
				VALUES (".$fields.");
			");
			unset($_REQUEST['action']);
		}
		if( isset($_REQUEST['del']) ) {
			# Delete data
			# Удаление записи
			$wpdb->query("DELETE FROM ".$table_name." WHERE k_pair='".addslashes($_REQUEST['k_pair'])."';");
			$LastAction = '<h3>'. sprintf(__('Password %s was deleted!', 'smscoin_rpayment'), $_REQUEST['k_pair']).' .</h3> ';
			unset($_REQUEST['del']);
		}
		if( isset($_REQUEST['edit']) ) {
			# Change data
			# Изменение записи
			$wpdb->query("UPDATE ".$table_name." SET
				k_timeout='".intval($_REQUEST['k_timeout'])."' ,
				k_limit_start='".intval($_REQUEST['k_limit_start'])."' ,
				k_limit_current='".intval($_REQUEST['k_limit_current'])."'
			WHERE k_pair='".addslashes($_REQUEST['k_pair'])."';");
			$LastAction = '<h3>'. sprintf(__('Password %s parameters was changed!', 'smscoin_rpayment'), $_REQUEST['k_pair']) . '</h3> ';
			unset($_REQUEST['edit']);
		}
		if( !isset($_REQUEST['rpp']) ) {
			$rpp = 5;
		} else {
			$rpp = intval($_REQUEST['rpp']);
		}
		$str .= '
			<style type="text/css">
			p, li, th, td {
			 font-size: 9pt;
			}
			.list_table {
			 width: 100%;
			}
			.list_table tr {
			 background: #ffffff;
			 color: inherit;
			}
			.list_table tr.row_0 {
			 background: #f9f9f9;
			 color: inherit;
			}
			.list_table tr.row_1 {
			 background: #efefff;
			 color: inherit;
			}
			.list_table th {
			 background: #f1f1f1;
			 color: #033;
			 padding: 2px;
			 text-align: center;
			 border-bottom: 1px #777 solid;
			}
			.list_table td {
			 padding: 1px;
			 text-align: center;
			}
			.list_table input {
			 width: auto;
			}
			.list_table th input {
			 width: 100%;
			}
			</style>

			<div><h3>'.__('Manually add a password:','smscoin_rpayment').'</h3></div>

			<table class="list_table">
			<tr>
				<th>'.__('Key','smscoin_rpayment').'</th>
				<th>'.__('Password','smscoin_rpayment').'</th>
				<th>'.__('Country','smscoin_rpayment').'</th>
				<th>'.__('Provider','smscoin_rpayment').'</th>
				<th>'.__('Text','smscoin_rpayment').'</th>
				<th>'.__('Cost','smscoin_rpayment').'</th>
				<th>'.__('Time','smscoin_rpayment').'</th>
				<th>'.__('Limit','smscoin_rpayment').'</th>
				<th></th>
			</tr>
			<tr>
				<form action="admin.php?page=smscoin_rpayment/smscoin_rpayment.php" method="post">
					<th><input name="key" type="text" size="5" value="" /></th>
					<th><input name="pair" type="text" size="4" value="" /></th>
					<th><input name="country" type="text" size="5" value="" /></th>
					<th><input name="provider" type="text" size="5" value="" /></th>
					<th><input name="text" type="text" size="10" value="" /></th>
					<th><input name="cost_local" type="text" size="5" value="" /></th>
					<th><input name="timeout" type="text" size="5" value="" /></th>
					<th><input name="limit" type="text" size="5" value="" /></th>

					<th><input name="action" type="hidden" value="add" />
					<input class="btn" type="submit" name="add" value="'.__('Add Password','smscoin_rpayment').'" /></th>
				</form>
			</tr>
			</table>
			 <hr />

			<div><h3>'.__('List recived sms','smscoin_rpayment').'</h3></div>
			<form action="admin.php?page=smscoin_rpayment/smscoin_rpayment.php" method="post">
			<div>
				'.__('Items per page','smscoin_rpayment').'<input name="rpp" type="text" size="5" value="'.$rpp.'" />
				<input class="btn" type="submit" name="find" value="'.__('Show','smscoin_rpayment').'" />
			</div>';

		$where = array();
		$order = "k_created";

		# Creating request
		# Создание запроса
		if (isset($_REQUEST['key']) && $_REQUEST['key']!='') {
			$where[] = "k_key='".intval($_REQUEST['key'])."'";
		}
		if (isset($_REQUEST['pair']) && $_REQUEST['pair']!='') {
			$where[] = "k_pair='".addslashes($_REQUEST['pair'])."'";
		}
		if (isset($_REQUEST['timeout']) && $_REQUEST['timeout']!='') {
			$where[] = "k_timeout='".intval($_REQUEST['timeout'])."'";
		}
		if (isset($_REQUEST['limit']) && $_REQUEST['limit']!='') {
			$where[] = "k_limit_start='".intval($_REQUEST['limit'])."'";
		}
		if (isset($_REQUEST['ip']) && $_REQUEST['ip']!='') {
			$where[] = "k_first_ip='".addslashes($_REQUEST['ip'])."'";
		}
		if (isset($_REQUEST['provider']) && $_REQUEST['provider']!='') {
			$where[] = "k_provider LIKE '%".addslashes($_REQUEST['provider'])."%'";
		}
		if (isset($_REQUEST['country']) && $_REQUEST['country']!='') {
			$where[] = "k_country='".addslashes($_REQUEST['country'])."'";
		}
		if (isset($_REQUEST['text']) && $_REQUEST['text']!='') {
			$where[] = "k_text LIKE '%".addslashes($_REQUEST['text'])."%'";
		}


		if(isset($_REQUEST['sms_page']) && $_REQUEST['sms_page']!='') {
			$page = intval($_REQUEST['sms_page']);
		} else {
			$page = 1;
		}

		$offset = ($page-1)*$rpp;

		$result = $wpdb->get_row("SELECT count(*) AS num_row FROM ".$table_name."
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."", ARRAY_A );
		$sms_num_row = intval($result['num_row']);

		$str .= smscoin_vip_paging($sms_num_row, $rpp);

		$str .= '<table class="list_table">
			<tr>
				<th>'.__('Key','smscoin_rpayment').'</th>
				<th>'.__('Password','smscoin_rpayment').'</th>
				<th>'.__('Country','smscoin_rpayment').'</th>
				<th>'.__('Provider','smscoin_rpayment').'</th>
				<th>'.__('Text','smscoin_rpayment').'</th>
				<th>'.__('Cost','smscoin_rpayment').'</th>
				<th>'.__('Added','smscoin_rpayment').'</th>
				<th>'.__('Time','smscoin_rpayment').'</th>
				<th>'.__('Limit','smscoin_rpayment').'</th>
				<th>'.__('Show','smscoin_rpayment').'</th>
				<th>'.__('First enter','smscoin_rpayment').'</th>
				<th>IP</th>
				<th>'.__('Options','smscoin_rpayment').'</th>
			</tr>

			<tr>
				<th><input name="key" type="text" size="5" value="'.$_REQUEST['key'].'" /></th>
				<th><input name="pair" type="text" size="7" value="'.$_REQUEST['pair'].'" /></th>
				<th><input name="country" type="text" size="5" value="'.$_REQUEST['country'].'" /></th>
				<th><input name="provider" type="text" size="5" value="'.$_REQUEST['provider'].'" /></th>
				<th><input name="text" type="text" size="10" value="'.$_REQUEST['text'].'" /></th>
				<th><input name="cost_local" type="text" size="5" value="'.$_REQUEST['cost_local'].'" /></th>
				<th>&nbsp;</th>
				<th><input name="timeout" type="text" size="3" value="'.$_REQUEST['timeout'].'" /></th>
				<th><input name="limit" type="text" size="3" value="'.$_REQUEST['limit'].'" /></th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
				<th><input name="ip" type="text" size="10" value="'.$_REQUEST['ip'].'" /></th>
				<th><input class="btn" type="submit" name="find" value="'.__('Find','smscoin_rpayment').'" /></th>
			</tr>';

		$smscoin_keys = $wpdb->get_results("SELECT * FROM ".$table_name."
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."
			ORDER BY ".addslashes($order)." DESC
			LIMIT ".intval($offset).",".intval($rpp));
		$i = 0;
		foreach($smscoin_keys as $skey) {
			$str .= '
			<form action="admin.php?page=smscoin_rpayment/smscoin_rpayment.php" method="post" >
			<tr class="row_'.$i.'">
			 <td><input size="5" name="k_key" readonly="readonly" value="'.$skey->k_key.'" /></td>
			 <td><input size="5" name="k_pair" readonly="readonly" value="'.$skey->k_pair.'" /></td>
			 <td>'.$skey->k_country.'</td>
			 <td>'.$skey->k_provider.'</td>
			 <td>'.$skey->k_text.'</td>
			 <td>'.$skey->k_cost_local.'</td>
			 <td>'.date("d.m.Y H:i", $skey->k_created).'</td>
			 <td><input size="5" name="k_timeout" value="'.$skey->k_timeout.'" /></td>
			 <td><input size="5" name="k_limit_start" value="'.$skey->k_limit_start.'" /></td>
			 <td><input size="5" name="k_limit_current" value="'.$skey->k_limit_current.'" /></td>
			 <td>'.($skey->k_first_access>0 ? date("d.m.Y H:i", $skey->k_first_access) : '').'</td>
			 <td>'.$skey->k_first_ip.'</td>
			 <td>

			<input class="btn" type="submit" name="del" value="Del" />
			<input class="btn" type="submit" name="edit" value="Edit" />
			 </td>
			</tr>
			</form>';
			$i = abs($i-1);
		}
		$str .= '</table></form>';
		$str .= smscoin_vip_paging($sms_num_row,$rpp);
	} else {
		$LastAction = '<h3>'.__('First you need to configure the module!','smscoin_rpayment').' </h3> ';
		$LastAction .= '<a href="admin.php?page=Settings1">'.__('Settings','smscoin_rpayment').'</a>';
		$page_mes .= '<a href="admin.php?page=Settings1">'.__('Settings','smscoin_rpayment').'</a>';
	}
	if(!empty($LastAction)) {
		$str .= '<!-- Last Action --><div id="message" class="updated fade"><p>'.$LastAction.'</p></div>';
	}

	echo $page_mes.$str;
}

###
#  Function which creates instructions for sending SMS
#  Функция создает инструкции по отправке смс
###
function smscoin_rpayment_instruction($key_id) {
	$wpurl = get_bloginfo('wpurl');
	$currentLocale = get_locale();
	if(!empty($currentLocale)) {
		$moFile = dirname(__FILE__) . "/lang/smscoin_rpayment-" . $currentLocale . ".mo";
		if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('smscoin_rpayment', $moFile);
	}

	 $mess = '
		<div class="div_ui" style="display: none">
			<h3>'.__('Select Country','smscoin_rpayment').':</h3>
			<select class="select_country">
				<option value="-">'.__('Select Country','smscoin_rpayment').'</option>
			</select>
			<div class="div_provider" style="display: none">
				<h3>'.__('Select Provider','smscoin_rpayment').':</h3>
				<select class="select_provider">
					<option value="-">'.__('Select Provider','smscoin_rpayment').'</option>
				</select>
			</div>
			<div class="div_instructions" style="display: none">
				<p>'.__('In order to receive a password, please send a message saying' ,'smscoin_rpayment' ).' <span class="message_text"></span> '.__('to the phone number','smscoin_rpayment').' <span class="shortcode"></span>.</p>
				<p>'.__('The message will cost you' , 'smscoin_rpayment').' — <span class="message_cost"></span>.</p>
				<p class="notes" style="display: none"></p>
				<p>'.__('You will receive your password in reply.' , 'smscoin_rpayment').'</p>
				<p>'.__('Caution!','smscoin_rpayment').'</p>
				<p>'.__('Pay attention to the message text and especially spaces.All the letters are latin.You\'ll be charged the full price, even in case of an error.','smscoin_rpayment').'</p>
			</div>
		</div>
		<div class="div_fail" style="display: none">
			<h1>'.__('Error connecting to server! Update you\'r tariffs','smscoin_rpayment').'</h1>
		</div>';
	return $mess;
}

###
#  Updating rate scale function
#  Функция обновления тарифной сетки
###
function smscoin_rpayment_tariffs_cron() {
	global $table_prefix, $wpdb;
	@ini_set('user_agent', 'smscoin_key_cron');

	$wpurl = get_bloginfo('wpurl');
	$key_id = intval(get_option('smscoin_rpayment_key_id'));
	$language = get_option('smscoin_rpayment_language');
	$response = file_get_contents("http://service.smscoin.com/language/$language/json/key/".$key_id."/");
	if(preg_match('|(JSONResponse = \[.*\])|is', $response, $feed) > 0) {
		if ($response !== false) {
				$filename = dirname(__FILE__).'/data/local.js';
				if (($hnd = @fopen($filename, 'w')) !== false) {
					if (@fwrite($hnd, $response) !== false) {
						$LastAction .= ' - Success, file updated @ '.date("r");
						$last_update = date("r");
						update_option('smscoin_rpayment_last_update_net', trim($last_update) );
					}
					fclose($hnd);
				}
		}
		if (function_exists('gzopen')) {
			$filename = dirname(__FILE__).'/data/local.js';
			$filename_gz = dirname(__FILE__).'/data/local.json';

			$text_fp = fopen($filename,'r');
			$gz_fp = gzopen($filename_gz,'w9');
			while(!feof($text_fp)) {
				gzwrite($gz_fp,fread($text_fp,655360));
			}
			fclose($text_fp);
		}
	}
	# Deleting users with time limit from VIP group
	# Удаление пользователей с группы VIP, время которых истекло
	$results = $wpdb->get_results("SELECT * FROM ".$table_prefix."vvip WHERE pair_time < ".(time()-(86400*get_option('smscoin_rpayment_s_vip_time'))) );
	$num_pages = count($results);
	for($i = 0; $i < $num_pages; $i++) {
		$wpdb->query("DELETE FROM ".$table_prefix."vvip WHERE uid = ".$results[$i]->uid);
		$res = $wpdb->get_row("SELECT * FROM ".$table_prefix."users WHERE ID = ".$results[$i]->uid);
		# Sending email
		# Отправка письма
		wp_mail($res->user_email, get_option('smscoin_rpayment_s_subject'), get_option('smscoin_rpayment_s_message'));
	}

}

###
#  Displaying instructions for sending sms in website admin function
#  Функция отображения инструкции по отправке смс в админке сайта
###
function smscoin_rpayment_tariffs() {
	global $wpdb, $table_prefix;
	echo smscoin_rpayment_add_script();
	$wpurl = get_bloginfo('wpurl');
	$key_id = intval(get_option('smscoin_rpayment_key_id'));
	$language = get_option('smscoin_rpayment_language');
	$last_update = get_option('smscoin_rpayment_last_update_net');
	if($key_id > 200000) {
		if ( isset($_POST['submit']) ) {
			if( isset($_POST['action']) && $_POST['action'] === 'up') {
				@ini_set('user_agent', 'smscoin_vip_cron');
				$response = file_get_contents("http://service.smscoin.com/language/$language/json/key/".$key_id."/");
				$LastAction .= 'From : <a onclick="window.open(this.href); return false;" href="http://service.smscoin.com/language/english/json/key/'.$key_id.'/">http://service.smscoin.com/language/english/json/key/'.$key_id.'/</a> ';
				if ($response !== false) {
						$filename = dirname(__FILE__).'/data/local.js';
						if (($hnd = @fopen($filename, 'w')) !== false) {
							if (@fwrite($hnd, $response) !== false) {
								$LastAction .= ' - Success, file updated @ '.date("r");
								$last_update = date("r");
								update_option('smscoin_rpayment_last_update_net', trim($last_update) );
							} else {
								$LastAction = 'File "'.$filename.'" not writeable!';
							}
							fclose($hnd);
						} else {
							$LastAction = 'Could not open file';
						}
				} else {
					$LastAction = 'Unable to connect to remote server';
				}
				$page = '';
				if (function_exists('gzopen')) {
					$filename = dirname(__FILE__).'/data/local.js';
					$filename_gz = dirname(__FILE__).'/data/local.json';

					$text_fp = fopen($filename,'r');
					$gz_fp = gzopen($filename_gz,'w9');
					while(!feof($text_fp)) {
						gzwrite($gz_fp,fread($text_fp,655360));
					}
					fclose($text_fp);
				}
			}
		}

		$page .=  '<h2>'.__('Your local tariff scale','smscoin_rpayment').'</h2>'.smscoin_rpayment_instruction($key_id);
		$page .= '<h2>'.__('Update your local tariff scale','smscoin_rpayment').'</h2>';
		$page .= '
			'.__('Last update: ','smscoin_rpayment').' '.$last_update.'
			<form action="admin.php?page=Tarifs1" method="post" id="smscoin_rpayment-conf" style="text-align: left ; margin: left; width: 50em; ">
				<input type="hidden" name="action" value="up" />
				<p class="submit"><input type="submit" name="submit" value="'.__('Update now: ','smscoin_rpayment').'" /></p>
			</form>';

	} else {
		$LastAction = '<h3>'.__('First you need to configure the module!','smscoin_rpayment').'</h3> ';
		$LastAction .= '<a href="admin.php?page=Settings1">'.__('Settings','smscoin_rpayment').'</a>';
		$page_mes .= '<a href="admin.php?page=Settings1">'.__('Settings','smscoin_rpayment').'</a>';
	}

	if(!empty($LastAction)) {
		echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$LastAction.'</p></div>';
	}
	# Deleting users with time limit from VIP group
	# Удаление пользователей с группы VIP, время которых истекло
	$results = $wpdb->get_results("SELECT * FROM ".$table_prefix."vvip WHERE pair_time < ".(time()-(86400*get_option('smscoin_rpayment_s_vip_time'))) );
	$num_pages = count($results);
	for($i = 0; $i < $num_pages; $i++) {
		$wpdb->query("DELETE FROM ".$table_prefix."vvip WHERE uid = ".$results[$i]->uid);
		$res = $wpdb->get_row("SELECT * FROM ".$table_prefix."users WHERE ID = ".$results[$i]->uid);
		# Sending email
		# Отправка письма
		wp_mail($res->user_email, get_option('smscoin_rpayment_s_subject'), get_option('smscoin_rpayment_s_message'));
	}
	echo $page;
}


###
#  Basic settings function
#  Функция основных настроек
###
function smscoin_rpayment_settings_page() {
	$languages = array("russian", "belarusian", "english", "estonian", "french", "german", "hebrew", "latvian", "lithuanian", "romanian", "spanish", "ukrainian");
	$str = '<h2>'.__('Module Settings','smscoin_rpayment').' SmsCoin VIP</h2>';
	if ( isset($_POST['submit']) ) {
		check_admin_referer();
		update_option('smscoin_rpayment_key_id', intval(trim($_POST['key_id'])));
		update_option('smscoin_rpayment_language', trim($_POST['language']));
		update_option('smscoin_rpayment_s_vip_time', trim($_POST['s_vip_time']));
		update_option('smscoin_rpayment_s_subject', trim($_POST['s_subject']));
		update_option('smscoin_rpayment_s_message', trim($_POST['s_message']));
		update_option('smscoin_rpayment_s_secret', trim($_POST['s_secret']));
		update_option('smscoin_rpayment_is_gzip', trim($_POST['is_gzip']));
		if( trim($_POST['s_tag']) != '') {
			update_option('smscoin_rpayment_s_tag', trim($_POST['s_tag']));
		} else {
			update_option('smscoin_rpayment_s_tag', 'rpayment');
		}
		if( trim($_POST['s_tag']) != '') {
			update_option('smscoin_rpayment_s_tag_vip', trim($_POST['s_tag_vip']));
		} else {
			update_option('smscoin_rpayment_s_tag_vip', 'SMSCOINPAY');
		}
		if (trim($_POST['key_id']) === "") {
			$mess='<h3>'.__('Wrong sms:key ID','smscoin_rpayment').'</h3>';
		} else {
			$mess='<h3>'.__('Settings saved','smscoin_rpayment').'</h3>';
		}
		$LastAction = $mess;
	}

	if(!empty($LastAction)) {
		$str .= '<!-- Last Action --><div id="message" class="updated fade"><p>'.$LastAction.'</p></div>';
	}
	echo $str;
?>

	<div class="wrap">
		<fieldset class="options">
			<legend><h2>SmsCoin - VIP, <?php _e('Settings','smscoin_rpayment') ?></h2></legend>
			<p><?php _e('For using this module you have to be' ,'smscoin_rpayment') ?> <a href="http://smscoin.com/account/register/" onclick="this.target = '_blank';"><b><?php _e('registered' ,'smscoin_rpayment') ?></b></a><?php _e(' at smscoin.net .' ,'smscoin_rpayment') ?></p>

			<p><hr /></p>
			<form action="admin.php?page=Settings1" method="post" id="smscoin_rpayment-conf" style="text-align: left ; margin: left; width: 50em; ">
				<p><?php _e('Enter ID of you\'r sms:key:' , 'smscoin_rpayment')?> <a href="http://smscoin.com/keys/add/" onclick="this.target = '_blank';"><?php _e('get sms:key','smscoin_rpayment') ?></a></p>
				<p><input id="key_id" name="key_id" type="text" size="12" maxlength="6" style="font-family: 'Courier New', monospace; font-size: 1.5em;" value="<?php echo get_option('smscoin_rpayment_key_id'); ?>" />
				<?php
					$select_txt = '<p>'.__('Select default script language','smscoin_rpayment').'</p>
					<select id="language" name="language" type="text"  style="font-family: \'Courier New\', monospace; font-size: 1.5em;">';
					$langs = $languages;
					foreach ($langs as $lang) {
						$select_txt .= '<option value="'.$lang.'"'.(($lang === get_option('smscoin_rpayment_language') )?' selected="selected"':'').'>'.$lang.'</option>';
					}
					echo $select_txt.'</select>';
				 ?>

				<p><?php echo __('Enter Secret code from settings of sms:key:','smscoin_rpayment'); ?></p>
				<p><input id="s_secret" name="s_secret" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo (get_option('smscoin_rpayment_s_secret') == "" ? ' value="" ' : ' value="'. get_option('smscoin_rpayment_s_secret') .'" ')?>  />
				<p><?php echo __('Enter the Tag name for hide the content:','smscoin_rpayment'); ?></p>
				<p><input id="s_tag" name="s_tag" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo (get_option('smscoin_rpayment_s_tag') == "" ? ' value="rpayment" ' : ' value="'. get_option('smscoin_rpayment_s_tag') .'" ')?>  />

				<p><?php echo __('Enter the Tag name for instructions:','smscoin_rpayment'); ?></p>
				<p><input id="s_tag_vip" name="s_tag_vip" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo (get_option('smscoin_rpayment_s_tag_vip') == "" ? ' value="SMSCOINPAY" ' : ' value="'. get_option('smscoin_rpayment_s_tag_vip') .'" ')?>  />

				<p><?php echo __('VIP Time in days:','smscoin_rpayment'); ?></p>
				<p><input id="s_vip_time" name="s_vip_time" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo (get_option('smscoin_rpayment_s_vip_time') == "" ? ' value="'.__('7','smscoin_rpayment').'" ' : ' value="'. get_option('smscoin_rpayment_s_vip_time') .'" ')?>  />

				<p><?php echo __('Enter the email subject:','smscoin_rpayment'); ?></p>
				<p><input id="s_subject" name="s_subject" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo (get_option('smscoin_rpayment_s_subject') == "" ? ' value="'.__('VIP time limited','smscoin_rpayment').'" ' : ' value="'. get_option('smscoin_rpayment_s_subject') .'" ')?>  />

				<p><?php echo __('Enter the email message:','smscoin_rpayment'); ?></p>
				<textarea id="s_message" name="s_message" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" ><?php echo (get_option('smscoin_rpayment_s_message') == "" ? __('You removed from VIP group','smscoin_rpayment') : get_option('smscoin_rpayment_s_message') )?></textarea>

				<p><?php echo __('Use gzip ?','smscoin_rpayment'); ?></p>
				<p>
					<select id="is_gzip" name="is_gzip" type="text"  style="font-family: \'Courier New\', monospace; font-size: 1.5em;">
						<?php
						echo '<option value="1" '.((get_option('smscoin_rpayment_is_gzip') == 1  )?' selected="selected"':'').'>'.__('Yes','smscoin_rpayment').'</option>
							<option value="0" '.((get_option('smscoin_rpayment_is_gzip') == 0 )?' selected="selected"':'').'>'.__('No','smscoin_rpayment').'</option>';
						?>
					</select>
				</p>


				<p class="submit"><input type="submit" name="submit" value="<?php echo __('Save Settings','smscoin_rpayment'); ?> &raquo;" /></p>
			</form>
		</fieldset>
	</div>
	<?php
}

###
#  Filtrating output data function (displaying hidden content or relevant information)
#  Функция фильтрации выходных данных (вывод скрытого контента или реливантной информации)
#
#  $content string
###
function smscoin_rpayment_post_filter($content) {
	global $user_ID, $wpdb, $table_prefix;
	$tag_name_start = get_option('smscoin_rpayment_s_tag');
	$tag_name = get_option('smscoin_rpayment_s_tag_vip');
	$tag_name_end = $tag_name_start;
	$key_id = get_option('smscoin_rpayment_key_id');
	$smscoin_rpayment_last_update_net = get_option('smscoin_rpayment_last_update_net');
	$response = '';
	$flag = 0;
	# Searching hidden content on the page
	# Поиск скрытого контента на странице
	if (preg_match('/\\['.$tag_name_start.'\\](.*?)\\[\\/'.$tag_name_end.'\\]/is', $content, $matches)) {
		################################################################################
		### SMS:Key v1.0.6 ###
		if (intval($key_id) > 200000) {
			if ($user_ID) {
				$data = $wpdb->get_row("SELECT * FROM ".$table_prefix."vvip WHERE uid = ".$user_ID);
				if (!$data) {
					$rpl_hidd = '<div style="text-align: left ;">'.__('Only VIP users can read the text','smscoin_rpayment').'</div>';
				} else {
					$rpl_hidd = $matches[1];
				}
			} else {
				$rpl_hidd = '<div style="text-align: left ;">'.__('Only VIP users can read the text','smscoin_rpayment').'</div>';
			}
		} else {
			$rpl_hidd = '<div style="text-align: left ;">'.__('Hidden text','smscoin_rpayment').'</div>';
		}
		# Displaying hidden content or relevant information
		# Вывод скрытого контента или реливантной информации
		$content = preg_replace('/\\['.$tag_name_start.'\\].*?\\[\\/'.$tag_name_end.'\\]/is', $rpl_hidd, $content);
		### SMS:Key end ###
		################################################################################
	}
	# Displaying hidden content or relevant information
	# Вывод инструкции по отправке смс, или реливантной информации
	if (strpos( $content, '['.$tag_name.']')) {
		# Tag which displays instructions found
		# Тег вывода инструкции найден
		if ($user_ID) {
			$data = $wpdb->get_row("SELECT * FROM ".$table_prefix."vvip WHERE uid = ".$user_ID);
			if ($data) {
				$content = preg_replace('/\\['.$tag_name.'\\]/is', __('You already in VIP group!','smscoin_rpayment'), $content);
			}
			else {
				$result = 0;
				if (isset($_GET['s_pair']) && $_GET['s_pair'] !='' && strlen($_GET['s_pair'])<=10) {
					# Validating password
					# Проверка пароля
					$result = do_vip_local_check ($key_id, $_GET['s_pair']);
				}
				if ($result != 1) {
					# Displaying instruction for sending SMS
					# Вывод инструкции по отправке смс
					$response .= __('<h3>If you have already received the password, enter it here:</h3>','smscoin_rpayment').'<br />';
					$array_qs = array();
					parse_str($_SERVER["QUERY_STRING"], $array_qs);
					$response .= '
					<form action="http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].'" method="get">
						<div>
							<input name="s_pair" type="text" value="" />';
							foreach($array_qs as $key=>$val) {
								$response .= '<input name="'.$key.'" type="hidden" value="'.$val.'" />';
							}
							$response .= '
							<input type="submit" value="'.__('Continue','smscoin_rpayment').'" />
						</div>
					</form>';

					$response .= __('<h3>To receive your password please send an sms</h3>','smscoin_rpayment'). smscoin_rpayment_add_script().''.smscoin_rpayment_instruction($key_id);
					$content = preg_replace('/\\['.$tag_name.'\\]/is', $response, $content);
				} else {
					# Moving user to VIP group
					# Перевод пользователя в VIP группу
					$wpdb->query("INSERT IGNORE INTO ".$table_prefix."vvip (uid, pair_time) VALUES (".$user_ID.", ".time().")");
					$content = preg_replace('/\\['.$tag_name.'\\]/is', __('Congratulation now you in VIP group!','smscoin_rpayment'), $content);
				}
			}
		} else $content = preg_replace('/\\['.$tag_name.'\\]/is', __('Login require.','smscoin_rpayment'), $content);
	}

	return $content;
}

###
#  Validating password function
#  Функция проверки пароля
#
#  $pair string
#  $key int
###
function do_vip_local_check ($key, $pair) {
	global $wpdb, $table_prefix;
	$table_name = $table_prefix . 'vkeys';

	$do_die = 0;
	if (isset($pair) && $pair !='' && strlen($pair)<=10) {

		# Validating password
		# Проверка пароля
		$result = $wpdb->get_row("SELECT * FROM $table_name
			WHERE k_status='1'
				AND k_pair='".addslashes($pair)."'
				AND k_key='".intval($key)."'",ARRAY_A);
			$data = $result;
			if ($data && $data['k_first_access'] == '0') {
				# First activation
				# Первая активация
				$wpdb->query("UPDATE $table_name
					SET k_first_access='".time()."', k_first_ip='".addslashes($_SERVER["REMOTE_ADDR"])."',
						k_first_from='".addslashes($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"])."'".($data['k_limit_current'] > 0 ? ", k_limit_current=k_limit_current-1" : "")."
					WHERE k_pair='".addslashes($pair)."' AND k_key='".intval($key)."'");
				$do_die = 1;
			} elseif ($data && ($data['k_timeout'] == 0 || ($data['k_first_access']+$data['k_timeout']*60)>time())) {
				if ($data['k_limit_start'] > 0) {
					if ($data['k_limit_current'] > 0) {
						# Additional activation
						# Другие активации
						$wpdb->query("UPDATE $table_name SET k_limit_current=k_limit_current-1
							WHERE k_pair='".addslashes($pair)."'
								AND k_key='".intval($key)."' AND k_limit_current>0");
						$do_die = 1;
					}
				} else {
					$do_die = 1;
				}
			}

	}
	return $do_die;
}

?>
