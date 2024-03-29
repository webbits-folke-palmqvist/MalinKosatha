<?php

// force UTF-8 Ø

if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light');
?>
<!DOCTYPE html>
<html>
<head>
	<?php zp_apply_filter('theme_head'); ?>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo LOCAL_CHARSET; ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" />
	<?php jqm_loadScripts(); ?>
</head>

<body>
<?php zp_apply_filter('theme_body_open'); ?>


<div data-role="page" id="mainpage">

  <?php jqm_printMainHeaderNav(); ?>

	<div data-role="content">
		<div class="content-primary">
		<h2 class="breadcrumb"><a href="<?php echo getGalleryIndexURL(); ?>"><?php echo gettext('Gallery'); ?></a> <?php printParentBreadcrumb('','',''); ?> <?php printAlbumTitle();?></h2>
		<?php printAlbumDesc(); ?>
		<?php if(hasPrevPage() || hasNextPage()) printPageListWithNav(gettext("prev"), gettext("next"),false,true,'pagelist',NULL,true,7); ?>
		<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="b">
			<?php while (next_album()): ?>
			<li>
			<a href="<?php echo html_encode(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?>">
			<?php printCustomAlbumThumbImage(getAnnotatedAlbumTitle(), null, 79, 79, 79, 79, NULL, null, NULL,NULL); ?>
			<?php printAlbumTitle(); ?><small> (<?php printAlbumDate(''); ?>)</small>
			<div class="albumdesc"><?php echo shortenContent(getAlbumDesc(), 100,'(...)',false); ?></div>
			<small class="ui-li-count"><?php jqm_printImageAlbumCount()?></small>

			</a>
			</li>
			<?php endwhile; ?>
		</ul>

			<?php while (next_image()): ?>
			<div class="image"><a href="<?php echo html_encode(getImageLinkURL());?>" title="<?php echo getBareImageTitle();?>">
			<?php printCustomSizedImage(getAnnotatedImageTitle(), NULL,79, 79, 79, 79, NULL, NULL, NULL, NULL, true, NULL); ?>
			</a>
			</div>
			<?php endwhile; ?>

		<br clear="all" />
		<?php if(hasPrevPage() || hasNextPage()) printPageListWithNav(gettext("prev"), gettext("next"),false,true,'pagelist',NULL,true,7); ?>
			<?php
		if (function_exists('printCommentForm')) {
		  printCommentForm();
		}	?>
		</div>
		 <div class="content-secondary">
			<?php jqm_printMenusLinks(); ?>
 </div>
	</div><!-- /content -->
<?php jqm_printBacktoTopLink(); ?>
<?php jqm_printFooterNav(); ?>
</div><!-- /page -->

<?php zp_apply_filter('theme_body_close'); ?>

</body>
</html>