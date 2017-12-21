<?php
	require_once "config.php";
	require_once BASE_DIR . "lib/templates.php";
	ob_start();
?>
<div id="bodyContainer">
	<div id="page">
		<header>
			<div id="logo">
				<a>
					<img src="/images/logo.png" alt="Civil Air Patrol" height="127" />
				</a>
			</div>
			<div class="headerDivider"></div>
			<div class="pagetitle"><?php echo Registry::get('Website.Name'); ?></div>
			<div class="servings">
				<span class="servingsTitle">Citizens Serving<br />Communities</span>
			</div>
			<nav id="mainNavigation">
				<ul>
					<li>
						<?php echo new Link('main', 'Home', null, null, 'main'); ?>
					</li>
					<li>
						<?php echo new Link('blog', 'News', null, null, 'blog'); ?>
					</li>
					<li>
						<?php echo new Link('calendar', 'Calendar', null, null, 'calendar'); ?>
					</li>
					<li>
						<?php echo new Link("photolibrary", "Photo Library", null, null, 'photolibrary'); ?>
					</li>
				</ul>
				<div class="search">
					<div id="output" style="margin:0"></div>
					<form id="search" action="/main" method="POST"
						data-form-reload="false" data-form-beforesend="" onsubmit="return handleFormSubmit(this, false);">
						<div role="search">
							<input class="searchInput" name="search" placeholder="Search..."
								type="text" class="searchInput" aria-label="Search through site content"/>
							<input class="search-btn submitBt" aria-label="Search" value="" type="submit">
						</div>
					</form>
				</div>
			</nav>
		</header>
<?php define ("HEADER_HTML", ob_get_clean()); ?>
