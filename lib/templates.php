<?php
    /**
     * @package lib/templates
     *
     * General collection of templates, all have the getHtml and __toString methods defined which functionally do the same thing, alongside setOption which sets options
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */
	 
	/**
	 * Parent class for various templates
	 *
	 * All templates have the __toString and getHtml methods, which are supposed to do the same
	 * thing as __toString calls getHtml, and setOption, which allows for setting various options
	 * that a template may or may not use. This class is mainly used for semantic purposes for those
	 * who are curious at what the code looks like, and to keep
	 * the code pretty DRY (hey, it saves 8 lines per template)
	 */
    class Template {
		/**
		 * Returns the HTML for a template
		 *
		 * @return str
		 */
		public function getHtml () {
			return '';
		}

		/**
		 * Returns the HTML for a template
		 *
		 * @return str
		 */
        public function __toString () {
            return $this->getHtml();
        }

		/**
		 * Sets an option for an object
		 *
		 * @param str $option The option to set
		 * @param mixed $value The value to set
		 *
		 * @return this Useful for chaining
		 */
		public function setOption ($Option, $value) {
			$this->$Option = $value;
			return $this;
		}
	}

	require_once(BASE_DIR."lib/templates/AsyncButton.php");
	require_once(BASE_DIR."lib/templates/FileDownloader.php");
	require_once(BASE_DIR."lib/templates/AsyncForm.php");
	require_once(BASE_DIR."lib/templates/DetailedList.php");
	require_once(BASE_DIR."lib/templates/DetailedListPlus.php");
	require_once(BASE_DIR."lib/templates/Link.php");
	require_once(BASE_DIR."lib/templates/PaginatorC1.php");
	require_once(BASE_DIR."lib/templates/Table.php");
	require_once(BASE_DIR."lib/templates/TableRow.php");
	require_once(BASE_DIR."lib/templates/Calendar.php");
	require_once(BASE_DIR."lib/templates/PageLink.php");
?>
