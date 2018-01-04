<?php
	/**
     * @package lib/templates/Table
     *
     * Creates a table. Shouldn't be used, only for calanders
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */
	
	/**
	 * While tables should be phased out, they are useful for calendars
	 */
	class Table extends Template {
		/**
		 * Takes TableRows and displays a table
		 *
		 * @param TableRow[] $rows Rows of the table
		 * @param str $caption Caption to use
		 *
		 * @return \Table Constructs a Table
		 */
		 public function __construct ($elements, $caption) {
			 $this->html = '';
			 $this->elements = $elements;
			 $this->caption = $caption;
		 }

		 /**
		  * Generates HTML for the Table
		  *
		  * Note, this also sets the $this->html attribute for later use
		  */
		 public function getHtml () {
			 $this->html = "<table><caption>$this->caption</caption>";
			 $header = true;

			 foreach ($this->elements as $el) {
				 $this->html .= $el($header);
				 $header = false;
			 }

			 $this->html .= "</table>";

			 return $this->html;
		 }
	}
?>