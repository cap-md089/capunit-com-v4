<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			return "<pre>".$m->uname.": ".print_r($m->dutyPositions, true)."</pre>";
		}
	}