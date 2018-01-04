<?php ob_start(); ?>

		<link rel="stylesheet" href="/<?php echo HOST_SUB_DIR; ?>nstyles/<?php echo Registry::Get("Styling.Preset") ?>.css" />
		<link rel="stylesheet" href="/<?php echo HOST_SUB_DIR; ?>nstyles/jquery.simple-dtpicker.css" />
		<link rel="stylesheet" href="/<?php echo HOST_SUB_DIR; ?>nstyles/multirange.css" />
		
		<?php if (UtilCollection::GetBrowser()['browser'] == 'Google Chrome') { ?>
		<link rel="shortcut icon" href="/<?php echo HOST_SUB_DIR; ?>favicon.ico" />
		<?php } else { ?>
		<link rel="shortcut icon" href="/filedownloader/<?php echo Registry::Get('Website.Logo'); ?>?ajax=true" />
		<?php } ?>

		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/jquery.min.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/misc.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/GUI.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/AJAX.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/templates.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/jquery.autocomplete.min.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/callbacks.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/garlic.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/jquery.simple-dtpicker.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/multirange.js"></script>

		<script src="//connect.facebook.net/en_US/sdk.js#xfbml=1" id="facebook-jssdk"></script>

		<!-- FaceBook Open Graph tags -->
		<meta property="og:url" content="" id="website_title_url" />
		<meta property="og:title" content="" id="website_title_meta" />
		<meta property="og:type" content="website" />
		<meta property="og:description" content="An Event Management site for Civil Air Patrol units" />
		<meta property="og:image" content="https://md089.capunit.com/images/banner.jpg" />
		
<?php define ("HEAD_HTML", ob_get_clean()); ?>
