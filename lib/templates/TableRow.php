<?php
	/**
	 * Forms a table row, to be used in conjunction with Table class
	 */
	class TableRow extends Template {
		/**
		 * Given an array structure, creates a TableRow which can be used by the Table
		 *
		 * @param array[] $elements Array structure, with each array being associative and having a 'class' key and a 'data' key
		 *
		 * @return \TableRow Constructs a TableRow
		 */
		public function __construct ($elements) {
			$this->elements = $elements;
			$this->html = "";
		}

		/**
		 * Generates and returns HTML for the TableRow
		 *
		 * Note, this also sets the $this->html attribute for later use
		 *
		 * @param boolean $header Should the table elements be TH or TD?
		 *
		 * @return string $html HTML for a tablerow
		 */
		public function getHtml ($header=Null) {
			$this->html = "<tr>";

			$el = isset($header) && $header ? "th" : "td";
			foreach ($this->elements as $el) {
				$this->html .= "<$el class=\"" . isset($el['class']) ? $el['class'] : '' . "\">" . $el['data'] . "</$el>";
			}

			$this->html .= "</tr>";

			return $this->html;
		}

		/**
		 * Overwrites data for different elements
		 *
		 * Uses the array_merge function, if one is curious
		 *
		 * @param string $index Index of elements to overwrite
		 * @param string[] $data New data to overwrite with, if data in this array is not present the element corresponding in the other array is not overwritten
		 *
		 * @return this $this For chaining
		 */
		public function setData ($i, $data) {
			$this->elements[$i] = array_merge($this->elements[$i], $data);
			return $this;
		}
	}
?>