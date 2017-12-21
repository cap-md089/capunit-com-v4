<?php
	/**
     * @package lib/templates/DetailedList
     *
     * Provides a list that can be used to display data
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */
	
	/**
	 * Displays information in a neat way
	 */
	class DetailedList extends Template {
		/**
		 * Constructs a DetailedList
		 *
		 * @param str|null List title, if set it is displayed, else it isn't
		 *
		 * @return \DetailedList
		 */
		public function __construct ($title=Null) {
			$this->data = array ();
			$this->html = '';
			$this->title = $title;
		}

		/**
		 * Adds an element to the list
		 *
		 * @param str $data Description title
		 * @param str|null $desc Description
		 * @param str|null $link A link, if it begins with a slash it will be an AJAX link
		 * @param str|null $linkt Link text
		 *
		 * @return this Useful for chaining
		 */
		public function addElement ($data, $description, $link=Null, $linkt) {
			$this->data[] = array (
				"data" => $data,
				"desc" => $description,
				"link" => $link,
				"linkt" => $linkt
			);
			return $this;
		}

		/**
		 * Gets the HTML for this list
		 *
		 * Note, this also sets the $this->html attribute for later use
		 *
		 * @param bool $blank Should the links for the event manager open in a new tab?
		 *
		 * @return str HTML
		 */
		public function getHtml ($blank=Null) {
			$this->html = '';
			if (isset($this->title)) {
				$this->html = "<div class=\"detailedlisttitle\">$this->title</div>\n";
			}

			$this->html .= "<ul class=\"detailedlist\">\n";

			foreach ($this->data as $line) {
				$this->html .= "<li>\n";
				$this->html .= "<div class=\"detailedlistrow\">\n";
				$this->html .= "<div class=\"detailedlistname\">".$line['data']."</div>\n";
				if (isset($line->link)) {
					$this->html .= "<div class=\"detailedlistlink\">";
					$linktext = isset($line['linkt']) ? $line['linkt'] : "Download";
					if (strpos($line->link, "/") == 0) { // AJAX link
						$this->html .= "<a href=\"".$line['link']."\" ".((isset($blank)&&($blank==1||$blank=true))?"target=\"_blank\" ":"")."onclick=\"return AJAXLinkClick(this);\">$linktext</a>";
					} else {
						$this->html .= "<a href=\"".$line['link']."\" target=\"_blank\">$linktext</a>";
					}
					$this->html .= "</div>\n";
				}
				$this->html .= "</div>\n";
				if (isset($line['desc'])) {
					$this->html .= "<div class=\"detailedlistdesc\">".$line['desc']."</div>";
				}
				$this->html .= "</div>\n";
				$this->html .= "</li>\n";
			}

			$this->html .= "<li></li></ul>";
			return $this->html;
		}
	}
?>