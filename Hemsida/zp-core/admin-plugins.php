<?php
/**
 * provides the Plugins tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-globals.php');

admin_securityChecks(NULL, currentRelativeURL());

$_GET['page'] = 'plugins';

/* handle posts */
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'saveplugins') {
		XSRFdefender('saveplugins');
		$filelist = getPluginFiles('*.php');
		foreach ($filelist as $extension=>$path) {
			$extension = filesystemToInternal($extension);
			$opt = 'zp_plugin_'.$extension;
			if (isset($_POST[$opt])) {
				$value = sanitize_numeric($_POST[$opt]);
				if (!getOption($opt)) {
					$option_interface = NULL;
					require_once($path);
					if (is_string($option_interface)) {
						$if = new $option_interface;	//	prime the default options
					}
				}
				setOption($opt, $value);
			} else {
				setOption($opt, 0);
			}
		}
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-plugins.php?saved");
		exitZP();
	}
}
$saved = isset($_GET['saved']);
printAdminHeader('plugins');
zp_apply_filter('texteditor_config', '','zenphoto');
?>
<script type="text/javascript">
	<!--
	function toggleDetails(plugin) {
		toggle(plugin+'_show');
		toggle(plugin+'_hide');
	}

	$(document).ready(function(){
		$(".plugin_doc").colorbox({
			close: '<?php echo gettext("close"); ?>',
			maxHeight:"98%",
			innerWidth:'560px'
		});
	});

	//-->
</script>
<?php
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
$paths = getPluginFiles('*.php');
$filelist = array_keys($paths);
natcasesort($filelist);
printTabs();
echo "\n" . '<div id="content">';

/* Page code */

if ($saved) {
	echo '<div class="messagebox fade-message">';
	echo  "<h2>".gettext("Applied")."</h2>";
	echo '</div>';
}

?>
<h1><?php echo gettext('Plugins'); ?></h1>
<p>
<?php
echo gettext("Plugins provide optional functionality for Zenphoto.").' ';
echo gettext("They may be provided as part of the Zenphoto distribution or as offerings from third parties.").' ';
echo sprintf(gettext("Third party plugins are placed in the <code>%s</code> folder and are automatically discovered."),USER_PLUGIN_FOLDER).' ';
echo gettext("If the plugin checkbox is checked, the plugin will be loaded and its functions made available to theme pages. If the checkbox is not checked the plugin is disabled and occupies no resources.");
?>
<a href="http://www.zenphoto.org/news/category/extensions" alt="Zenphoto extensions section"> <?php echo gettext('Find more plugins'); ?></a>
</p>
<p class='notebox'><?php echo gettext("<strong>Note:</strong> Support for a particular plugin may be theme dependent! You may need to add the plugin theme functions if the theme does not currently provide support."); ?>
</p>
<form action="?action=saveplugins" method="post">
	<?php XSRFToken('saveplugins');?>
	<input type="hidden" name="saveplugins" value="yes" />
<p class="buttons">
<button type="submit" value="<?php echo gettext('Apply') ?>" title="<?php echo gettext("Apply"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Apply"); ?></strong></button>
<button type="reset" value="<?php echo gettext('Reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p><br clear="all" /><br /><br />
<table class="bordered options">
<tr>
<th><?php echo gettext("Available Plugins"); ?></th>
<th colspan="2">
	<?php echo gettext("Description"); ?>
