<?php
/**
 * Support functions for "statistics" about images and albums.
 *
 * Supports such statistics as "most popular", "latest", "top rated", etc.
 *
 * <b>CAUTION:</b> The usage to get an specific album has changed. You now have to pass the foldername of an album instead the album title.
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_description = gettext("Functions that provide various statistics about images and albums in the gallery.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";


/**
 *
 * used to get a list of albums to be further processed
 * @param object $obj from whence to get the albums
 * @param array $albumlist collects the list
 */
function getImageAlbumAlbumList($obj, &$albumlist) {
	global $_zp_gallery;
	$hint = $show = false;
	$locallist = $obj->getAlbums();
	foreach ($locallist as $folder) {
		$album = new Album(NULL, $folder);
		If (!$album->isDynamic() && $album->checkAccess($hint, $show))  {
			$albumlist[] = $album->getID();
			getImageAlbumAlbumList($album, $albumlist);
		}
	}
}

/**
 * Returns a list of album statistic accordingly to $option
 *
 * @param int $number the number of albums to get
 * @param string $option
 *		"popular" for the most popular albums,
 *		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 *		"toprated" for the best voted
 *		"latestupdated" for the latest updated
 *		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder The name of an album to get only the statistc for its subalbums
 * @return string
 */
function getAlbumStatistic($number=5, $option, $albumfolder='') {
	global $_zp_gallery;
	$albumlist = array();
	if ($albumfolder) {
		$obj = new Album(NULL, $albumfolder);
		$albumlist[] = $obj->getID();
	} else {
		$obj = $_zp_gallery;
	}
	getImageAlbumAlbumList($obj, $albumlist);
	$albumWhere = '';
	if(!empty($albumlist)) {
		$albumWhere = ' WHERE (`id`='.implode(' OR `id`=', $albumlist).')';
	}
	switch($option) {
		case "popular":
			$sortorder = "hitcounter";
			break;
		case "latest":
			$sortorder = "id";
			break;
		case "latest-mtime":
			$sortorder = "images.mtime";
			break;
		case "latest-date":
			$sortorder = "date";
			break;
		case "latest-publishdate":
			$sortorder = "IFNULL(publishdate,date)";
			break;
		case "mostrated":
			$sortorder = "total_votes";
			break;
		case "toprated":
			$sortorder = "(total_value/total_votes)";
			break;
		case "latestupdated":
			$sortorder = 'updateddate';
			break;
		case "random":
			$sortorder = "RAND()"; 
			break;
	}
	$albums = query_full_array("SELECT id, title, folder, thumb FROM " . prefix('albums') . $albumWhere . " ORDER BY ".$sortorder." DESC LIMIT ".$number);
	return $albums;
}

/**
 * Prints album statistic according to $option as an unordered HTML list
 * A css id is attached by default named '$option_album'
 *
 * @param string $number the number of albums to get
 * @param string $option
 *		"popular" for the most popular albums,
 *		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 *		"toprated" for the best voted
 *		"latestupdated" for the latest updated
 *		"random" for random order (yes, strictly no statistical order...)
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 */
function printAlbumStatistic($number, $option, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$albumfolder='',$firstimglink=false) {
	$albums = getAlbumStatistic($number, $option,$albumfolder);
	echo "\n<div id=\"".$option."_album\">\n";
	echo "<ul>";
	foreach($albums as $album) {
		printAlbumStatisticItem($album, $option,$showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$firstimglink);
	}
	echo "</ul></div>\n";
}

