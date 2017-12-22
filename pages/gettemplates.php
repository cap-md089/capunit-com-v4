<?php
	define ("USER_REQUIRED", false);
	
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if ($e['uri'][$e['uribase-index']] == 'body') {
				include_once BASE_DIR . "templates/".Registry::get('Styling.Preset')."/footer.php";
				include_once BASE_DIR . "templates/".Registry::get('Styling.Preset')."/body.php";
				include_once BASE_DIR . "templates/".Registry::get('Styling.Preset')."/header.php";
				return [
					'headers' => [
						'Cache-control' => 'public, max-age=31536000'
					],
					'body' => HEADER_HTML . BODY_HTML . FOOTER_HTML
				];
			} elseif ($e['uri'][$e['uribase-index']] == 'head') {
				include_once BASE_DIR . "templates/".Registry::get('Styling.Preset')."/head.php";
				return [
					'headers' => [
						'Cache-control' => 'public, max-age=31536000'
					],
					'body' => HEAD_HTML
				];
			} else {
				return '';
			}
		}
	}