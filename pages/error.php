<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (isset($e['uri'][0]) && $e['uri'][0] == 'source') {
				return highlight_file(__FILE__);
			}
			return [1][1];
		}
	}