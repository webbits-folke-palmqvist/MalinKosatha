<?php
/**
 * Support for the jPlayer jQuery/Flash 2.0.0 multimedia player (jplayer.org). It will play natively via HTML5 in capable browser
 * if the appropiate multimedia formats are provided. This is not an adaption of the existing 3rd party plugin zenjPlayer but a full featured plugin.

 * Audio: <var>.mp3</var>, <var>.m4a</var>, <var>.fla</var> - Counterpart formats <var>.oga</var> and <var>.webma</var> supported (see note below!)<br>
 * Video: <var>.m4v</var>/<var>.mp4</var>, <var>.flv</var> - Counterpart formats <var>.ogv</var> and <var>.webmv</var> supported (see note below!)
 *
 * IMPORTANT NOTE ON OGG AND WEBM COUNTERPART FORMATS:
 *
 * The counterpart formats are not valid formats for Zenphoto itself as that would confuse the management.
 * Therefore these formats can be uploaded via ftp only.
 * The files needed to have the same file name (beware the character case!). In single player usage the player
 * will check via file system if a counterpart file exists if counterpart support is enabled.
 * <b>NOTE:</b> Counterpart format does not work correctly on playlists yet. Detailed reason: Priority solution
 * setting must be "flash" as otherwise flv and fla will not work on some browsers like Safari.
 * This in return disables counterpart support for ogg and webm files for some reason on Firefox).
 * Since the flash fallback covers all essential formats ths is not much of an issue for visitors though.
 *
 * Otherwise it will not work. It is all or none.
 * See {@link http://jplayer.org/latest/developer-guide/#reference-html5-media the developer guide} for info on that.
 *
 * NOTE ON PLAYER SKINS:<br>
 * The look of the player is determined by a pure HTML/CSS based skin (theme). There may occur display issues with themes.
 * Only the Zenphoto's own default skin <var>zenphotolight</var>
 * has been tested with the standard themes (and not even with all it works perfectly).
 * So you might need to adjust the skin yourself to work with your theme. It is recommended that
 * you place your custom skins within the root /plugins folder like:
 *
 * plugins/jplayer/skin/<i>skin name1</i><br>
 * plugins/jplayer/skin/<i>skin name2</i> ...
 *
 * You can select the skin then via the plugin options. <b>NOTE:</b> A skin may have only one CSS file.
 *
 * USING PLAYLISTS:<br>
 * You can use <var>$_zp_flash_player->printjPlayerPlaylist()</var> on your theme's album.php directly to display a
 * video/audio playlist (default) or an audio only playlist.
 * Alternativly you can show a playlist of a specific album anywhere. In any case you need to modify your theme.
 * See the documentation for the parameter options.
 *
 * <b>NOTE:</b> This player does not support external albums!
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 */

