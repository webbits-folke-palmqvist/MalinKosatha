<?php
/**
 * Places security information in a security log
 * The logged data includes:
 * <ul>
 * 	<li>the ip address of the client browser</li>
 * 	<li>the type of entry</li>
 * 	<li>the user/user name</li>
 * 	<li>the success/failure</li>
 * 	<li>the <i>authority</i> granting/denying the request</li>
 * 	<li>Additional information, for instance on failure, the password used</li>
 * </ul>
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_is_filter = 10|CLASS_PLUGIN;
$plugin_description = gettext('Logs selected security events.');
$plugin_author = "Stephen Billard (sbillard)";

$option_interface = 'security_logger';

if (getOption('logger_log_admin')) {
	zp_register_filter('admin_login_attempt', 'security_logger::adminLoginLogger');
	zp_register_filter('federated_login_attempt', 'security_logger::federatedLoginLogger');
}
if (getOption('logger_log_guests')) zp_register_filter('guest_login_attempt', 'security_logger::guestLoginLogger');
zp_register_filter('admin_allow_access', 'security_logger::adminGate');
zp_register_filter('admin_managed_albums_access', 'security_logger::adminAlbumGate');
zp_register_filter('save_user', 'security_logger::UserSave');
zp_register_filter('admin_XSRF_access', 'security_logger::admin_XSRF_access');
zp_register_filter('admin_log_actions', 'security_logger::log_action');
zp_register_filter('log_setup','security_logger::log_setup');

/**
 * Option handler class
 *
 */
