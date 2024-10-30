<?php
/*
	Plugin Name: KC S2M+MC Free
	Plugin URI: http://krumch.com/kc-s2m-mc/
	Description: Bidirectional transparent integration/synchronization/bridge of S2Member with MailChimp - Free edition
	Version: 2016.12.12
	Author: Krum Cheshmedjiev
	Author URI: http://krumch.com
	Text Domain: kc-s2m-mc-free
	Domain Path: /languages
	Tested up to: 5.0
	Requires at least: 3.0
	Requires: WordPress® 3.0+, PHP 5.2+
	Tags: bidirectional, transparent, integration, integrate, synchronization, synchronize, s2m, s2member, mailchimp, mc, member, members, members info, mail, email, mail info, list, lists, mailing list, tool, bridge
*/

use \KC\MailChimp\MailChimp;
add_filter('http_request_timeout', 'kc_s2m_mc_change_http_request_timeout', 9999999999);

function kc_s2m_mc_activate() {
	if(is_plugin_active('s2member/s2member.php') and $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"] != '') {
		$lists = kc_s2m_mc_getLists();
		kc_s2m_mc_synchCheck($lists);
		kc_s2m_mc_setWebhooks($lists);
	}
}

register_activation_hook( __FILE__, 'kc_s2m_mc_activate' );
add_action('ws_plugin__s2member_after_activation', 'kc_s2m_mc_activate');
add_action('ws_plugin__s2member_before_update_all_options', 'kc_s2m_mc_listsEdit');
add_filter('ws_plugin__s2member_update_all_options', 'kc_s2m_mc_listsEdit');
add_action('wp_ajax_kc-s2m-mc-wh', 'kc_s2m_mc_wh');
add_action('wp_ajax_nopriv_kc-s2m-mc-wh', 'kc_s2m_mc_wh');

function kc_s2m_mc_deactivate() {
	if(is_plugin_active('s2member/s2member.php') and '' != $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]) {
		kc_s2m_mc_delWebhooks(kc_s2m_mc_getLists());
		@delete_option('kc_s2m_mc_nosynch');
		@delete_option('kc_s2m_mc_lists');
	}
}

register_deactivation_hook( __FILE__, 'kc_s2m_mc_deactivate' );
add_action('ws_plugin__s2member_before_deactivation', 'kc_s2m_mc_deactivate');

