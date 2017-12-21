<?php ob_start(); ?>
<?php
	$con = Registry::Get("Contact");
	$add = $con->MeetingAddress;
	$add2 = $con->MailingAddress;
	$footer = Registry::Get("Footer");
	$counts = [
		2 => 'half',
		3 => 'third',
		4 => 'fourth'
	];
	$count = (1 + ($add2->Name != '' ? 1 : 0) + ($add->Name != '' ? 1 : 0) + 1);
?>
<div id="footer">
	<div class="page">
		<div class="<?php echo $counts[$count]; ?>Box">
			<div class="footerBoxTitle">Connect With Us</div>
			<p>
				<?php
					if ($con->FaceBook != '') {
						echo "<a target=\"_blank\" href=\"https://www.facebook.com/$con->FaceBook\" class=\"socialMedia fb\"></a>";
					}
					if ($con->Twitter != '') {
						echo "<a target=\"_blank\" href=\"https://www.twitter.com/$con->Twitter\" class=\"socialMedia twitter\"></a>";
					}
					if ($con->YouTube != '') {
						echo "<a target=\"_blank\" href=\"https://www.youtube.com/channel/$con->YouTube\" class=\"socialMedia youtube\"></a>";
					}
					if ($con->LinkedIn != '') {
						echo "<a target=\"_blank\" href=\"https://in.linkedin.com/in/$con->LinkedIn\" class=\"socialMedia linkedin\"></a>";
					}
					if ($con->Instagram != '') {
						echo "<a target=\"_blank\" href=\"https://www.instagram.com/$con->Instagram\" class=\"socialMedia instagram\"></a>";
					}
					if ($con->Flickr != '') {
						echo "<a target=\"_blank\" href=\"https://www.flickr.com/photos/$con->Flickr\" class=\"socialMedia flickr\"></a>";
					}
				?>
			</p>
		</div>
		<?php if ($add2->Name != '') { ?>
		<div class="<?php echo $counts[$count]; ?>Box">
			<div class="footerBoxTitle">Mailing Address</div>
			<p><?php
				echo "$add2->Name<br />";
				echo "$add2->FirstLine<br />";
				echo "$add2->SecondLine<br />";
			?></p>
		</div>
		<?php } if ($add->Name != '') { ?>
		<div class="<?php echo $counts[$count]; ?>Box">
			<div class="footerBoxTitle">Meeting Address</div>
			<p><?php
				echo "$add->Name<br />";
				echo "$add->FirstLine<br />";
				echo "$add->SecondLine<br />";
			?></p>
		</div>
		<?php } ?>
		<div class="<?php echo $counts[$count]; ?>Box">
			<div class="footerBoxTitle">Resources</div>
			<ul style="list-style-type: none;padding:0;margin:0">
				<li><a href="https://www.capnhq.gov/">eServices</a></li>
				<li><a href="http://www.cap.news/">Latest CAP News</a></li>
			</li>
		</div>
		<div style="color: white;" class="onlyBox">
			<div style="float: left;font-size: 12px">&copy; <?php echo date('Y').' '.Registry::Get("Website.Name"); ?></div>
			<div style="float: right; font-size: 12px"><a href="http://www.capmembers.com/">CAP Members.com</a> | <a href="http://www.cap.news/">CAP News</a> | <a href="#" onclick="$('html').animate({scrollTop:0},'slow');return false;">Top</a></div>
		</div>
	</div>
</div>
<?php define ("FOOTER_HTML", ob_get_contents()); ob_end_clean(); ?>
