<?php
	/**
     * @package lib/templates/Link
     *
     * Defines a link for AJAX navigation, to be used instead of regular <a> elements
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */

	/**
	 * A Link which is used for AJAX navigation, otherwise use a regular <a> element
	 */
    class Link extends Template {
		/**
		 * Constructs a link element, which is used for AJAX links. Otherwise <a> elements work fine
		 *
		 * @param str $target Can be _blank to open a page in a new window
		 * @param str $text The text of the link
		 * @param str[] $path An array for the construction of the URI, e.g. ['a', 'path'] becomes /a/path
		 * @param str[] $query An array for the construction of the URI, e.g. ['key' => 'val'] becomes ?key=val
		 *
		 * @return \Link
		 */
		public function __construct ($target=Null, $text=Null, $path=Null, $query=Null, $class=Null) {
			$this->target = isset($target) ? $target : '';
			$this->text = isset($text) ? $text : '';
			$this->path = isset($path) ? $path : array();
			$this->query = isset($query) ? $query : array();
			$this->class = isset($class) ? ' class="'.$class.'"' : '';
            if (isset($_METHODD['embed']) && $_METHODD['embed'] == 'true') {
                $this->_target = '_blank';
            }
        }

		/**
		 * Gets the URL to be used for the Link element
		 *
		 * @return string $url URL for the Link element
		 */
		public function getURL ($abslink = true) {
			global $_ACCOUNT;
			$url = (!$abslink ? ((HOST_SSL ? "https:" : "http:") . '//'.$_ACCOUNT->id.'.'. HOST_ADDRESS . '/') : '/') . HOST_SUB_DIR . $this->target . ($this->target == '' ? '' : '/');
			foreach ($this->path as $dir) {
				$url .= $dir . '/';
			}
			$url .= '?';
			foreach ($this->query as $k => $v) {
				$url .= "$k=$v&";
			}
			return rtrim(rtrim($url, "&"), "?");
		}

		/**
		 * Gets the HTML of the Link Element
		 *
		 * @return string $html HTML for the Link Element
		 */
        public function getHtml () {
			$url = $this->getURL();
			return "<a$this->class href=\"" . $url . '" ' . (isset($this->_target) ? "target=\"" . $this->_target .'" ' : '') . 'onclick="return !!AJAXLinkClick(this);">' . $this->text . '</a>';
        }

		/**
		 * Sets part of the query up
		 *
		 * @param string $key Key to set in query, e.g. ?key=value
		 * @param string $value Value to set in query, e.g. ?key=value
		 */
		public function setKeyValue ($key, $value) {
			$this->query[$key] = $value;
		}
    }