function kc_s2m_mc_translation() {
	load_plugin_textdomain('kc-s2m-mc-free', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
}
add_action('init', 'kc_s2m_mc_translation');

function kc_s2m_mc_admin() {
global $KDB;
	echo '<form method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'"><div style="text-align:center"><h1>KC s2M+MC</h1></div>';
	wp_nonce_field('admin_form', 'admin_form');
	if(isset($_POST['activate'])) kc_s2m_mc_activate();
	if(is_plugin_active('s2member/s2member.php') and '' != $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]) {
		echo '<div style="text-align:center"><input type="submit" name="synch_check" value="'.__('Check for synchronization', 'kc-s2m-mc-free').'"></div>';
		if(isset($_POST['admin_form']) and wp_verify_nonce($_POST['admin_form'], 'admin_form')) {
			if(isset($_POST['webhooks'])) kc_s2m_mc_setWebhooks(kc_s2m_mc_getLists());
			$syn = 0;
			foreach($_POST as $name => $v) {
				if($name == 'synch_check') continue;
				$n = explode('_', $name);
$KDB .= "kc_s2m_mc_admin::n -> !<pre>".print_r($n, true)."</pre>!<br>\n";
				if($n[0] == 'synch') {
					if(!isset($n[2])) {
$KDB .= "kc_s2m_mc_admin::1<br>\n";
						if($n[1] == 'all') kc_s2m_mc_synch();
					} else if($n[1] == 'site' or $n[1] == 'list') {
$KDB .= "kc_s2m_mc_admin::2<br>\n";
$KDB .= "kc_s2m_mc_admin::n -> !<pre>".print_r($n, true)."</pre>!<br>\n";
						kc_s2m_mc_synch($n[2], $n[1]);
					}
#echo "<div class=\"updated fade\"><p>$KDB</p></div>";
KC_ErrLog(array('subject' => 'kc_s2m_mc_synch'));
					$syn++;
				}
			}
			if($syn or isset($_POST['synch_check'])) kc_s2m_mc_synchCheck(kc_s2m_mc_getLists());
			$nosynch = get_option('kc_s2m_mc_nosynch');
			foreach($_POST as $name => $v) {
				$n = explode('_', $name);
				if($n[0] == 'forget') {
					if($n[1] == 'all' and !isset($n[2])) {
						$nosynch = array();
					} else {
						unset($nosynch[$n[2]][$n[1]]);
						if($n[1] == 'all' or !$nosynch[$n[2]]) unset($nosynch[$n[2]]);
					}
					update_option('kc_s2m_mc_nosynch', $nosynch);
					break;
				}
			}
			if($nosynch) {
?>
				<div class="updated"><p><strong><?php _e('Site members and MailChimp&#174; lists are not synchronized.', 'kc-s2m-mc-free'); ?></strong> <a href="#" onclick="alert('<?php echo sprintf(__('In fact, there is no way all accounts to be synchronized, because there is lot of cases and exceptions. MailChimp&#174; can report events slower, a member can not receive the confirmation email from MailChimp&#174;, or not click the link yet, or member is admin in the site - all these and others can cause differences in list of members at MailChimp&#174; and site.\n\nBest way to see if the plugin works is to test manually. Create a new user in your MailChimp&#174; list and check if it will be created in WordPress. Well, needs confirmation etc... If this do not works, click `%s` button, wait 5-10 min (MailChimp&#174; can be slow) and test again.', 'kc-s2m-mc-free'), __('Re-set MailChimp&#174; site', 'kc-s2m-mc-free')); ?>'); return false;">[?]</a></p></div>
		<p><table border=0>
<?php
				$i = 0;
				foreach($nosynch as $list => $l) {
					$i++;
					if(isset($l['list'])) echo '<tr><td><span style="color: #164A61;">'.sprintf(__('List ID %s have %s members, what is not exists in site\'s DB.', 'kc-s2m-mc-free'), $list, $l['list']).'</span></td><td><input type="submit" name="synch_list_'.$list.'" value="'.__('Synch it', 'kc-s2m-mc-free').'"></td><td><input type="submit" name="forget_list_'.$list.'" value="'.__('Forget it', 'kc-s2m-mc-free').'"></td></tr>';
					if(isset($l['site'])) echo '<tr><td><span style="color: #164A61;">'.sprintf(__('%s site\'s members are not present in the list ID %s<br>(allow some time for people to confirm and then check again).', 'kc-s2m-mc-free'), $l['site'], $list).'</span></td><td><input type="submit" name="synch_site_'.$list.'" value="'.__('Synch it', 'kc-s2m-mc-free').'"></td><td><input type="submit" name="forget_site_'.$list.'" value="'.__('Forget it', 'kc-s2m-mc-free').'"></td></tr>';
					if(isset($l['list']) and isset($l['site'])) echo '<tr><td colspan=3><span style="color: #164A61;">'.sprintf(__('Synchronize both ways the list ID %s.', 'kc-s2m-mc-free'), $list).'</span>&nbsp;<input type="submit" name="synch_all_'.$list.'" value="'.__('Synch it', 'kc-s2m-mc-free').'">&nbsp;<input type="submit" name="forget_all_'.$list.'" value="'.__('Forget it', 'kc-s2m-mc-free').'"></td></tr>';
				}
				if($i > 1) echo '<tr><td align="center" colspan=3><input type="submit" name="synch_all" value="'.__('Synch ALL', 'kc-s2m-mc-free').'">&nbsp;<input type="submit" name="forget_all" value="'.__('Forget ALL', 'kc-s2m-mc-free').'"></td></tr>';
?>
		</table></p>
<?php
			}
		}
		echo '<div style="text-align:center"><input type="submit" name="webhooks" value="'.__('Re-set MailChimp&#174; site', 'kc-s2m-mc-free').'"></div>';
	} else {
		$alert = __('To obtain your MailChimp&#174; List ID(s), log into your MailChimp&#174; and go to the List. Now click the (Settings) link in the menu. A submenu opens, click the (List name and defaults) row. You will find List ID in red, at right column. Until MailChimp&#174 change the user interface again...', 'kc-s2m-mc-free');
		$s2mpgl = '<a href="http://www.s2member.com/2842.html" target="_blank" rel="external">'.__('s2Member&#174; plugin', 'kc-s2m-mc-free').'</a>';
		$mcacc = '<a href="http://www.s2member.com/mailchimp" target="_blank" rel="external">'.__('MailChimp&#174; account', 'kc-s2m-mc-free').'</a>';
		$mcak = '<a href="http://www.s2member.com/mailchimp-api-key" target="_blank" rel="external">'.__('MailChimp&#174; API Key', 'kc-s2m-mc-free').'</a>';
		$mclid = '<a href="#" onclick="alert('.$alert.'); return false;">'.__('MailChimp&#174; List IDs', 'kc-s2m-mc-free').'</a>';
		$s2mals = '<a href="'.site_url('/wp-admin/admin.php?page=ws-plugin--s2member-els-ops', 'http').'" target="_blank" rel="external">'.__('s2Member&#174; API / List Servers', 'kc-s2m-mc-free').'</a>';
		echo sprintf(__('You will need first to install %s. You will need a %s, a %s, your %s and must set some fields at %s page in the "MailChimp&#174; Integration" tab.', 'kc-s2m-mc-free'), $s2mpgl, $mcacc, $mcak, $mclid, $s2mals).'<br><br><div style="text-align:center"><input type="submit" name="activate" value="'.__('All done, run the synchronization', 'kc-s2m-mc-free').'"></div>';
	}
?>
				</form>
<?php
}

