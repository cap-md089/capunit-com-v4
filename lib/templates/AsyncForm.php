<?php
	/**
     * @package lib/templates/AsyncForm
     *
     * Provides a list that can be used to display data
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
	 */

	/**
	 * An asynchronous form that fits in with the rest of the website
     * The setOption function works here, as there is the option of using `reload` to trigger a page reload when the form gets a response
	 */
	class AsyncForm extends Template {
		/**
		 * @var bool Whether or not to initiate a page reload when submitting the form
		 */
		public $reload = true;

		/**
		 * Formats the output from multcheckbox and radiobox as a string for databases or something
		 *
		 * @param str[] $output The input from the user
		 * @param str[] $input The input to addField that was used to create the box
		 *
		 * @return str The result
		 */
		public static function ParseCheckboxOutput ($output, array $input) {
			if (gettype($output) == 'string') return $output;
			$ret = '';
			for ($i = 0, $j = 0; $i < count($output); $i++, $j++) {
				if (strtolower($input[$j]) == 'other') {
					$ret .= $output[$i] == 'true' ? $output[$i+1].', ' : '';
					$i++;
				} else {
					$ret .= $output[$i] == 'true' ? $input[$j].', ' : '';
				}
			}
			return rtrim($ret, ', ');
		}

		/**
		 * Constructs an Asynchronous Form
		 *
		 * @param str $pageId Action of the URL
		 * @param str $title Title of the form
         * @param str $class CSS class of the form
         * @param str $id CSS ID of the form
		 * @param str $method HTTP Method to use, defaults to PUT
		 *
		 * @return \AsyncForm Form
		 */
		public function __construct ($pageId=Null, $title=Null, $class=Null, $id=Null, $method="POST", $beforeSend=Null) {
			global $_FUNC;
			$this->pid = isset($pageId) ? $pageId : $_FUNC;
			$this->pid = ltrim($this->pid, '/');
			$this->pid = '/' . HOST_SUB_DIR . $this->pid;
			$this->method = $method;
			$this->id = isset($id) ? $id : "";
            $this->class = isset($class) ? $class : "asyncForm";
			$this->html = "";
			$this->fields = [];
			$this->hfields = [];
            $this->reload = true;
			$this->title = isset($title) ? "<h2>$title</h2>\n" : "";
			$this->beforeSend = $beforeSend;
			$this->submit = [
				"name" => "form-submit",
				"label" => "Submit",
				"class" => "forminput",
				"rowid" => "",
				"takeOutput" => true
			];
		}

		/**
		 * Generates the HTML for a form, because that's boring
		 *
		 * @return str HTML
		 */
		public function getHtml () {
			$this->html = "<div id=\"{$this->id}_box\">\n";
			$this->html .= $this->title;
			if ($this->id != "signIn") {
				 $this->html .= "<div id=\"output\"></div>\n";
			}
			$this->html .= "<form data-persists=\"garlic\" method=\"$this->method\" enctype=\"multipart/form-data\" ";
			$this->html .= ($this->id == 'signin' || $this->id == "signIn") ? "data-signin-form=\"true\" " : '';
            $this->html .= "data-form-reload=\"" . ($this->reload ? "true" : "false") . "\" ";
			$this->html .= "data-form-beforesend=\"" . (isset($this->beforeSend) ? $this->beforeSend : "") .'" ';
			$this->html .= "action=\"$this->pid\" onsubmit=\"return handleFormSubmit(this, ".($this->submit['takeOutput']?'true':'false').");\" class=\"$this->class asyncForm\" id=\"$this->id\">";
			$this->html .= "\n";

			foreach ($this->fields as $field) {
				$id = isset($field['rowid']) ? ' id="'.$field['rowid'].'"' : '';
                if ($field['ftype'] == 'header' || $field['ftype'] == 'label') {
                    $this->html .= "<div$id class=\"formbar fheader\">\n";
                } else {
                    $this->html .= "<div$id class=\"formbar\">\n";
                }
                if ($field['label'] == 'flat') {
					$this->html .= "<div class=\"formbox flat\">\n</div>";
				} else if ($field['label'] !== 'nolabel') {
                    $this->html .= "<div class=\"formbox\">\n";
                    $this->html .= "<label for=\"".$field['fname']."[]\">".$field['label']."</label>\n";
                    $this->html .= "</div>\n";
                } 
                $this->html .= "<div style=\"height:auto\" class=\"formbox\">\n";
				$this->html .= $field['fhtml'];
				$this->html .= "</div>\n";
				$this->html .= "</div>\n";
			}

			$this->html .= "<div class=\"formbar\" id=\"".$this->submit['rowid']."\">\n";
			$this->html .= "<div class=\"formbox flat\"></div>\n";
			$this->html .= "<div class=\"formbox\">\n";
			$this->html .= "<input type=\"submit\" class=\"".$this->submit['class']."\" name=\"".$this->submit['class']."\" value=\"".$this->submit['label']."\" />\n";
			if ($this->submit['takeOutput']) {
				$this->html .= "<div style=\"padding-left:10px;display:inline-block\" class=\"output\"></div>";
			}
			$this->html .= "</div>\n";
			$this->html .= "</div>\n";

			foreach ($this->hfields as $field) {
				$this->html .= "<input type=\"hidden\" value=\"".$field['value']."\" name=\"".$field['name']."\" />\n";
			}

			$this->html .= "</form>\n";
			$this->html .= "</div>\n";

			return $this->html;
		}

		/**
		 * Adds a field to the form
		 *
		 * If the field is of the following types, a 'data' argument is needed:
		 * 		select
		 *		autocomplete
		 *		textarea
		 *
		 *		For autocomplete, data must be an array with autocomplete values
		 *		For select, data must be an array of values to select
		 *		For textarea, data must be an array with index 0 being the width and index 1 being the height
		 *
		 * @param str $name Name attribute
		 * @param str $label Label for the field
		 * @param str $type Type attribute, custom autocomplete is included
		 * @param str $class CSS class
		 * @param mixed $data Data for different elements, this is for things such as select and autocomplete. If it has the 'value' key set, the value becomes the default value
		 * @param str|str[] $default Default value for field
		 * @param str $rowid The HTML ID for the row of the field
		 *
		 * @return self Useful for chaining, e.g. $form->addField ()->addField ()->addField()...->getHtml();
		 */
		public function addField ($name, $label, $type=Null, $class=Null, $data=Null, $default=Null, $rowid=Null) {
            $type = isset($type) ? $type : "text";
			$data = isset($data) ? $data : [];
			$class = isset($class) ? $class : 'forminput';
			$valueh = isset($data['value']) ? '' : '';

			switch (strtolower($type)) {
				case "checkbox" :
					$random = (string)rand(1000, 9999);
					$html = "<div class=\"checkboxDiv\" class=\"$class\"><input type=\"checkbox\"".(isset($default)&&$default?" checked":"")." value=\"".($data!=[]?$data:"true")."\" name=\"$name\" id=\"{$random}{$name}\" /><label for=\"{$random}{$name}\"></label></div>";
				break;

				case "multcheckbox" :
					if (isset($default) && $default && gettype($default) != 'string') {
						$default = implode(', ', $default);
					}
					$selected = [];
					$other = '';
					if (isset($default)) {
						$default = explode(', ', $default);
						foreach ($default as $v) {
							if (in_array($v, $data)) {
								$selected[] = $v;
							} else {
								$selected[] = 'other';
								if ($other == '') $other = $v;
								else $other .= ', '.$v;
							}
						}	
					}
					$html = "<section class=\"$class\">";
					for ($i = 0; $i < count($data); $i++) {
						$fname = $data[$i];
						$ftext = $fname;
						$checked = in_array($fname, $selected);
						$random = (string)rand(1000, 9999);
						if (strtolower($fname) == 'other') {
							$ftext = "Other: <input type=\"text\" name=\"{$name}[]\" class=\"otherInput\"";
							$ftext .= " value=\"$other\"";
							$ftext .= " />";
							if ($other != '') {
								$checked = true;
							}
						}
						$html .= "<div class=\"checkboxDiv checkboxDivMult\" class=\"forminput\"><input ".($checked?"checked ":"")."type=\"checkbox\" value=\"$fname\" name=\"{$name}[]\" id=\"{$random}{$name}{$fname}{$i}\" /><label for=\"{$random}{$name}{$fname}{$i}\"></label><label for=\"{$random}{$name}{$fname}{$i}\">$ftext</label></div>";
					}
					$html .= "</section>";
				break;

				case "radio" :
					$html = "<section class=\"$class radioDiv\">";
					$i = 0;
					$selected = '';
					if (isset($default)) {	
						foreach ($data as $fname => $fvalue) {
							if (is_int($fname)) {
								$fname = $fvalue;
							}
							if ($default == $fname) {
								$selected = $fname;
							}
						}
					} else {
						foreach ($data as $fname => $fvalue) {
							$selected = $fname;
							break;
						}
					}
					$disabled = isset($default);
					foreach ($data as $fname => $fvalue) {
						$random = (string)rand(1000, 9999);
						if (is_int($fname)) {
							$fname = $fvalue;
						}
						$ftext = $fvalue;
						if (strtolower($fvalue) == 'other') {
							$ftext = "Other: <input type=\"text\" id=\"{$random}{$name}Other\" class=\"otherRadioInput otherInput\" ";
							if ($selected == '') {
								$ftext .= "value=\"$default\"";
							}
							$ftext .= ' />';
							$fname = "";
						}
						$html .= "<div class=\"roundedTwo\">";
						$html .= "<input id=\"{$random}{$name}{$i}\" type=\"radio\" ".(isset($default)&&$default==$fname?"checked ":"")."name=\"$name\" value=\"".$fname."\"";
						if ($fname === $selected) {
							$html .= " checked";
						} else if (strtolower($fvalue) == 'other' && $selected == '') {
							$html .= " checked";
						}
						$html .= " />";
						$html .= "<label for=\"{$random}{$name}{$i}\">$ftext</label>";
						$html .= "<div class=\"check\"></div>";
						$html .= "</div>";
						$i++;

					}
					$html .= "</section>";
				break;

				case "select" :
                    $html = "<div class=\"selectDiv\">";
					$html .= "<select name=\"$name\" class=\"$class\">\n";
					foreach ($data as $k => $v) {
						$selected = false;
						if (isset($default)) $selected = (is_int($k) ? $v : $k) == $default;
						if (!is_int($k)) {
							$html .= "<option ".($selected?"selected=\"selected\" ":"")."value=\"".htmlspecialchars($k)."\">".htmlspecialchars($v)."</option>\n";
						} else {
							$html .= "<option ".($selected?"selected=\"selected\" ":"")."value=\"".htmlspecialchars($v)."\">".htmlspecialchars($v)."</option>\n";
						}
					}
					$html .= "</select>";
                    $html .= "<div class=\"downArrow\"></div>";
                    $html .= "</div>";
				break;

				case "autocomplete" :
					$html  = "<div class=\"autocomplete\">";
					$html .= "<input name=\"$name\" type=\"text\" class=\"$class\" ".(isset($default)?"value=\"$default\" ":"")."/>";
					$html .= "<div class=\"data\" style=\"display:none\">";
					foreach ($data as $v) {
						$html .= "<span>$v</span>";
					}
					$html .= "</div>";
				break;

				case "textarea" :
					if (isset($default)) {
						if (!isset($data)) $data = [];
						$data['value'] = $default;
					}
					$html = "<textarea ".(isset($default)?"value=\"$default\" ":"")."name=\"$name\" cols=\"".(isset($data[0])?$data[0]:32)."\" rows=\"".(isset($default)?$default:"").(isset($data[1])?$data[1]:4)."\" class=\"$class\">".(isset($data['value'])?$data['value']:'')."</textarea>";
				break;

				case "file" :
					$html = "<label class=\"file\" for=\"{$name}[]\">Upload</label><input id=\"{$name}[]\" type=\"file\" name=\"{$name}[]\" class=\"$class\" multiple=\"multiple\" /> or ".
					(new AsyncButton(Null, 'select files.', 'asyncFormSelectFilesInsteadOfUpload'))->getHtml($name);
					$this->addHiddenField('filesList[]', $name);
				break;

                case "header" :
                case "label" :
                    $html = '';
                    $label = '<h3>' . $label . '</h3>';
                break;

				case "readtext" :
				case "textread" :
					$html = $label;
					$label = "flat";
				break;

                case "range" :
                    $html = "<input ".(isset($default)?"value=\"$default\" ":"")."type=\"range\" name=\"$name\" class=\"$class\" min=\"".(isset($data['min'])?$data['min']:0)."\" max=\"".(isset($data['max'])?$data['max']:50)."\" value=\"";
                    $html .= (isset($data['value'])?$data['value']:0)."\" step=\"".(isset($data['step'])?$data['step']:1)."\" />";
					$html .= "<output for=\"$name\" onforminput=\"".(isset($data['format'])?$data['format']:"this.value=document.getElementById('{$name}').value;")."\"></output>";
                break;

				case "daterange" :
					$html = "<input type=\"range\" name=\"$name\" multiple class=\"$class\" min=\"".(isset($data['min'])?$data['min']:0)."\" max=\"".(isset($data['max'])?$data['max']:50)."\" ";
					$html .= "step=\"".(isset($data['step'])?$data['step']:60*15)."\" value=\"".(isset($data['min'])?$data['min']:0).",".(isset($data['max'])?$data['max']:50)."\" id=\"$name\" />";
					$html .= "<output for=\"$name\"></output>";
				break;

				default :
					$html = "<input ".(isset($default)?"value=\"$default\" ":"")."type=\"$type\" name=\"$name\" class=\"$class\"";
					if ($data != [] && isset($data)) {
						foreach ($data as $k => $v) {
							$html .= " $k=\"$v\"";
						}
					}
					$html .= " />";
				break;
			}
			$this->fields[] = [
				"label" => $label,
				"fhtml" => $html,
				"fname" => $name,
                "ftype" => $type,
				'rowid' => $rowid
			];

			return $this;
		}

		/**
		 * Adds a hidden field
		 *
		 * @param str $name Name of the field
		 * @param str $value Value of the field
		 *
		 * @return self Useful for chaining
		 */
		public function addHiddenField ($name, $value) {
			$this->hfields[] = [
				"name" => $name,
				"value" => $value
			];
			return $this;
		}

		/**
		 * Sets the info for the submit function
		 *
		 * @param str $label Label of the submit button
		 */
		public function setSubmitInfo ($label, $name=Null, $class=Null, $rowid=Null, $takeOutput=false) {
			$this->submit = [
				"label" => $label,
				"name" => isset($name) ? $name : "form-submit",
				"class" => isset($class) ? $class : "forminput",
				"rowid" => isset($rowid) ? $rowid : "",
				"takeOutput" => $takeOutput
			];
			return $this;
		}
	}
