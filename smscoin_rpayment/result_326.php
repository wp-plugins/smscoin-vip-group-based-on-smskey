<?php
/*
=====================================================
 WordPress plugin - by SmsCoin
-----------------------------------------------------
 http://smscoin.com
-----------------------------------------------------
 Copyright (c) 2008 SmsCoin
=====================================================
 Ôàéë: result.php
-----------------------------------------------------
 Purpose: payment module through SMS message
=====================================================
*/

	require_once('../../../wp-load.php');

	global $wpdb, $table_prefix;

	# the function returns an MD5 of parameters passed
	# ôóíêöèÿ âîçâðàùàåò MD5 ïåðåäàííûõ åé ïàðàìåòðîâ
	function smscoin_rkey_ref_sign() {
		$params = func_get_args();
		$prehash = implode("::", $params);
		return md5($prehash);
	}

	# filtering junk off acquired parameters
	# ïàðñèì ïîëó÷åííûå ïàðàìåòðû íà ïðåäìåò ìóñîðà
	foreach($_GET as $k => $v) {
		$_GET[$k] = substr(trim(strip_tags($v)), 0, 250);
	}

	# service secret code
	# ñåêðåòíûé êîä ñåðâèñà
	$secret_code = get_option('smscoin_rpayment_s_secret');
	# collecting required data
	# ñîáèðàåì íåîáõîäèìûå äàííûå

	$key		=	intval($_GET["key"]);
	$pair		=	$_GET["pair"];
	$timeout	=	intval($_GET["timeout"]);
	$limit		=	intval($_GET["limit"]);
	$content	=	$_GET["content"];
	$country	=	$_GET["country"];
	$cost_local	=	$_GET["cost_local"];
	$provider	=	$_GET["provider"];
	$sign		=	$_GET["sign_v4"];

	# making the reference signature
	# ñîçäàåì ýòàëîííóþ ïîäïèñü


	$reference = smscoin_rkey_ref_sign($secret_code, $key, $pair, $timeout, $limit, $content, $country, $cost_local, $provider);


	# validating the signature
	# ïðîâåðÿåì, âåðíà ëè ïîäïèñü
	if( $sign == $reference) {
		# success, proceeding
		# îáðàáàòûâàåì ïîëó÷åííûå äàííûå

		# insert new transaction to DB
		# Äîáàâëåíèå çàïèñè â áàçó äàííûõ
		$fields = "1, ".intval($key).", '".addslashes($pair)."','".addslashes($country)."',
		'".addslashes($provider)."', '".addslashes($content)."', '".floatval($cost_local)."',
		".time().", ".intval($timeout).", ".intval($limit).", ".intval($limit);

		$wpdb->query("INSERT INTO ".$table_prefix."vkeys (k_status, k_key, k_pair, k_country,
			k_provider, k_text, k_cost_local, k_created, k_timeout, k_limit_start, k_limit_current)
			VALUES (".$fields.");
		");

		echo 'OK';
	} else {
		# failure, reporting error
		# íåïðàâèëüíî ñîñòàâëåí çàïðîñ
		echo 'checksum failed';
	}
?>

