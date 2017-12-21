<?php
	require_once BASE_DIR . "lib/templates/AsyncButton.php";

	class FileDownloader extends AsyncButton {
		
		protected $fileid;
		
		public function __construct ($text, $fileid) {
			$this->fileid = $fileid;
			parent::__construct('filedownloader', $text, "fileDownloader");
		}

		public function getHtml ($data = Null) {
			return parent::getHtml($this->fileid);
		}
	}