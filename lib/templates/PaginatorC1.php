<?php
	/**
     * @package lib/templates/PaginatorC1
     *
     * Creates a paginator that can be used to different pages
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */

	/**
	 * The Class 1 Paginator has a navigation bar at the top, the class 2 paginator has numbers at the bottom (needs to be made still)
	 */
	class PaginatorC1 extends Template {
		/**
		 * The pages are made by another method
		 *
		 * @return \PaginatorC1
		 */
		public function __construct () {
			$this->pagehtml = '';
			$this->navhtml = '';
			$this->pages = [];
			$this->names = [];
		}

		/**
		 * Adds a page
		 *
		 * @param str $name Name of the page
		 * @param str $page Page HTML, can be anything
		 *
		 * @return PaginatorC1
		 */
		public function addPage ($name, $page) {
			$this->names[] = $name;
			$this->pages[] = $page;

			return $this;
		}

		/**
		 * Gets the HTMl of the Paginator
		 *
		 * @return str HTML
		 */
		public function getHtml () {
			$this->html = "";
			$this->pagehtml = "<div class=\"pages\">";
			$this->navhtml = "<ul class=\"pagenav\">";

			$navt = "<li><a href=\"#%s\">%s</li></a>";
			$pagt = "<div id=\"%s\" class=\"page-%s page inactive\">%s</div>";

			for ($i = 0; $i < count($this->names); $i++) {
				$pid = strtolower(implode("", explode(" ", $this->names[$i]))) . (string)$i;
				$this->navhtml .= sprintf($navt, $pid, $this->names[$i]);
				$this->pagehtml .= sprintf($i == 0 ? "<div id=\"%s\" class=\"page-%s page active\">%s</div>" : $pagt, $pid, $pid, $this->pages[$i]);
			}

			$this->pagehtml .= "</div>";
			$this->navhtml .= "</ul>";

			$this->html = $this->navhtml . $this->pagehtml . "<div style=\"clear:both;\"></div>";

			return $this->html;
		}
	}
?>