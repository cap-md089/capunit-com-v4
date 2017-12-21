<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$html = <<<HTM
<script>
	mobile = false;
	$(".mobile").addClass("desktop").removeClass("mobile");
	$("meta[name=viewport]").attr("content", "width=1200, initial-scale=1");
	getHtml('/');
</script>
HTM;
			return $html;
		}
	}