/**
 * A helper function that only prints a item of the loop within printAlbumStatistic()
 * Not for standalone use.
 *
 * @param array $album the array that getAlbumsStatistic() submitted
 * @param string $option
 *		"popular" for the most popular albums,
 *		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 *		"toprated" for the best voted
 *		"latestupdated" for the latest updated
 *		"random" for random order (yes, strictly no statistical order...)
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 */
function printAlbumStatisticItem($album, $option, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$firstimglink=false) {
	global $_zp_gallery;
	$twidth = $width;
	$theight = $height;
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width)) $width = 85;
		if (is_null($height)) $height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	$tempalbum = new Album(NULL, $album['folder']);
	if($firstimglink && $tempalbum->getNumImages() != 0) {
		$firstimage = $tempalbum->getImages(1); // need only the first so don't get all
		$firstimage = $firstimage[0];
		$modrewritesuffix = getOption('mod_rewrite_image_suffix');
		$imagepath = html_encode(rewrite_path("/".$firstimage.$modrewritesuffix,"&amp;image=".$firstimage,false));
	} else {
		$imagepath = "";
	}
	$albumpath = html_encode(rewrite_path("/".pathurlencode($tempalbum->name).$imagepath, "index.php?album=".pathurlencode($tempalbum->name).$imagepath));
	echo "<li><a href=\"".$albumpath."\" title=\"" . html_encode($tempalbum->getTitle()) . "\">\n";
	$albumthumb = $tempalbum->getAlbumThumbImage();
	$thumb = newImage($tempalbum, $albumthumb->filename);
	switch ($crop) {
		case 0:
			echo "<img src=\"".pathurlencode($albumthumb->getCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, TRUE))."\" alt=\"" . html_encode($albumthumb->getTitle()) . "\" /></a>\n<br />";
			break;
		case 1;
			echo "<img src=\"".pathurlencode($albumthumb->getCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, TRUE))."\" alt=\"" . html_encode($albumthumb->getTitle()) . "\" /></a>\n<br />";
			break;
		case 2:
			echo "<img src=\"".pathurlencode($albumthumb->getThumb())."\" alt=\"" . html_encode($albumthumb->getTitle()) . "\" /></a>\n<br />";
			break;
	}
	if($showtitle) {
		echo "<h3><a href=\"".$albumpath."\" title=\"" . html_encode($tempalbum->getTitle()) . "\">\n";
		echo $tempalbum->getTitle()."</a></h3>\n";
	}
	if($showdate) {
		if($option === "latestupdated") {
			$filechangedate = filectime(ALBUM_FOLDER_SERVERPATH.internalToFilesystem($tempalbum->name));
			$latestimage = query_single_row("SELECT mtime FROM " . prefix('images'). " WHERE albumid = ".$tempalbum->getID() . " AND `show` = 1 ORDER BY id DESC");
			$count = db_count('images',"WHERE albumid = ".$tempalbum->getID() . " AND mtime = ". $latestimage['mtime']);
			echo "<p>".sprintf(gettext("Last update: %s"),zpFormattedDate(DATE_FORMAT,$filechangedate))."</p>";
			if($count <= 1) {
				$image = gettext("image");
			} else {
				$image = gettext("images");
			}
			echo "<span>".sprintf(gettext('%1$u new %2$s'),$count,$image)."</span>";
		} else {
			echo "<p>". zpFormattedDate(DATE_FORMAT,strtotime($tempalbum->getDateTime()))."</p>";
		}
	}
	if($showstatistic === "rating" OR $showstatistic === "rating+hitcounter") {
		$votes = $tempalbum->get("total_votes");
		$value = $tempalbum->get("total_value");
		if($votes != 0) {
			$rating =  round($value/$votes, 1);
		}
		echo "<p>".sprintf(gettext('Rating: %1$u (Votes: %2$u)'),$rating,$tempalbum->get("total_votes"))."</p>";
	}
	if($showstatistic === "hitcounter" OR $showstatistic === "rating+hitcounter") {
		$hitcounter = $tempalbum->get("hitcounter");
		if(empty($hitcounter)) { $hitcounter = "0"; }
		echo "<p>".sprintf(gettext("Views: %u"),$hitcounter)."</p>";
	}
	if($showdesc) {
		echo shortenContent($tempalbum->getDesc(), $desclength,' (...)');
	}
	echo "</li>";
}

/**
 * Prints the most popular albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 */
function printPopularAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='hitcounter',$width=NULL,$height=NULL,$crop=NULL,$albumfolder='',$firstimglink=false) {
	printAlbumStatistic($number,"popular",$showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$albumfolder,$firstimglink);
}

/**
 * Prints the latest albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 */
function printLatestAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$albumfolder='',$firstimglink=false) {
	printAlbumStatistic($number,"latest",$showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$albumfolder,$firstimglink);
}

/**
 * Prints the most rated albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 */
function printMostRatedAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$albumfolder='',$firstimglink=false) {
	printAlbumStatistic($number,"mostrated",$showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$albumfolder,$firstimglink);
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 */
function printTopRatedAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$albumfolder='',$firstimglink=false) {
	printAlbumStatistic($number,"toprated",$showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$albumfolder,$firstimglink);
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 */
function printLatestUpdatedAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$albumfolder='',$firstimglink=false) {
	printAlbumStatistic($number,"latestupdated",$showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$albumfolder,$firstimglink);
}

