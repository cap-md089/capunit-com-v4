<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$html = <<<HTM
<script>
	mobile = true;
	initializeMobile();
</script>
HTM;
			return $html;
		}
	}