function kc_s2m_mc_adminmenu() { add_options_page("kc_s2m_mc", "KC S2M+MC", 'administrator', "kc_s2m_mc", "kc_s2m_mc_admin"); }
add_action('admin_menu', 'kc_s2m_mc_adminmenu');

# Functions

function kc_s2m_mc_change_http_request_timeout() { return 300; }

function kc_s2m_mc_listMembers($api, $list) {
	$lmembers = array();
	$lmem = kcs2mmc_listMembers($api, $list);
#$KDB .= "kc_s2m_mc_listMembers::lmem -> !<pre>".print_r($lmem, true)."</pre>!<br>\n";
	if($api->success()) {
		foreach($lmem as $m) {
			$lmembers[$m['email']] = 1;
		}
	}
	return $lmembers;
}

function kc_s2m_mc_siteMembers($roles=null) {
	global $wpdb;
	$admins = $wpdb->get_results("select user_id from $wpdb->usermeta where meta_key='".$wpdb->prefix."user_level' && meta_value='10'", OBJECT_K);
	$sql = "select user_email, ID from $wpdb->users";
	if($roles) {
		$sql = '';
		foreach(explode(',', $roles) as $role) {
			if($role != '') {
				if($sql) $sql .= ' || ';
				$sql .= "um.meta_value like '%s2member_level$role%'";
				if(!intval($role)) $sql .= " || um.meta_value like '%subscriber%'";
			}
		}
		$sql = "select u.user_email, u.ID from $wpdb->users u, $wpdb->usermeta um where u.ID=um.user_id && um.meta_key='".$wpdb->prefix."capabilities' && ($sql)";
	}
	$members = $wpdb->get_results($sql, ARRAY_A);
	$smembers = array();
	if($members) {
		foreach($members as $m) {
			if(!array_key_exists($m['ID'], $admins)) $smembers[$m['user_email']] = 1;
		}
	}
	return $smembers;
}

function kc_s2m_mc_synchCheck($lists) {
	require_once 'MailChimp.php';
	$api = new MailChimp($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]);
	$nosynch = array();
	foreach($lists as $list => $v) {
		$lmembers = kc_s2m_mc_listMembers($api, $list);
		$smembers = kc_s2m_mc_siteMembers($v);
		$s = count(array_diff_key($lmembers, $smembers));
		if($s) $nosynch[$list]['list'] = $s;
		$s = count(array_diff_key($smembers, $lmembers));
		if($s) $nosynch[$list]['site'] = $s;
	}
	update_option('kc_s2m_mc_nosynch', $nosynch);
}

