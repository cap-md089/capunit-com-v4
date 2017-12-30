<?php global $_ACCOUNT; ob_start(); ?>

		<link rel="stylesheet" href="/<?php echo HOST_SUB_DIR; ?>nstyles/<?php echo Registry::Get("Styling.Preset") ?>.css" />
		<link rel="stylesheet" href="/<?php echo HOST_SUB_DIR; ?>nstyles/jquery.simple-dtpicker.css" />
		<link rel="stylesheet" href="/<?php echo HOST_SUB_DIR; ?>nstyles/multirange.css" />
		
		<?php if (UtilCollection::GetBrowser()['browser'] == 'Google Chrome') { ?>
		<link rel="shortcut icon" href="/<?php echo HOST_SUB_DIR; ?>favicon.ico" />
		<?php } else { ?>
		<link rel="shortcut icon" href="/filedownloader/<?php echo Registry::Get('Website.Logo'); ?>?ajax=true" />
		<?php } ?>

		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/jquery.min.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/GUI.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/AJAX.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/templates.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/jquery.autocomplete.min.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/callbacks.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/garlic.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/jquery.simple-dtpicker.js"></script>
		<script src="/<?php echo HOST_SUB_DIR; ?>scripts/<?php echo Registry::get('Styling.Preset'); ?>/multirange.js"></script>

		<!-- <script src="//connect.facebook.net/en_US/sdk.js#xfbml=1" id="facebook-jssdk"></script> -->

		<!-- FaceBook/Twitter Open Graph tags -->
		<meta property="og:type" content="website" />
		<meta property="og:url" content="https://<?php echo $_ACCOUNT->id; ?>.capunit.com/" id="website_title_url" />
		<meta property="og:title" content="<?php echo Registry::get('Website.Name'); ?>" id="website_title_meta" />
		<meta property="og:description" content="An Event Management site for Civil Air Patrol units" class="descriptions" />
		<meta property="og:image:url" content="http://<?php echo $_ACCOUNT->id; ?>.capunit.com/images/banner.jpg" />
		<meta property="og:image:secure_url" content="https://<?php echo $_ACCOUNT->id; ?>.capunit.com/images/banner.jpg" />

		<!-- Client metadata -->
		<meta name="description" content="" class="descriptions" />
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=400, initial-scale=1" />

		<meta name="ROBOTS" content="INDEX, FOLLOW" />
		
<?php define ("HEAD_HTML", ob_get_clean()); ?>