$plugin_description = gettext("Enable <strong>jPlayer</strong> to handle multimedia files.");
$plugin_notice = gettext("<strong>IMPORTANT</strong>: Only one multimedia player plugin can be enabled at the time and the class-video plugin must be enabled, too.").'<br /><br />'.gettext("Please see <a href='http://jplayer.org'>jplayer.org</a> for more info about the player and its license.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_disable = (getOption('album_folder_class') === 'external')?gettext('This player does not support <em>External Albums</em>.'):false;
$option_interface = 'jplayer_options';

if (isset($_zp_flash_player) || $plugin_disable) {
	setOption('zp_plugin_jplayer',0);
	if (isset($_zp_flash_player)) {
		trigger_error(sprintf(gettext('jPlayer not enabled, %s is already instantiated.'),get_class($_zp_flash_player)),E_USER_NOTICE);
	}
} else {
	$_zp_flash_player = new jPlayer(); // claim to be the flash player.
	zp_register_filter('theme_head','jplayerJS');
	if(getOption('jplayer_playlist')) {
		zp_register_filter('theme_head','jplayer_playlistJS');
	}
}


function jplayerJS() {
	$skin = getOption('jplayer_skin');
	if(file_exists($skin)) {
		$skin = str_replace(SERVERPATH,WEBPATH,$skin); //replace SERVERPATH as that does not work as a CSS link
	} else {
		$skin = WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/jplayer/skin/zenphotolight/jplayer.zenphotolight.css';
	}
	?>
	<link href="<?php echo $skin; ?>" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="<?php echo WEBPATH . '/' . ZENFOLDER . '/'.PLUGIN_FOLDER; ?>/jplayer/js/jquery.jplayer.min.js"></script>
	<?php
}

function jplayer_playlistJS() {
	?>
	<script type="text/javascript" src="<?php echo WEBPATH . '/' . ZENFOLDER . '/'.PLUGIN_FOLDER; ?>/jplayer/js/jplayer.playlist.min.js"></script>
	<?php
}

class jplayer_options {

	function jplayer_options() {
		setOptionDefault('jplayer_autoplay', '');
		setOptionDefault('jplayer_poster', 1);
		setOptionDefault('jplayer_postercrop', 1);
		setOptionDefault('jplayer_showtitle', '');
		setOptionDefault('jplayer_playlist', '');
		setOptionDefault('jplayer_playlist_numbered', 1);
		setOptionDefault('jplayer_playlist_playtime', 0);
		setOptionDefault('jplayer_download', '');
		setOptionDefault('jplayer_size', 'jp-video-270p');
		setOptionDefault('jplayer_skin', 'zenphotolight');
		setOptionDefault('jplayer_counterparts', 0);
		/*TODO: what are these sizes?
		if (class_exists('cacheManager')) {
			$player = new jPlayer();
			cacheManager::deleteThemeCacheSizes('jplayer');
			cacheManager::addThemeCacheSize('jplayer', NULL, $player->width, $player->height, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
		}
		*/
	}


	function getOptionsSupported() {
		$skins = getjPlayerSkins();
	 /*
	 The player size is entirely styled via the CSS skin so there is no free size option. For audio (without thumb/poster) that is always 480px width.
	 The original jPlayer skin comes with 270p (480x270px) and 360p (640x360px) sizes for videos but the Zenphoto custom skin comes with some more like 480p and 1080p.
	 If you need different sizes than you need to make your own skin (see the skin option for info about that)
	 */

	 return array(gettext('Autoplay') => array('key' => 'jplayer_autoplay', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("Disabled automatically if several players on one page")),
								gettext('Poster (Videothumb)') => array('key' => 'jplayer_poster', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If the videothumb should be shown (jplayer calls it poster)")),
						   gettext('Poster cropping (Videothumb)') => array('key' => 'jplayer_postercrop', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If enable the videothumb to be cropped. Otherwise jPlayer will squish the image to fit if it does not have the correct aspect ratio.")),
						   gettext('Audio poster (Videothumb)') => array('key' => 'jplayer_audioposter', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If the poster should be shown for audio files (mp3,m4a,fla) (does not apply for playlists which are all or none).")),
						   gettext('Show title') => array('key' => 'jplayer_showtitle', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If the title should be shown below the player in single player mode (not needed on normal themes) (ignored in playlists naturally).")),
						   gettext('Playlist support') => array('key' => 'jplayer_playlist', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("Enable this if you wish to use the playlist mode this loads the scripts needed. NOTE: You have to add the function printjPlayerPlaylist() to your theme yourself. See the documentation for info.")),
						   gettext('Playlist numbered') => array('key' => 'jplayer_playlist_numbered', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("Enable this if you wish the playlist to be numbered.")),
							 gettext('Playlist playtime') => array('key' => 'jplayer_playlist_playtime', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("Enable if you want to show the playtime of playlist entries.")),
						   gettext('Enable download') => array('key' => 'jplayer_download', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("Enables direct file downloads (playlists only).")),
						   gettext('Player size') => array('key' => 'jplayer_size', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(
										gettext('jp-video-270p (480x270px)')=>"jp-video-270p",
										gettext('jp-video-360p (640x360px)')=>"jp-video-360p",
										gettext('jp-video-480p (720x405px)*')=>"jp-video-480p",
										gettext('jp-video-720p (1280x720px)*')=>"jp-video-720p",
										gettext('jp-video-1080p (1920x1080px)*')=>"jp-video-1080p"),
										'desc' => gettext("jPlayer cannot be sized freely as it is dependend on its HTML and CSS based skin. Sizes marked with a <strong>*</strong> are supported by the two Zenphoto custom skins only. If you need different sizes you need to modify a skin or make your own and also need to change values in the plugin class method getPlayerSize().")),
							 gettext('Player skin') => array('key' => 'jplayer_skin', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $skins,
										'desc' => gettext("Select the skin (theme) to use. <br />NOTE: Since the skin is pure HTML/CSS only there may be display issues with certain themes that require manual adjustments. Place custom skin within the root plugins folder. See plugin documentation for more info."))
							);
	}

}
/**
 * Gets the skin names and css files
 *
 */
function getjPlayerSkins() {
	$all_skins = array();
	$default_skins_dir = SERVERPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/jplayer/skin/';
	$user_skins_dir = SERVERPATH.'/'.USER_PLUGIN_FOLDER.'/jplayer/skin/';
	$filestoignore = array( '.', '..','.DS_Store','Thumbs.db','.htaccess','.svn');
	$skins = array_diff(scandir($default_skins_dir),array_merge($filestoignore));
	$default_skins = getjPlayerSkinCSS($skins,$default_skins_dir);
	//echo "<pre>";print_r($default_skins);echo "</pre>";
	$skins2 = @array_diff(scandir($user_skins_dir),array_merge($filestoignore));
	if(is_array($skins2)) {
		$user_skins = getjPlayerSkinCSS($skins2,$user_skins_dir);
		//echo "<pre>";print_r($user_skins);echo "</pre>";
		$default_skins = array_merge($default_skins,$user_skins);
	}
	return $default_skins;
}
/**
 * Gets the css files for a skin. Helper function for getjPlayerSkins().
 *
 */
function getjPlayerSkinCSS($skins,$dir) {
	$skin_css = array();
	foreach($skins as $skin) {
		$css = safe_glob($dir.'/'.$skin.'/*.css');
		if($css) {
			$skin_css = array_merge($skin_css,array($skin => $css[0]));	// a skin should only have one css file so we just use the first found
		}
	}
	return $skin_css;
}


class jPlayer {
	public $width = '';
	public $height = '';
	public $playersize = '';
	public $mode = '';
	public $supplied = '';
	public $supplied_counterparts = '';

	function __construct() {
		$this->playersize = getOption('jplayer_size');
		switch($this->playersize) {
			case 'jp-video-270p':
				$this->width = 480;
				$this->height = 270;
				break;
			case 'jp-video-360p':
				$this->width = 640;
				$this->height = 360;
				break;
			case 'jp-video-480p':
				$this->width = 720;
				$this->height = 405;
				break;
			case 'jp-video-720p':
		  	$this->width = 1280;
				$this->height = 720;
		  	break;
		  case 'jp-video-1080p':
		  	$this->width = 1920;
				$this->height = 1080;
		  	break;
		}
	}

	/**
	 * Get the JS configuration of jplayer
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the current image is used
	 * @param string $imagefilename the filename of the movie
	 * @param string $count number (preferredly the id) of the item to append to the css for multiple players on one page
	 * @param string $width Not supported as jPlayer is dependend on its CSS based skin to change sizes. Can only be set via plugin options.
	 * @param string $height Not supported as jPlayer is dependend on its CSS based skin to change sizes. Can only be set via plugin options.
	 *
	 */
	function getPlayerConfig($moviepath='', $imagefilename, $count='', $width='', $height='') {
		global $_zp_current_album, $_zp_current_image;
		if(empty($moviepath)) {
			$moviepath = $_zp_current_image->getFullImage();
		} else {
			$moviepath = $moviepath;
		}
		$ext = getSuffix($moviepath);
		if(!in_array($ext,array('m4a','m4v','mp3','mp4','flv','fla'))) {
			echo '<p>'.gettext('This multimedia format is not supported by jPlayer').'</p>';
			return NULL;
		}
		$this->setModeAndSuppliedFormat($ext);
		if(empty($count)) {
			$multiplayer = false;
			$count = '1';
		}	else {
			$multiplayer = true; // since we need extra JS if multiple players on one page
			$count = $count;
		}
		$autoplay = '';
		if(getOption('jplayer_autoplay') && !multiplayer) {
			$autoplay = ',jPlayer("play");';
		}
		$imgextensions = array("jpg","jpeg","gif","png");
		$videoThumb = '';
		if(getOption('jplayer_poster') && ($this->mode == 'video' || ($this->mode == 'audio' && getOption('jplayer_audioposter')))) {
			if(is_null($_zp_current_image)) {
				$albumfolder = $moviepath;
				$filename = $imagefilename;
				$videoThumb = '';
			} else {
				//$album = $_zp_current_album;
				//$albumfolder = $_zp_current_album->name;
				$filename = $_zp_current_image->filename;
				$splashimagerwidth = $this->width;
				$splashimageheight = $this->height;
				//getMaxSpaceContainer($splashimagerwidth, $splashimageheight, $_zp_current_image, true); // jplayer squishes always if not the right aspect ratio
				$videoThumb = ',poster:"'.html_encode($_zp_current_image->getCustomImage(null, $this->width, $this->height, $this->width, $this->height, null, null, true)).'"';
			}
		}
		$playerconfig = '
		<script type="text/javascript">
			//<![CDATA[
		$(document).ready(function(){
			$("#jquery_jplayer_'.$count.'").jPlayer({
				ready: function (event) {
					$(this).jPlayer("setMedia", {
						'.$this->supplied.':"'.pathurlencode($moviepath).'"
						'.$this->getCounterpartFiles($moviepath,$ext).'
						'.$videoThumb.'
					})'.$autoplay.';
				},
				swfPath: "'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/jplayer/js",
				supplied: "'.$this->supplied_counterparts.$this->supplied.'",
				cssSelectorAncestor: "#jp_container_'.$count.'"';

		if($multiplayer) {
			$playerconfig .= ',
				play: function() { // To avoid both jPlayers playing together.
				$(this).jPlayer("pauseOthers");
			}
			';
		}

		if($this->mode == 'video' || ($this->mode == 'audio' && getOption('jplayer_poster') && getOption('jplayer_audioposter'))) {
				$playerconfig .= '
				,	size: {
			width: "'.$this->width.'px",
			height: "'.$this->height.'px",
			cssClass: "'.$this->playersize.'"
		}';
			}

		$playerconfig .= '
			});
		});
	//]]>
  </script>';

  // I am really too lazy to figure everything out to optimize this quite complex html nesting so I generalized only parts.
  // This will also make it easier and more convenient to spot any html changes the jplayer developer might come up with later on (as he did from 2.0 to 2.1!)
		if($this->mode == 'video' || !empty($videoThumb)) {
			$playerconfig .= '
			<div id="jp_container_'.$count.'" class="jp-video '.$this->playersize.'">
			<div class="jp-type-single">
				<div id="jquery_jplayer_'.$count.'" class="jp-jplayer"></div>
				<div class="jp-gui">
					<div class="jp-video-play">
						<a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
					</div>
					<div class="jp-interface">
						<div class="jp-progress">
							<div class="jp-seek-bar">
								<div class="jp-play-bar"></div>
							</div>
						</div>
						<div class="jp-current-time"></div>
						<div class="jp-duration"></div>
						<div class="jp-controls-holder">';
				$playerconfig .= $this->getPlayerHTMLparts($this->mode,'controls');
				$playerconfig .= '
							<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>';
				$playerconfig .= $this->getPlayerHTMLparts($this->mode,'toggles');
				$playerconfig .= '
						</div>';
				if(getOption('jplayer_showtitle')) {
					$playerconfig .= '
						<div class="jp-title">
							<ul>
								<li>'.html_encode($_zp_current_image->getTitle()).'</li>
							</ul>
						</div>';
				}
					$playerconfig .= '
					</div>
				</div>';
				$playerconfig .= $this->getPlayerHTMLparts($this->mode,'no-solution');
				$playerconfig .= '
			</div>
		</div>
		';

		} else { // audio

		$playerconfig .= '
		<div id="jquery_jplayer_'.$count.'" class="jp-jplayer"></div>
		<div id="jp_container_'.$count.'" class="jp-audio">
			<div class="jp-type-single">
				<div class="jp-gui jp-interface">';
				$playerconfig .= $this->getPlayerHTMLparts($this->mode,'controls');
				$playerconfig .= '
					<div class="jp-progress">
						<div class="jp-seek-bar">
							<div class="jp-play-bar"></div>
						</div>
					</div>
					<div class="jp-volume-bar">
						<div class="jp-volume-bar-value"></div>
					</div>
					<div class="jp-time-holder">
						<div class="jp-current-time"></div>
						<div class="jp-duration"></div>';
				$playerconfig .= $this->getPlayerHTMLparts($this->mode,'toggles');
				$playerconfig .= '
					</div>
				</div>';
				if(getOption('jplayer_showtitle')) {
					$playerconfig .= '
						<div class="jp-title">
							<ul>
								<li>'.html_encode($_zp_current_image->getTitle()).'</li>
							</ul>
						</div>';
				}
				$playerconfig .= $this->getPlayerHTMLparts($this->mode,'no-solution');
				$playerconfig .= '
			</div>
		</div>
		';
	} // video/audio if else end
		return $playerconfig;

	}

	/**
	 * outputs the player configuration HTML
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the current image is used
	 * @param string $imagefilename the filename of the movie. if empty (within albums) the function getImageTitle() is used
	 * @param string $count unique text for when there are multiple player items on a page
	 */
	function printPlayerConfig($moviepath='',$imagefilename='',$count ='') {
		echo $this->getPlayerConfig($moviepath,$imagefilename,$count,NULL,NULL);
	}

	/**
	 * gets commonly used html parts for the player config
	 *
	 * @param string $mode 'video' or 'audio'
	 * @param string $part part to get: 'controls', 'controls-playlist', 'toggles', 'toggles-playlist','no-solution'
	 */
 function getPlayerHTMLparts($mode='',$part='') {
 	$htmlpart = '';
 	switch($part) {
 		case 'controls':
 		case 'controls-playlist':
			$htmlpart = '
			<ul class="jp-controls">';

			if($part == 'controls-playlist') {
				$htmlpart .= '<li><a href="javascript:;" class="jp-previous" tabindex="1">'.gettext('previous').'</a></li>';
			}
			$htmlpart .= '
				<li><a href="javascript:;" class="jp-play" tabindex="1">'.gettext('play').'</a></li>
				<li><a href="javascript:;" class="jp-pause" tabindex="1">'.gettext('pause').'</a></li>';
			if($part == 'controls-playlist') {
				$htmlpart .= '<li><a href="javascript:;" class="jp-next" tabindex="1">'.gettext('next').'</a></li>	';
			}
			$htmlpart .= '
				<li><a href="javascript:;" class="jp-stop" tabindex="1">'.gettext('stop').'</a></li>
				<li><a href="javascript:;" class="jp-mute" tabindex="1" title="'.gettext('mute').'">'.gettext('mute').'</a></li>
				<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="'.gettext('unmute').'">'.gettext('unmute').'</a></li>
				<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="'.gettext('max volume').'">'.gettext('max volume').'</a></li>
		  </ul>';
			break;
		case 'toggles':
		case 'toggles-playlist':
			$htmlpart = '<ul class="jp-toggles">';
			if($mode == 'video') {
				$htmlpart .= '
					<li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="'.gettext('full screen').'">'.gettext('full screen').'</a></li>
					<li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="'.gettext('restore screen').'">'.gettext('restore screen').'</a></li>';
			}

			if($part == 'toggles-playlist') {
				$htmlpart .= '
					<li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="'.gettext('shuffle').'">'.gettext('shuffle').'</a></li>
					<li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="'.gettext('shuffle off').'">'.gettext('shuffle off').'</a></li>
					';
			}
			$htmlpart .= '
			<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">'.gettext('repeat').'</a></li>
			<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">'.gettext('repeat off').'</a></li>
			</ul>';
			break;
		case 'no-solution':
			$htmlpart = '
			<div class="jp-no-solution">
				<span>'.gettext('Update Required').'</span>
				'.gettext('To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.').'
			</div>';
			break;
	  }
		return $htmlpart;
	}


	/**
	 * Returns the height of the player
	 * @param object $image the image for which the width is requested
	 *
	 * @return int
	 */
	function getVideoWidth($image=NULL) {
		if (!is_null($image) && $this->mode == 'audio'&& !getOption('jplayer_poster') && !getOption('jplayer_audioposter')) {
			return 420; //audio default
		}
		return $this->width;
	}

	/**
	 * Returns the width of the player
	 * @param object $image the image for which the height is requested
	 *
	 * @return int
	 */
	function getVideoHeigth($image=NULL) {
		if (!is_null($image) && $this->mode == 'audio' && !getOption('jplayer_poster') && !getOption('jplayer_audioposter')) {
			//return 0;
		}
		return $this->height;
	}

	/**
	 * Sets the properties $mode, $supplied and $supplied_counterparts
	 *
	 */
	function setModeAndSuppliedFormat($ext) {
		switch($ext) {
			case 'm4a':
			case 'mp3':
			case 'fla':
				$this->mode = 'audio';
				switch($ext) {
					case 'm4a':
						$this->supplied = 'm4a';
						break;
					case 'mp3':
						$this->supplied = 'mp3';
						break;
					case 'fla':
						$this->supplied = 'fla';
						break;
				}
				break;
			case 'mp4':
			case 'm4v':
			case 'flv':
				$this->mode = 'video';
				switch($ext) {
					case 'm4v':
					case 'mp4':
						$this->supplied = 'm4v';
						break;
					case 'flv':
						$this->supplied = 'flv';
						break;
				}
				break;
		}
	}

	/** TODO: Could not get this to work with Firefox. Low priority so postponed for sometime later...
	 * Gets the mp3, m4v,m4a,mp4 counterpart formats (webm,ogg) for html5 browser compatibilty
	 * NOTE: THese formats need to be uploaded via FTP as they are not valid file types for Zenphoto to avoid confusion
	 *
	 * @param string $moviepath full link to the multimedia file to get counterpart formats to.
	 * @param string $ext the file format extention to search the counterpart for (as we already have fetched that)
	 */

	function getCounterpartFiles($moviepath,$ext) {
		$counterparts = '';
		switch($ext) {
			case 'mp3':
			case 'm4a':
			case 'fla':
				$suffixes = array('webma','oga');
				break;
			case 'mp4':
			case 'm4v':
			case 'flv':
				$suffixes = array('webmv','ogv');
				break;
			default:
				$suffixes = array();
				break;
		}
		foreach($suffixes as $suffix) {
			$filesuffix = $suffix;
			if($suffix == 'oga') {
				$filesuffix = 'ogg';
			}
			$counterpart = str_replace($ext,$filesuffix,$moviepath,$count);
			//$suffix = str_replace('.','',$suffix);
			if(file_exists(str_replace(WEBPATH,SERVERPATH,$counterpart))) {
				$this->supplied_counterparts .= $suffix.',';
				$counterparts .= ','.$suffix.':"'.pathurlencode($counterpart).'"';
			}
		}
		return $counterparts;
	}

	/**
	 * Prints a playlist using jPlayer. Several playlists per page supported.
	 *
 	 * The playlist is meant to replace the 'next_image()' loop on a theme's album.php.
 	 * It can be used with a special 'album theme' that can be assigned to media albums with with .flv/.mp4/.mp3s, although Flowplayer 3 also supports images
   * Replace the entire 'next_image()' loop on album.php with this:
   * <?php printjPlayerPlaylist("playlist"); ?> or <?php printjPlayerPlaylist("playlist-audio"); ?>
	 *
	 * @param string $option "playlist" use for pure video and mixed video/audio playlists or if you want to show the poster/videothumb with audio only playlists,
	 *											 "playlist-audio" use for pure audio playlists (m4a,mp3,fla supported only) if you don't need the poster/videothumb to be shown only.
	 * @param string $albumfolder album name to get a playlist from directly
	 */
	function printjPlayerPlaylist($option="playlist",$albumfolder="") {
		global $_zp_gallery,$_zp_current_album, $_zp_current_image;
		if(empty($albumfolder)) {
			$albumobj = $_zp_current_album;
		} else {
			$albumobj = new Album(NULL,$albumfolder);
		}
		$numimages = $albumobj->getNumImages();
		if ($numimages != 0) {
			$entries = $albumobj->getImages(0);
			if($option == 'playlist' || $option == 'playlist-audio') {
				?>
				<script type="text/javascript">
				//<![CDATA[
				$(document).ready(function(){
					new jPlayerPlaylist({
						jPlayer: "#jquery_jplayer_<?php echo $albumobj->getID(); ?>",
						cssSelectorAncestor: "#jp_container_<?php echo $albumobj->getID(); ?>"
					}, [
					<?php
						$count = '';
						$number = '';
						foreach($entries as $entry) {
							$count++;
							$ext = getSuffix($entry);
							switch($option) {
								case 'playlist':
									$suffixes = array('m4a','m4v','mp3','mp4','flv','fla');
									break;
								case 'playlist-audio':
									$suffixes = array('m4a','mp3','fla');
									break;
							}
							$numbering = '';
							if(in_array($ext,$suffixes)) {
								$number++;
								if(getOption('jplayer_playlist_numbered')) {
									//if($number < 10) {
										$numbering = '<span>'.$number.'</span>';
									//}
								}
								$video = new Video($albumobj,$entry);
								$videoThumb = '';
								$this->setModeAndSuppliedFormat($ext);
								if($option == 'playlist' && getOption('jplayer_poster')) {
									$albumfolder = $albumobj->name;
									$videoThumb = ',poster:"'.html_encode($video->getCustomImage(null, $this->width, $this->height, $this->width, $this->height, null, null, true)).'"';
								}
								$playtime = '';
								if(getOption('jplayer_playlist_playtime')) {
									if(!empty($playtime)) {
										$playtime = ' ('.$video->get('VideoPlaytime').')';
									}
								}
								?>
								{
									title:"<?php echo $numbering.html_encode($video->getTitle()).$playtime; ?>",
									<?php	if(getOption('jplayer_download')) { ?>
										free:true,
									<?php } ?>
									<?php echo $this->supplied; ?>:"<?php echo pathurlencode($video->getFullImageURL()); ?>"
									<?php echo $this->getCounterpartFiles($video->getFullImageURL(),$ext); ?>
									<?php echo $videoThumb; ?>
								}
								<?php
								if($numimages != $count) {
									echo ',';
								}
							} // if video
						} // foreach
						// for some reason the playlist must run with supplied: "flash,html" because otherwise neither videothumbs(poster) nor flv/flv work on Safari 4.1.
						// Seems the flash fallback fails here
					?>
					], {
						swfPath: "<?php echo WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER; ?>/jplayer/js",
						solution: "flash,html",
					<?php if($option == 'playlist') { ?>
						supplied: "<?php echo $this->supplied_counterparts; ?>m4v, mp4, m4a, mp3, fla, flv"
					<?php } else { ?>
						supplied: "<?php echo $this->supplied_counterparts; ?>m4a, mp3, fla"
					<?php }
					if($option != 'playlist-audio') { ?>
						,	size: {
						width: "<?php echo $this->width; ?>px",
						height: "<?php echo $this->height; ?>px",
						cssClass: "<?php echo $this->playersize; ?>"
						}
					<?php } ?>
					});
				});
			//]]>
			</script>
			<?php
			if($option == 'playlist') {
			?>
			<div id="jp_container_<?php echo $albumobj->getID(); ?>" class="jp-video <?php echo $this->playersize; ?>">
			<div class="jp-type-playlist">
				<div id="jquery_jplayer_<?php echo $albumobj->getID(); ?>" class="jp-jplayer"></div>
				<div class="jp-gui">
					<div class="jp-video-play">
						<a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
					</div>
					<div class="jp-interface">
						<div class="jp-progress">
							<div class="jp-seek-bar">
								<div class="jp-play-bar"></div>
							</div>
						</div>
						<div class="jp-current-time"></div>
						<div class="jp-duration"></div>
						<div class="jp-controls-holder">
							<?php echo $this->getPlayerHTMLparts('video','controls-playlist');?>
							<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>
							<?php echo $this->getPlayerHTMLparts('video','toggles-playlist');	?>
						</div>
						<div class="jp-title">
							<ul>
								<li></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="jp-playlist">
					<ul>
						<!-- The method Playlist.displayPlaylist() uses this unordered list -->
						<li></li>
					</ul>
				</div>
				<?php echo $this->getPlayerHTMLparts('video','no-solution');	?>
			</div>
		</div>
			<?php
			} else { // playlist-audio
			?>
			<div id="jquery_jplayer_<?php echo $albumobj->getID(); ?>" class="jp-jplayer"></div>
		<div id="jp_container_<?php echo $albumobj->getID(); ?>" class="jp-audio">
			<div class="jp-type-playlist">
				<div class="jp-gui jp-interface">
					<?php echo $this->getPlayerHTMLparts('audio','controls-playlist');	?>
					<div class="jp-progress">
						<div class="jp-seek-bar">
							<div class="jp-play-bar"></div>
						</div>
					</div>
					<div class="jp-volume-bar">
						<div class="jp-volume-bar-value"></div>
					</div>
					<div class="jp-time-holder">
						<div class="jp-current-time"></div>
						<div class="jp-duration"></div>
					</div>
					<?php echo $this->getPlayerHTMLparts('audio','toggles-playlist');	?>
				</div>
				<div class="jp-playlist">
					<ul>
						<li></li>
					</ul>
				</div>
				<?php echo $this->getPlayerHTMLparts('audio','no-solution'); ?>
			</div>
		</div>

			<?php
			} // if else playlist
		 } // if $option end
		} // if no images at all end
	} // function playlist


} // jplayer class


// theme function wrapper for user convenience
	function printjPlayerPlaylist($option="playlist",$albumfolder="") {
		global $_zp_flash_player;
		$_zp_flash_player->printjPlayerPlaylist($option,$albumfolder);
	}