<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$m->hasPermission("AdministerPT")) {return ['error'=>401];}
			
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return false;
			if (!$m->hasPermission("AdministerPT")) {return ['error'=>401];}

		}
	}