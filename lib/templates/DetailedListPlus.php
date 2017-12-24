<?php
	/**
     * @package lib/templates/DetailedListPlus
     *
     * Provides a list that can be used to display data, except this one allows for dropdowns and can provide a way to phase out tables
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */
	
	/**
	 * Like a DetailedList, except it allows dropdowns. It's a good way to phase out tables
	 */
	class DetailedListPlus extends DetailedList {
		/**
		 * Creates a detailed list where if you clink a title it drops the description down
		 *
		 * Options (through setOption) includes only defaultopen, a boolean, which tell whether or not the rows should all be open
		 *
		 * @param str $title Title of detailed list
		 *
		 * @return \DetailedListPlus List
		 */
		public function __construct ($title=Null) {
			$this->data = array ();
			$this->title = isset($title) ? "<h2 class=\"title\">$title</h2>" : '';
			$this->defaultopen = false;
		}

		/**
		 * Adds a row
		 *
		 * @param str $data The data to display
		 * @param str $desc A description, drops down/pulls up
		 * @param str|Link $link If a string, inserts it into an A element, if a Link, then inserts the Link's HTML
		 * @param str $linkt Link text, defaults to 'Download'
		 *
		 * @return this
		 */
		public function addElement ($data, $desc, $link=Null, $linkt=Null, $open=Null) {
			$link = isset($link) ? $link : '';
			$linkt = isset($linkt) ? $linkt : 'Download';
			$this->data[] = [
				'data' => $data,
				'desc' => $desc,
				'link' => $link,
				'linkt' => $linkt,
				'open' => $open
			];

			return $this;
		}

		/**
		 * Gets the HTML of the list
		 *
		 * @return str HTML
		 */
		public function getHtml ($blank = Null) {
			$this->html = $this->title;

			$this->html .= "<ul class=\"detailedlistplus\" data-options=\"defaultopen:".($this->defaultopen?'true':'false')."\">";

			for ($i = 0; $i < count($this->data); $i++) {
				$this->html .= "<li>";

				$this->html .= "<div class=\"row$i detailedlistplusrow".(isset($this->data[$i]['open']) ?
				$this->data[$i]['open'] ? " open" : " closed" : ($this->defaultopen?' open':' closed'))."\">";
				$this->html .= "<div class=\"detailedlistplusarrow\"></div>";
				$this->html .= "<div class=\"detailedlistplusname\">".$this->data[$i]['data']."</div>";
				if (gettype($this->data[$i]['link']) == 'object') {
					$this->html .= "<div class=\"detailedlistpluslink\">" . $this->data[$i]['link'] . "</div>";
				} elseif ($this->data[$i]['link'] != '') {
					$this->html .= "<div class=\"detailedlistpluslink\"><a href=\"".$this->data[$i]['link']."\" target=\"_blank\">".$this->data[$i]['linkt']."</a></div>";
				}
				$this->html .= "</div>";
				$this->html .= "<div class=\"detailedlistplusdesc\">" . $this->data[$i]['desc'] . "</div>";

				$this->html .= "</li>";
			}

			$this->html .= "</ul>";

			return $this->html;
		}
	}
?>