class security_logger {
	/**
	 * class instantiation function
	 *
	 * @return security_logger
	 */
	function __construct() {
		setOptionDefault('logger_log_guests', 1);
		setOptionDefault('logger_log_admin', 1);
		setOptionDefault('logger_log_type', 'all');
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Record logon attempts of') => array('key' => 'logger_log_allowed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
										'checkboxes' => array(gettext('Administrators') => 'logger_log_admin', gettext('Guests') => 'logger_log_guests'),
										'desc' => gettext('If checked login attempts will be logged.')),
									gettext('Record') =>array('key' => 'logger_log_type', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array(gettext('All attempts') => 'all', gettext('Successful attempts') => 'success', gettext('unsuccessful attempts') => 'fail'),
										'desc' => gettext('Record login failures, successes, or all attempts.'))
		);
	}

	function handleOption($option, $currentValue) {
	}

	/**
	 * Does the log handling
	 *
	 * @param int $success
	 * @param string $user
	 * @param string $name
	 * @param string $ip
	 * @param string $type
	 * @param string $authority kind of login
	 * @param string $addl more info
	 */
	private static function Logger($success, $user, $name, $ip, $action, $authority, $addl=NULL) {
		global $_zp_authority;
		$admin = Zenphoto_Authority::getAnAdmin(array('`user`=' => $_zp_authority->master_user, '`valid`=' => 1));
		if ($admin) {
			$locale = $admin->getLanguage();
		}
		if (empty($locale)) {
			$locale = 'en_US';
		}
		$cur_locale = getUserLocale();
		setupCurrentLocale($locale);	//	the log will be in the language of the master user.
		switch ($action) {
			case 'clear_log':
				$type = gettext('Log reset');
				break;
			case 'delete_log':
				$type = gettext('Log deleted');
				break;
			case 'download_log':
				$type = gettext('Log downloaded');
				break;
			case 'setup_install':
				$type = gettext('Install');
				$addl = gettext('version').' '.ZENPHOTO_VERSION.'['.ZENPHOTO_RELEASE."]";
				if (!zpFunctions::hasPrimaryScripts()) {
					$addl .= ' '.gettext('clone');
				}
				break;
			case 'setup_delete':
				$type = gettext('Remove setup files');
				break;
			case 'user_new':
				$type = gettext('Request add user');
				break;
			case 'user_update':
				$type = gettext('Request update user');
				break;
			case 'user_delete':
				$type = gettext('Request delete user');
				break;
			case 'XSRF access blocked':
			$type = gettext('XSRF access blocked');
				break;
			case 'Blocked album':
				$type = gettext('Blocked album');
				break;
			case 'Blocked access':
				$type = gettext('Blocked access');
				break;
			case 'Front-end':
				$type = gettext('Guest login');
				break;
			case 'Back-end':
				$type = gettext('Admin login');
				break;
			default:
				$type = $action;
		}

		$file = SERVERPATH.'/'.DATA_FOLDER . '/security.log';
		$preexists = file_exists($file) && filesize($file) > 0;
		$f = fopen($file, 'a');
		if($f) {
			if (!$preexists) { // add a header
				fwrite($f, gettext('date'."\t".'requestor\'s IP'."\t".'type'."\t".'user ID'."\t".'user name'."\t".'outcome'."\t".'authority'."\tadditional information\n"));
			}
			$message = date('Y-m-d H:i:s')."\t";
			$message .= $ip."\t";
			$message .= $type."\t";
			$message .= $user."\t";
			$message .= $name."\t";
			if ($success) {
				$message .= gettext("Success")."\t";
				$message .= substr($authority, 0, strrpos($authority,'_auth'));
			} else {
				$message .= gettext("Failed")."\t";
			}
			if ($addl) {
				$message .= "\t".$addl;
			}
			fwrite($f, $message . "\n");
			fclose($f);
			clearstatcache();
			if (!$preexists) {
				@chmod($file, 0600);
				if (strtoupper(substr(PHP_OS, 0,3)) == 'WIN') {
					$permission = fileperms($file)&0700;	//	on Windows owner==group==public
				} else {
					$permission = fileperms($file)&0777;
				}
				if ($permission != 0600) {
					$f = fopen($file, 'a');
					fwrite($f,"\t\t".gettext('Set Security log permissions')."\t\t\t".gettext('Failed')."\t\t".sprintf(gettext('File permissions of Security log are %04o'),$permission)."\n");
					fclose($f);
					clearstatcache();
				}
			}
		}
		setupCurrentLocale($cur_locale);	//	restore to whatever was in effect.
	}

	/**
	 * returns the user id and name of the logged in user
	 */
	private static function populate_user() {
		global $_zp_current_admin_obj;
		if (is_object($_zp_current_admin_obj)) {
			$user = $_zp_current_admin_obj->getUser();
			$name = $_zp_current_admin_obj->getName();
		} else {
			$user= $name = '';
		}
		return array($user,$name);
	}

	/**
	 * Logs an attempt to log onto the back-end or as an admin user
	 * Returns the rights to grant
	 *
	 * @param int $success the admin rights granted
	 * @param string $user
	 * @param string $pass
	 * @return int
	 */
	static function adminLoginLogger($success, $user, $pass, $auth='zp_admin_auth') {
		switch (getOption('logger_log_type')) {
			case 'all':
				break;
			case 'success':
				if (!$success) return false;
				break;
			case 'fail':
				if ($success) return true;
				break;
		}
		$name = '';
		if ($success) {
			$admin = Zenphoto_Authority::getAnAdmin(array('`user`=' => $user, '`valid`=' => 1));
			$pass = '';	// mask it from display
			if (is_object($admin)) {
				$name = $admin->getName();
			}
		}
		security_logger::Logger($success, $user, $name, getUserIP(), 'Back-end', $auth, $pass);
		return $success;
	}

	/**
	 * Logs an attempt to log on via the federated_logon plugin
	 * Returns the rights to grant
	 *
	 * @param int $success the admin rights granted
	 * @param string $user
	 * @param string $pass
	 * @return int
	 */
	static function federatedLoginLogger($success, $user) {
		return security_logger::adminLoginLogger($success, $user, 'n/a', 'federated_logon_auth');
	}

	/**
	 * Logs an attempt for a guest user to log onto the site
	 * Returns the "success" parameter.
	 *
	 * @param bool $success
	 * @param string $user
	 * @param string $pass
	 * @param string $athority what kind of login
	 * @return bool
	 */
	static function guestLoginLogger($success, $user, $pass, $athority) {
		switch (getOption('logger_log_type')) {
			case 'all':
				break;
			case 'success':
				if (!$success) return false;
				break;
			case 'fail':
				if ($success) return true;
				break;
		}
	$name = '';
		if ($success) {
			$admin = Zenphoto_Authority::getAnAdmin(array('`user`=' => $user, '`valid`=' => 1));
			$pass = '';	// mask it from display
			if (is_object($admin)) {
				$name = $admin->getName();
			}
		}
		security_logger::Logger($success, $user, $name, getUserIP(), 'Front-end', $athority, $pass);
		return $success;
	}

	/**
	 * Logs blocked accesses to Admin pages
	 * @param bool $allow set to true to override the block
	 * @param string $page the "return" link
	 */
	static function adminGate($allow, $page) {
		list($user,$name) = security_logger::populate_user();
		security_logger::Logger(false, $user, $name, getUserIP(), 'Blocked access', '', $page);
		return $allow;
	}

	/**
	 * Logs blocked accesses to Managed albums
	 * @param bool $allow set to true to override the block
	 * @param string $page the "return" link
	 */
	static function adminAlbumGate($allow, $page) {
		list($user,$name) = security_logger::populate_user();
		security_logger::Logger(false, $user, $name, getUserIP(), 'Blocked album', '', $page);
		return $allow;
	}

	/**
	 * logs attempts to save on the user tab
	 * @param string $discard
	 * @param object $userobj user object upon which the save was targeted
	 * @param string $class what the action was.
	 */
	static function UserSave($discard, $userobj, $class) {
		list($user,$name) = security_logger::populate_user();
		security_logger::Logger(true, $user, $name, getUserIP(), 'user_'.$class, 'zp_admin_auth', $userobj->getUser());
		return $discard;
	}

	/**
	 * Loggs Cross Site Request Forgeries
	 *
	 * @param bool $discard
	 * @param string $token
	 * @return bool
	 */
	static function admin_XSRF_access($discard, $token) {
		list($user,$name) = security_logger::populate_user();
		security_logger::Logger(false, $user, $name, getUserIP(), 'XSRF access blocked', '', $token);
		return false;
	}

	/**
	 * logs security log actions
	 * @param bool $allow
	 * @param string $log
	 * @param string $action
	 */
	static function log_action($allow, $log, $action) {
		list($user,$name) = security_logger::populate_user();
		security_logger::Logger($allow, $user, $name, getUserIP(), $action, 'zp_admin_auth', basename($log));
		return $allow;
	}

	/**
	 * Logs setup actions
	 * @param bool $success
	 * @param string $action
	 * @param string $file
	 */
	static function log_setup($success, $action, $txt) {
		list($user,$name) = security_logger::populate_user();
		security_logger::Logger($success, $user, $name, getUserIP(), 'setup_'.$action, 'zp_admin_auth', $txt);
		return $success;
	}

}
?>