function kc_s2m_mc_setWebhooks($lists) {
	if(isset($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]) and $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"] != '') {
		$diff_lists = get_option('kc_s2m_mc_lists');
		if(!$diff_lists) $diff_lists = array();
		$diff_lists = array_diff_key($diff_lists, $lists);
		if($diff_lists) kc_s2m_mc_delWebhooks($diff_lists);
		require_once 'MailChimp.php';
		$api = new MailChimp($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]);
		$whurl = admin_url('admin-ajax.php').'?action=kc-s2m-mc-wh&k='.md5($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]);
		foreach($lists as $list => $v) {
			$whbr = 0;
			$Webhooks = kcs2mmc_listWebhooks($api, $list);
#KC_ErrLog(array('Webhooks' => $Webhooks));
			if(is_array($Webhooks)) foreach($Webhooks as $wh) if($wh['url'] == $whurl) $whbr++;
			if(!$whbr) $rez = kcs2mmc_listWebhookAdd($api, $list, $whurl, array('subscribe' => true, 'unsubscribe' => true, 'profile' => true, 'cleaned' => true, 'upemail' => true, 'campaign' => false), array('user' => true, 'admin' => true, 'api' => false));
		}
		update_option('kc_s2m_mc_lists', $lists);
	}
}

function kc_s2m_mc_delWebhooks($lists) {
	require_once 'MailChimp.php';
	$api = new MailChimp($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]);
	foreach($lists as $list => $v) $rez = kcs2mmc_listWebhookDel($api, $list, admin_url('admin-ajax.php').'?action=kc-s2m-mc-wh&k='.md5($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]));
}

function kc_s2m_mc_getLists() {
	$lists = array();
	for($n = $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["levels"]; $n >= 0; $n--) {
		foreach(explode(',', format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["level".$n."_mailchimp_list_ids"])) as $l) {
			list($l,,) = explode('::', trim($l));
			if($l) $lists[$l] .= $n.',';
		}
	}
	return $lists;
}

function kc_s2m_mc_listsEdit($status) {
	kc_s2m_mc_setWebhooks(kc_s2m_mc_getLists());
	return $status;
}

function kc_s2m_mc_synch($lists=null, $from=null) {
	global $wpdb;
global $KDB;
$KDB .= "kc_s2m_mc_synch::lists, from -> !<pre>".print_r(array('lists' => $lists, 'from' => $from), true)."</pre>!<br>\n";
	$lists0 = kc_s2m_mc_getLists();
	if($lists) {
		$lists = array($lists => $lists0[$lists]);
	} else {
		$lists = $lists0;
	}
	require_once 'MailChimp.php';
	$api = new MailChimp($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]);
	foreach($lists as $list => $v) {
		$lmembers = kc_s2m_mc_listMembers($api, $list);
		$smembers = kc_s2m_mc_siteMembers($v);
		if(!$from or $from == 'list' or $from == 'all') {
			$mem = array();
			$mm = array_diff_key($lmembers, $smembers);
			foreach($mm as $mail => $time) $mem[] = $mail;
			$rez = kcs2mmc_listMemberInfo($api, $list, $mem);
$KDB .= "kc_s2m_mc_synch::rez -> !<pre>".print_r($rez, true)."</pre>!<br>\n";
			if($api->success()) {
				foreach($rez as $k => $m) {
					$user = array('user_login' => $m['email'], 'user_pass' => md5($m['email']), 'user_email' => $m['email']);
					if($m['merges']['FNAME']) $user['first_name'] = $m['merges']['FNAME'];
					if($m['merges']['LNAME']) $user['last_name'] = $m['merges']['LNAME'];
$KDB .= "kc_s2m_mc_synch::user -> !<pre>".print_r($user, true)."</pre>!<br>\n";
					wp_insert_user($user);
					$smembers[$m['merges']['EMAIL']] = 1;
				}
			}
		}
		if(!$from or $from == 'site' or $from == 'all') {
			$mem = array();
			$mails = "select u.ID, u.user_email from $wpdb->users u, $wpdb->usermeta um where (u.ID=um.user_id && um.meta_key='".$wpdb->prefix."user_level' &&  meta_value<>'10') && (";
			$mm = array_diff_key($smembers, $lmembers);
			foreach($mm as $mail => $time) {
				$mails .= "u.user_email = '$mail' || ";
				$mem[] = array('EMAIL' => $mail, 'EMAIL_TYPE' => 'html');
			}
			foreach($wpdb->get_results(rtrim($mails, ' |').')', ARRAY_A) as $usr) {
				$user = new WP_User($usr['ID']);
				if($user->first_name == '' and $user->last_name == '') continue;
				foreach($mem as $k => $v) {
					if($mem[$k]['EMAIL'] != $usr['user_email']) continue;
					$mem[$k]['FNAME'] = $user->first_name;
					$mem[$k]['LNAME'] = $user->last_name;
					break;
				}
			}
$KDB .= "kc_s2m_mc_synch::mem -> !<pre>".print_r($mem, true)."</pre>!<br>\n";
			kcs2mmc_listBatchSubscribe($api, $list, $mem);
		}
	}
