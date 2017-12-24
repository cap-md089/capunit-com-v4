<?php
	/**
     * @package lib/templates/AsyncButton
     *
     * 
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */

	/**
	 * An asynchronous button that can be used to submit data to the server
	 */
	class AsyncButton extends Template {

		public $data = '';
		/**
		 * Constructs the button
		 *
		 * @param str $pageId Action of the URL
		 * @param str $text Text label
		 * @param str $cbackn Name of JavaScript function callback
		 * @param str $class CSS class
		 * @param str $id CSS ID
		 * @param str $func Function to be sent, part of the GET query
		 * @param str $method HTTP method to use, defaults to POST
		 *
		 * @return \AsyncButton Button
		 */
		public function __construct ($pageId=Null, $text=Null, $cbackn="bcback", $class=Null, $id=Null, $func="PUT", $method="PUT") {
			global $_FUNC;
			$this->pid = isset($pageId) ? $pageId : $_FUNC;
			if (strpos($this->pid, '/') === false) {
				$this->pid = '/'.HOST_SUB_DIR.$this->pid;
			}
			$this->method = $method;
			$this->id = isset($id) ? $id : "";
			$this->class = isset($class) ? $class : "asyncbutton";
			$this->html = "";
			$this->data = "";
			$this->func = isset($func) ? $func : 'PUT';
			$this->cbackn = $cbackn;
			$this->text = $text;
		}

		/**
		 * Gets the HTML of the button
		 *
		 * @param str $data Data to pass to the
		 *
		 * @return str HTML of button
		 */
		public function getHtml ($data=Null) {
			if (isset ($data)) {
				
			} else if (isset($this->data)) {
				$data = $this->data;
			} else {
				
			}
			$this->html = '';
			$this->html .= "<a href=\"$this->pid\"";
			$this->html .= " class=\"asyncButton $this->class\"";
			$this->html .= $this->id != '' ? " id=\"$this->id\"" : "";
			$this->html .= " onclick=\"return !!AsyncButton(th"."is, '$this->cbackn');\"";
			$this->html .= " data-http-method=\"$this->method\"";
			$this->html .= " data-http-func=\"$this->func\"";
			$this->html .= isset($data) ? " data-http-data=\"".htmlentities($data)."\"" : '';
			$this->html .= ">$this->text</a>";

			return $this->html;
		}
	}
?>