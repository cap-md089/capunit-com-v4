<?php
	define ("USER_REQUIRED", false);
	
	class Output {
		public static function doGet ($e, $c, $l, $m) {
			$text = <<<EOD

	I'm a little teapot, short and stout!<br />
				<br />
				This page is returned with an <a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#4xx_Client_Error">HTTP response code 418</a>,
				AKA "I'm a little teapot". You can check if you open the developer tools for your browser, go to network, and refresh the page<br />
				<br />
				On Firefox or Chrome, press Cmd-Opt-I or Ctrl-Opt-I and press the network tab<br />
				If you are a web developer and truly curious about this you would be using
				either one of those browsers, Safari and Opera cause enough headaches (Don't
				get me started on Internet Explorer and Microsoft Edge!!)
EOD;
			header("HTTP/1.1 418 I'm a teapot");
			return [
				'body' => $text,
				'title' => 'Teapot'
			];
		}
	}
?>