#KC_ErrLog(array('subject' => 'kc_s2m_mc_synch'));
}

function kc_s2m_mc_wh() {
	global $wpdb;
	if(isset($_POST['type']) and isset($_REQUEST['k']) and $_REQUEST['k'] == md5($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"])) {
		if(!array_key_exists($_POST['data']['list_id'], get_option('kc_s2m_mc_lists'))) exit();
		switch($_POST['type']) {
			case 'subscribe':
				$user = array('user_login' => $_POST['data']['email'], 'user_pass' => $_POST['data']['id'], 'user_email' => $_POST['data']['email']);
				if($_POST['data']['merges']['FNAME']) $user['first_name'] = $_POST['data']['merges']['FNAME'];
				if($_POST['data']['merges']['LNAME']) $user['last_name'] = $_POST['data']['merges']['LNAME'];
				wp_insert_user($user);
				break;
			case 'unsubscribe':
				switch($_POST['data']['action']) {
					case 'delete':
						kc_s2m_mc_delete_user($_POST['data']['email'], $_POST['data']['list_id']);
						break;
					case 'unsub':
						break;
					default:
						break;
				}
				break;
			case 'profile':
				@sleep(2);
				$ids = $wpdb->get_results("select u.ID from $wpdb->users u, $wpdb->usermeta um where u.ID=um.user_id && um.meta_key='".$wpdb->prefix."user_level' &&  meta_value<>'10' && user_email='{$_POST['data']['email']}'", ARRAY_A);
				if($ids) {
					$user['user_email'] = $_POST['data']['email'];
					if($_POST['data']['merges']['FNAME']) $user['first_name'] = $_POST['data']['merges']['FNAME'];
					if($_POST['data']['merges']['LNAME']) $user['last_name'] = $_POST['data']['merges']['LNAME'];
					foreach($ids as $id) {
						$user['ID'] = $id['ID'];
						wp_update_user($user);
					}
				}
				break;
			case 'upemail':
				$wpdb->query("UPDATE $wpdb->users SET user_email = '{$_POST["data"]["new_email"]}' WHERE user_email = '{$_POST["data"]["old_email"]}'");
				break;
			case 'cleaned':
				kc_s2m_mc_delete_user($_POST['data']['email'], $_POST['data']['list_id']);
				break;
			case 'campaign':
				break;
			default:
				break;
		}
	}
	echo 'Thanks';
	exit();
}

function kc_s2m_mc_delete_user($email, $list) {
	$ids = $wpdb->get_results("select u.ID, u.user_email from $wpdb->users u, $wpdb->usermeta um where u.ID=um.user_id && um.meta_key='".$wpdb->prefix."user_level' &&  meta_value<>'10' && user_email='$email'", ARRAY_A);
	if($ids) {
		require_once 'MailChimp.php';
		$api = new MailChimp($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["mailchimp_api_key"]);
		$lists = array_diff(array_keys(kc_s2m_mc_getLists()), array($list));
		$reassign = $wpdb->get_var("select user_id from $wpdb->usermeta where meta_key='".$wpdb->prefix."user_level' && meta_value='10' limit 1");
		$mem = array();
		$member = array();
		foreach($ids as $id) {
			$mem[] = $id['user_email'];
			$member[$id['user_email']] = $id['ID'];
		}
		foreach($lists as $l) {
			$rez = kcs2mmc_listMemberInfo($api, $l, $mem);
			foreach($rez as $k => $m) unset($member[$m['merges']['EMAIL']]);
		}
		foreach($member as $m => $mid) wp_delete_user($mid, $reassign);
	}
}

function kcs2mmc_listMembers($api, $list) {
global $KDB;
$KDB .= "kcs2mmc_listMembers::list -> !<pre>".print_r($list, true)."</pre>!<br>\n";
	$result = $api->get("lists/$list/members", array('fields' => 'members.email_address', 'status' => 'subscribed'));
#	$result = $api->get("lists/$list/members", array('status' => 'subscribed'));
	$rez = array();
$KDB .= "kcs2mmc_listMembers::api->success() -> !<pre>".print_r($api->success(), true)."</pre>!<br>\n";
	if($api->success()) {
$KDB .= "kcs2mmc_listMembers::api->success() -> !YES!<br>\n";
$KDB .= "kcs2mmc_listMembers::result -> !<pre>".print_r($result, true)."</pre>!<br>\n";
		if(isset($result['members'])) foreach($result['members'] as $id => $member) $rez[] = array('email' => $member['email_address']);
	} else {
$KDB .= "kcs2mmc_listMembers::api->success() -> !NO!<br>\n";
$KDB .= "kcs2mmc_listMembers::api->getLastError() -> !<pre>".print_r($api->getLastError(), true)."</pre>!<br>\n";
		KC_ErrLog(array('error' => $api->getLastError()));
	}
	return $rez;
}

function kcs2mmc_listMemberInfo($api, $list, $members, $single=false) {
global $KDB;
$KDB .= "kcs2mmc_listMemberInfo::IN -> !<pre>".print_r(array('list' => $list, 'members' => $members, 'single' => $single), true)."</pre>!<br>\n";
	if($single) {
$KDB .= "kcs2mmc_listMemberInfo::if($single) -> !<pre>".($single)."</pre>!<br>\n";
$KDB .= "kcs2mmc_listMemberInfo::(true === single) -> !<pre>".(true === $single)."</pre>!<br>\n";
		$rez = false;
		$result = $api->get("lists/$list/members/".$api->subscriberHash($members), (true === $single)?array('fields' => 'status'):null);
		if($api->success()) {
			if(true === $single and $result["status"] == "subscribed") {
				$rez = true;
			} else {
				$rez = $result;
			}
		} else {
			KC_ErrLog(array('error' => $api->getLastError()));
		}
	} else {
		$rez = array();
		if(!is_array($members)) $members = array($members);
		$result = $api->get("lists/$list/members", array('fields' => 'members.email_address,members.merge_fields,members.status'));
$KDB .= "kcs2mmc_listMemberInfo::members -> !<pre>".print_r($result['members'], true)."</pre>!<br>\n";
		if($api->success()) {
			foreach($result['members'] as $id => $member) {
				if(in_array($member['email_address'], $members)) {
#$KDB .= "kcs2mmc_listMemberInfo::member -> !<pre>".print_r($member, true)."</pre>!<br>\n";
					$rez[] = array('email' => $member['email_address'], 'merges' => $member['merge_fields'], 'status' => $member['status']);
				}
			}
		} else {
			KC_ErrLog(array('error' => $api->getLastError()));
		}
	}
	return $rez;
}

function kcs2mmc_listBatchSubscribe($api, $list, $members) {
	$Batch = $api->new_batch();
	$me = array();
	foreach($members as $i => $m) $me[] = $m['EMAIL'];
	$mem = array();
	foreach(kcs2mmc_listMemberInfo($api, $list, $me) as $i => $m) $mem[$m['email']] = $m['status'];
	foreach($members as $id => $member) {
		$opt_in = (isset($mem[$member['EMAIL']]) and $mem[$member['EMAIL']] == 'subscribed')?false:$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["custom_reg_opt_in"];
		$Batch->put("op$id", "lists/$list/members/".$api->subscriberHash($member['EMAIL']), kcs2mmc_prepareForSubscribe($member, $opt_in));
	}
	$result = $Batch->execute();
}

function kcs2mmc_prepareForSubscribe($member, $opt_in) {
	$status = ($opt_in)?"pending":'subscribed';
	$newmem = array('email_address' => $member['EMAIL'], 'status' => $status, 'status_if_new' => $status, "email_type" => "html");
#	unset($member['EMAIL'], $member['OPTIN_IP'], $member['OPTIN_TIME']);
	unset($member['EMAIL']);
	$merges = array();
	foreach($member as $i => $v) $merges[$i] = $v;
	$newmem['merge_fields'] = (object)$merges;
	return $newmem;
}

function kcs2mmc_listUnsubscribe($api, $list, $email) {
	$api->delete("lists/$list/members/".$api->subscriberHash($email));
#	$rez = true;
#	if($api->success()) {
#	} else {
#		$rez = false;
#		KC_ErrLog(array('error' => $api->getLastError()));
#	}
#	return $rez;
}

function kcs2mmc_listWebhooks($api, $list) {
	global $kcs2mmc_Webhooks;
	if(!isset($kcs2mmc_Webhooks)) $kcs2mmc_Webhooks = array();
	if(isset($kcs2mmc_Webhooks[$list])) {
		return $kcs2mmc_Webhooks[$list];
	} else {
		$result = $api->get("lists/$list/webhooks");
		if(!$api->success() or !isset($result['webhooks'])) {
			KC_ErrLog(array('error' => $api->getLastError()));
		}
		$kcs2mmc_Webhooks[$list] = $result['webhooks'];
		return $result['webhooks'];
	}
}

function kcs2mmc_listWebhookAdd($api, $list, $whurl, $events, $sources) {
	$result = $api->post("lists/$list/webhooks", array('url' => $whurl, 'events' => $events, 'sources' => $sources));
	if(!$api->success()) {
		KC_ErrLog(array('error' => $api->getLastError()));
	}
	return $result;
}

function kcs2mmc_listWebhookDel($api, $list, $whurl) {
	global $kcs2mmc_Webhooks;
	$id = kcs2mmc_listWebhooks($api, $list);
	$id = '';
	foreach($kcs2mmc_Webhooks[$list] as $i => $wh) {
		if($wh['url'] == $whurl) {
			$result = $api->delete("lists/$list/webhooks/".$wh['id']);
			if(!$api->success()) {
				KC_ErrLog(array('error' => $api->getLastError()));
			} else {
				unset($kcs2mmc_Webhooks[$list][$i]);
			}
			break;
		}
	}
}

if(!function_exists('KC_ErrLog')) {
function KC_ErrLog($err='') {
	global $KDB;
#$KDB = '';
	$e = new Exception;
	$message="_________Create time__________\n+!".date(DATE_RSS)."!<br>\n";
	$message.="_________IN Variables __________\n!<pre>".print_r($_REQUEST, true)."</pre>!<br>\n";
	$message.="\n_________Environment Variables__________\n!<pre>".print_r($_SERVER, true)."</pre>!<br>\n";
	if($KDB) $message.="\n_________ KDB __________\n$KDB";
	if($err) $message.="\n_________ERROR__________\n!<pre>".print_r($err, true)."</pre>!<br>\n";
	$message.="_________CALL STACK __________\n!<pre>".print_r($e->getTraceAsString(), true)."</pre><br>\n";
	unset($e);
	if($KDB) {
		if(function_exists('KC_Err')) { KC_Err($message); } else { echo "<div class=\"updated fade\"><p>$message</p></div>"; }
#	} else {
#		echo "<div class=\"updated fade\"><p>$message</p></div>";
	}
}
}

?>
