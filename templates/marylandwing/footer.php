<?php ob_start(); ?>
<?php
	$con = Registry::Get("Contact");
	$add = $con->MeetingAddress;
?>
<footer>
	<div id="footer">
		<div class="footerdivider">
			<?php echo $add->Name; ?><br />
			<?php echo $add->FirstLine; ?><br />
			<?php echo $add->SecondLine; ?><br /><br />
			<a href="mailto:<?php echo $con->MainEmail; ?>"><?php echo $con->MainEmailText; ?></a>
		</div>
		<div class="footerdivider">
			<em><strong>Disclaimer:</strong> Links or references to individuals or companies does not constitute an endorsement of any information,
		product, or service you may receive from such sources.</em>
		</div>
	</div>
</footer>
<?php define ("FOOTER_HTML", ob_get_contents()); ob_end_clean(); ?>
