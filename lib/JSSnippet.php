<?php
    /**
     * @package lib/JSSnippet
     *
     * Gives a few bits of JavaScript snippets and helpful functions (such as preperation and minifying)
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */
	class JSSnippet {
		/**
		 * A quick snippet that can be returned to force the page to reload
		 */
		public static $PageReload = "<script>getHtml();</script>";

		/**
		 * Quick bit of HTMl to present a sign in link
		 * 
		 * @param str $text The text to have in the link
		 * 
		 * @return str HTML to use
		 */
		public static function SigninLink ($text) {
			return "<a href=\"#\" class=\"signin_link\">$text</a>";
		}


		/**
		 * Prepares the JS to be run when it is sent to the client
		 *
		 * @param str $js The JavaScript to prepare
		 *
		 * @return str The prepared JavaScript
		 */
		public static function PrepareJS ($js) {
			return "<script>$js</script>";
		}

		/**
		 * A basic minify function, removes all comments, all newlines, all pairs of whitespace characters, and all tabs
		 *
		 * @param str $js The JavaScript to minify
		 *
		 * @return str The minified JavaScript
		 */
		public static function MinifyJS ($var) {
			$var = preg_replace("/\/\/.*\n/", "", $var);
		    $var = preg_replace("/\/\*.*\/\*/", "", $var);
		    $var = str_replace("\n", '', $var);
		    $var = str_replace("  ", "", $var);
			$var = str_replace("\t", "", $var);
			return $var;
		}

		/**
		 * Returns a javascript snippet to go to a certain page
		 *
		 * Uses same structure as the Link class for parameters, just no link text
		 *
		 * @param str $base The page to go to
		 * @param str[] $url A list of url parameters to add on
		 *
		 * @return str The HTML/JavaScript to return
		 */
		public static function PageRedirect($base=Null, $url=[], $query=[]) {
			if (!isset($base)) {
				return self::PrepareJS("getHtml('/".HOST_SUB_DIR."');");
			}
			$nurl = '';
			foreach ($url as $fragment) {
				$nurl .= "$fragment/";
			}
			if (count($query) > 0) {
				$nurl .= "?";
				foreach ($query as $k => $v) {
					$nurl .= "$k=$v&";
				}
				$nurl = rtrim($nurl, "&");
			}
			return self::PrepareJS("getHtml('/".HOST_SUB_DIR."$base/$nurl');");
		}
	}