/**
 * Returns a list of image statistic according to $option
 *
 * @param string $number the number of images to get
 * @param string $option "popular" for the most popular images,
 *		"popular" for the most popular albums,
 *		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 *		"toprated" for the best voted
 *		"latestupdated" for the latest updated
 *		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder foldername of an specific album
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @return string
 */
function getImageStatistic($number, $option, $albumfolder='',$collection=false) {
	global $_zp_gallery;
	$albumlist = array();
	if ($albumfolder) {
		$obj = new Album(NULL, $albumfolder);
		$albumlist[] = $obj->getID();
	} else {
		$obj = $_zp_gallery;
	}
	getImageAlbumAlbumList($obj, $albumlist);
	if (empty($albumlist)) {
		return array();
	}
	$albumWhere = ' AND (albums.`id`='.implode(' OR albums.`id`=', $albumlist).')';
	switch ($option) {
		case "popular":
			$sortorder = "images.hitcounter";
			break;
		case "latest-date":
			$sortorder = "images.date";
			break;
		case "latest-mtime":
			$sortorder = "images.mtime";
			break;
		case "latest":
			$sortorder = "images.id";
			break;
		case "latest-publishdate":
			$sortorder = "IFNULL(images.publishdate,images.date)";
			break;
		case "mostrated":
			$sortorder = "images.total_votes";
			break;
		case "toprated":
			$sortorder = "(images.total_value/images.total_votes)";
			break;
		case "random":
			$sortorder = "RAND()";
			break;
		default:
			$sortorder = 'id';
			break;
	}
	$imageArray = array();
	$hint = $show = NULL;
	if(!empty($albumfolder) && $obj->isDynamic()) {
		$sorttype = str_replace('images.','',$sortorder);
		$images = $obj->getImages(0,0,$sorttype,'DESC');
		foreach ($images as $image) {
			$image = newImage($obj, $image);
			if ($image->checkAccess($hint, $show)) {
				$imageArray[] = $image;
				if (count($imageArray) >= $number) {	// got enough
					break;
				}
			}
		}
	} else {
		$result = query("SELECT images.albumid, images.filename AS filename, images.mtime as mtime, images.title AS title, " .
															"albums.folder AS folder, images.show, albums.show, albums.password FROM " .
															prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
															"WHERE (images.albumid = albums.id) " . $albumWhere .
															" ORDER BY ".$sortorder." DESC");
		while ($row = db_fetch_assoc($result)) {
			$image = newImage(NULL, $row);
			if ($image && $image->checkAccess($hint, $show)) {
				$imageArray[] = $image;
				if (count($imageArray) >= $number) {	// got enough
					break;
				}
			}
		}
	}
	return $imageArray;
}

/**
 * Prints image statistic according to $option as an unordered HTML list
 * A css id is attached by default named accordingly'$option'
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular images,
 *		"popular" for the most popular albums,
 *		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 *		"toprated" for the best voted
 *		"latestupdated" for the latest updated
 *		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder foldername of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic "hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 * @return string
 */
