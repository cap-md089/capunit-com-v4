<?php
	require_once (BASE_DIR."lib/templates/AsyncButton.php");

	class PageLink extends AsyncButton {
		protected $targetid;

		public function __construct ($text, $targetid) {
			$this->targetid = $targetid;
			parent::__construct($_SERVER['REQUEST_URI'].'#'.$targetid, $text, 'pageLink');
		}

		public function getHtml ($data = Null) {
			return parent::getHtml($this->targetid);
		}
	}