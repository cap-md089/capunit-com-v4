<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$html = <<<HTM
<script>
	mobile = true;
	$(".desktop").addClass("mobile").removeClass("desktop");
	$("meta[name=viewport]").attr("content", "width=450, initial-scale=1");
</script>
HTM;
			return $html;
		}
	}