function printImageStatistic($number, $option, $albumfolder='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	$images = getImageStatistic($number, $option, $albumfolder,$collection);
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width)) $width = 85;
		if (is_null($height)) $height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	echo "\n<div id=\"$option\">\n";
	echo "<ul>";
	foreach ($images as $image) {
		if($fullimagelink) {
			$imagelink = $image->getFullImageURL();
		} else {
			$imagelink = $image->getImageLink();
		}
		echo "<li><a href=\"" . html_encode($imagelink)."\" title=\"" . html_encode($image->getTitle()) . "\">\n";
		switch ($crop) {
			case 0:
				echo "<img src=\"".pathurlencode($image->getCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, TRUE))."\" alt=\"" . html_encode($image->getTitle()) . "\" /></a>\n";
				break;
			case 1:
				echo "<img src=\"".pathurlencode($image->getCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, TRUE))."\" alt=\"" . html_encode($image->getTitle()) . "\" /></a>\n";
				break;
			case 2:
				echo "<img src=\"".pathurlencode($image->getThumb())."\" alt=\"" . html_encode($image->getTitle()) . "\" /></a>\n<br />";
				break;
		}
		if($showtitle) {
			echo "<h3><a href=\"".pathurlencode($image->getImageLink())."\" title=\"" . html_encode($image->getTitle()) . "\">\n";
			echo $image->getTitle()."</a></h3>\n";
		}
		if($showdate) {
			echo "<p>". zpFormattedDate(DATE_FORMAT,strtotime($image->getDateTime()))."</p>";
		}
		if($showstatistic === "rating" OR $showstatistic === "rating+hitcounter") {
			$votes = $image->get("total_votes");
			$value = $image->get("total_value");
			if($votes != 0) {
				$rating =  round($value/$votes, 1);
			}
			echo "<p>".sprintf(gettext('Rating: %1$u (Votes: %2$u)'),$rating,$votes)."</p>";
		}
		if($showstatistic === "hitcounter" OR $showstatistic === "rating+hitcounter") {
			$hitcounter = $image->get("hitcounter");
			if(empty($hitcounter)) { $hitcounter = "0"; }
			echo "<p>".sprintf(gettext("Views: %u"),$hitcounter)."</p>";
		}
		if($showdesc) {
			echo shortenContent($image->getDesc(), $desclength,' (...)');
		}
		echo "</li>";
	}
	echo "</ul></div>\n";
}

/**
 * Prints the most popular images
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printPopularImages($number=5, $albumfolder='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	printImageStatistic($number, "popular",$albumfolder, $showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$collection,$fullimagelink);
}

/**
 * Prints the n top rated images
 *
 * @param int $number The number if images desired
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printTopRatedImages($number=5, $albumfolder="", $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	printImageStatistic($number, "toprated",$albumfolder, $showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$collection,$fullimagelink);
}

/**
 * Prints the n most rated images
 *
 * @param int $number The number if images desired
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printMostRatedImages($number=5, $albumfolder='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	printImageStatistic($number, "mostrated", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic,$width,$height,$crop,$collection,$fullimagelink);
}

/**
 * Prints the latest images by ID (the order zenphoto recognized the images on the filesystem)
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printLatestImages($number=5, $albumfolder='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40, $showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	printImageStatistic($number, "latest", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic,$width,$height,$crop,$collection,$fullimagelink);
}

/**
 * Prints the latest images by date order (date taken order)
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printLatestImagesByDate($number=5, $albumfolder='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	printImageStatistic($number, "latest-date", $albumfolder, $showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$collection,$fullimagelink);
}

/**
 * Prints the latest images by mtime order (date uploaded order)
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 *		"hitcounter" for showing the hitcounter (views),
 *		"rating" for rating,
 *	"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printLatestImagesByMtime($number=5, $albumfolder='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40,$showstatistic='',$width=NULL,$height=NULL,$crop=NULL,$collection=false,$fullimagelink=false) {
	printImageStatistic($number, "latest-mtime", $albumfolder, $showtitle, $showdate, $showdesc, $desclength,$showstatistic,$width,$height,$crop,$collection,$fullimagelink);
}

/**
 * A little helper function that checks if an image or album is to be considered 'new' within the time range set in relation to getImageDate()/getAlbumDate()
 * Returns true or false.
 *
 * @param string $mode What to check "image" or "album".
 * @param integer $timerange The time range the item should be considered new. Default is 604800 (unix time seconds = ca. 7 days)
 * @return bool
 */
function checkIfNew($mode="image",$timerange=604800) {
	$currentdate = date("U");
	switch($mode) {
		case "image":
			$itemdate = getImageDate("%s");
			break;
		case "album":
			$itemdate = getAlbumDate("%s");
			break;
	}
	$newcheck = $currentdate - $itemdate;
	if($newcheck < $timerange) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Gets the number of all subalbums of all subalbum levels of either the current album or $albumobj
 *
 * @param object $albumobj Optional album object to check
 * @param string $pre Optional text you want to print before the number
 * @return bool
 */
function getNumAllSubalbums($albumobj,$pre='') {
	global $_zp_gallery, $_zp_current_album;
	if(is_null($albumobj)) {
		$albumobj = $_zp_current_album;
	}
	$count = '';
	$albums = getAllAlbums($_zp_current_album);
	if(count($albums) != 0) {
		$count = '';
		foreach ($albums as $album) {
			$count++;
		}
		return $pre.$count;
	} else {
		return false;
	}
}
?>