</th>
<?php
foreach ($filelist as $extension) {
	$opt = 'zp_plugin_'.$extension;
	$third_party_plugin = strpos($paths[$extension],ZENFOLDER) === false;
	$pluginStream = file_get_contents($paths[$extension]);
	$parserr = 0;
	if ($str = isolate('$plugin_description', $pluginStream)) {
		if (false === eval($str)) {
			$parserr = $parserr | 1;
			$plugin_description = gettext('<strong>Error parsing <em>plugin_description</em> string!</strong>.');
		}
	} else {
		$plugin_description = '';
	}
	if ($str = isolate('$plugin_notice', $pluginStream)) {
		if (false === eval($str)) {
			$parserr = $parserr | 1;
			$plugin_notice = gettext('<strong>Error parsing <em>plugin_notice</em> string!</strong>.');
		}
	} else {
		$plugin_notice = '';
	}
	if ($str = isolate('$plugin_author', $pluginStream)) {
		if (false === eval($str)) {
			$parserr = $parserr | 2;
			$plugin_author = gettext('<strong>Error parsing <em>plugin_author</em> string!</strong>.');
		}
	} else {
		$plugin_author = '';
	}
	if ($str = isolate('$plugin_version', $pluginStream)) {
		if (false === eval($str)) {
			$parserr = $parserr | 4;
			$plugin_version = ' '.gettext('<strong>Error parsing <em>plugin_version</em> string!</strong>.');
		}
	} else {
		$plugin_version = '';
	}
	if ($str = isolate('$plugin_disable', $pluginStream)) {
		if (false === eval($str)) {
			$parserr = $parserr | 8;
			$plugin_URL = gettext('<strong>Error parsing <em>plugin_disable</em> string!</strong>.');
		} else {
			if ($plugin_disable) {
				setOption($opt, 0);
			}
		}
	} else {
		$plugin_disable = false;
	}
	$plugin_URL = FULLWEBPATH.'/'.ZENFOLDER.'/pluginDoc.php?extension='.$extension;
	if ($third_party_plugin) {
		$plugin_URL .= '&amp;thirdparty';
	}
	$currentsetting = getOption($opt);
	$plugin_is_filter = 1|THEME_PLUGIN;
	if ($str = isolate('$plugin_is_filter', $pluginStream)) {
		eval($str);
		if ($plugin_is_filter < THEME_PLUGIN) {
			if ($plugin_is_filter < 0) {
				$plugin_is_filter = abs($plugin_is_filter)|THEME_PLUGIN|ADMIN_PLUGIN;
			} else {
				if ($plugin_is_filter == 1) {
					$plugin_is_filter = 1|THEME_PLUGIN;
				} else {
					$plugin_is_filter = $plugin_is_filter|CLASS_PLUGIN;
				}
			}
		}
		if ($currentsetting && $currentsetting != $plugin_is_filter) {
			setOption($opt, $plugin_is_filter);	//	the script has changed its setting!
		}
	}
	if ($optionlink = isolate('$option_interface', $pluginStream)) {
		if (preg_match('/\s*=\s*new\s(.*)\(/i',$optionlink)) {
			$plugin_notice .= '<br /><br />'.gettext('<strong>Note:</strong> Instantiating the option interface within the plugin may cause performance issues. You should instead set <code>$option_interface</code> to the name of the class as a string.');
		}
		$optionlink = FULLWEBPATH.'/'.ZENFOLDER.'/admin-options.php?page=options&amp;tab=plugin&amp;single='.$extension;
	} else {
		$optionlink = NULL;
	}
	$selected_style = '';
	if ($currentsetting > THEME_PLUGIN) {
		$selected_style = ' class="currentselection"';
	}
	?>
	<tr<?php echo $selected_style;?>>
		<td width="30%">
			<label id="<?php echo $extension; ?>">
				<?php
				if ($third_party_plugin) {
					$whose = gettext('third party plugin');
					$path = stripSuffix($paths[$extension]).'/logo.png';
					if (file_exists($path)) {
						$ico = str_replace(SERVERPATH, WEBPATH, $path);
					} else {
						$ico = 'images/place_holder_icon.png';
					}
				} else {
					$whose = 'Zenphoto official plugin';
					$ico = 'images/zp_gold.png';
				}
				?>
				<img class="zp_logoicon" src="<?php echo $ico; ?>" alt="<?php echo gettext('logo'); ?>" title="<?php echo $whose; ?>" />
				<?php
				$attributes = '';
				if ($parserr) {
					$optionlink = false;
					$attributes .= ' disabled="disabled"';
				} else {
					if ($currentsetting > THEME_PLUGIN) {
						$attributes .= ' checked="checked"';
					}
				}
				if ($plugin_disable) {
					?>
					<span class="icons" id="<?php echo $extension;?>_checkbox">
						<a href="javascript:toggle('showdisable_<?php echo $extension; ?>');" title="<?php  echo gettext('This plugin is disabled. Click for details.'); ?>">
							<img src="images/action.png" alt="" class="zp_logoicon" />
						</a>
						<input type="hidden" name="<?php echo $opt; ?>" id="<?php echo $opt; ?>" value="0"	/>
					</span>
					<?php
				} else {
					?>
					<input type="checkbox" name="<?php echo $opt; ?>" id="<?php echo $opt; ?>" value="<?php echo $plugin_is_filter; ?>"<?php echo $attributes; ?>	/>
					<?php
				}
				echo $extension;
				if (!empty($plugin_version)) {
					echo ' v'.$plugin_version;
				}
				?>
			</label>
		</td>
		<td width="60">
			<span class="icons"><a class="plugin_doc" href="<?php echo $plugin_URL; ?>"><img class="icon-position-top3" src="images/info.png" title="<?php printf(gettext('More information on %s'),$extension); ?>" alt=""></a></span>
			<?php
			if ($optionlink && !$plugin_disable) {
				?>
				<span class="icons"><a href="<?php echo $optionlink; ?>" title="<?php printf(gettext("Change %s options"),$extension); ?>"><img class="icon-position-top3" src="images/options.png" alt="" /></a></span>
				<?php
			}
			if ($plugin_notice) {
				?>
				<span class="icons"><a href="javascript:toggle('show_<?php echo $extension;?>');" title ="<?php echo gettext('Plugin warnings'); ?>" ><img class="icon-position-top3" src="images/warn.png" alt="" /></a></span>
				<?php
			}
			?>
		</td>
		<td>
			<?php
			echo $plugin_description;
			if ($plugin_disable) {
				?>
				<div id="showdisable_<?php echo $extension; ?>" style="display:none" class="warningbox">
					<?php
					if ($plugin_disable) {
						echo $plugin_disable;
					}
					?>
				</div>
				<?php
			}
			if ($plugin_notice) {
				?>
				<div id="show_<?php echo $extension; ?>" style="display:none" class="notebox">
					<?php
					if ($plugin_notice) {
						echo $plugin_notice;
					}
					?>
				</div>
				<?php
			}
			?>
		</td>
	</tr>
	<?php
}
?>
</table>
<br />
<ul class="iconlegend">
<li><img src="images/zp_gold.png" alt=""><?php echo gettext('Official plugin'); ?></li>
<li><img src="images/info.png" alt=""><?php echo gettext('Usage info'); ?></li>
<li><img src="images/options.png" alt=""><?php echo gettext('Options'); ?></li>
<li><img src="images/warn.png" alt=""><?php echo gettext('Warning note'); ?></li>
</ul>
<p class="buttons">
<button type="submit" value="<?php echo gettext('Apply') ?>" title="<?php echo gettext("Apply"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Apply"); ?></strong></button>
<button type="reset" value="<?php echo gettext('Reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p><br />
<?php
echo "</form>\n";

echo "\n" . '</div>';  //content
printAdminFooter();
echo "\n" . '</div>';  //main
echo "\n</body>";
echo "\n</html>";
?>



