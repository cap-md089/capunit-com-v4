<?php
	class Output {
		public static function doPut ($e, $c, $l, $m, $a) {
			$mem = Member::Estimate($e['raw']['data']);
			$ret = [];
			if($mem) {
				$ret['phone'] = $mem->getBestPhone();
				if (!$ret['phone']) $ret['phone'] = '';
				$ret['email'] = $mem->getBestEmail();
				if (!$ret['email']) $ret['email'] = '';
			} else {
				$ret['phone'] = '';
				$ret['email'] = '';
			}
			$html = json_encode($ret);
			return $html;
		}
	}