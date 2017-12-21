<?php
	require_once (BASE_DIR."lib/DB_Utils.php");
	require_once (BASE_DIR."lib/Member.php");

	if (!function_exists('mime_content_type')) {
		function mime_content_type ($path) {
			$path = explode(".", $path);
			return [
				'css' => 'text/css',
				'js' => 'text/javascript',
				'png' => 'image/png',
				'jpg' => 'image/jpeg'
			][$path[count($path)-1]];
		}
	}

	class File {

		public $ID = '';

		public $Name = '';

		public $Data = '';

		public $Comments = '';

		public $ContentType = '';

		public $Created = 0;

		public $MD5 = '';

		public $MemberOnly = false;

		public $IsPhoto = false;

		public $Size = 0;

		public $Member;

		public $Account;

		private $destroyed = false;

		private $readonly = false;

		public static function Create ($name, $data, \Member $member, $comments='', $memberonly=false, $isphoto=false) {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();

			$stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileData']." WHERE MD5 = :md5 AND (AccountID = :aid OR AccountID='www');");
			$stmt->bindValue(':md5', md5($data).sha1($data));
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			$d = DB_Utils::ExecutePDOStatement($stmt);
			if (count($d) == 1) {
				return self::Get($d[0]['ID']);
			}

			$f = explode(".", $name);
			$end = $f[count($f)-1];
			$file = tmpfile();
			fwrite($file, $data);
			$metaDatas = stream_get_meta_data($file);
			$uri = $metaDatas['uri'];
			rename($uri, $uri.'.'.$end);
			$uri .= '.'.$end;
			$ctype = mime_content_type($uri);
			fclose($file);

			$id = uniqid('file', true);
			$time = time();
			$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileData']." VALUES 
			(:id, :uploadi, :uploadn, :name, :comments, :ctype, :created, :monly,
			:iphot, :slide, :data, :md5, :aid);");
			$stmt->bindValue(':id', $id);
			$stmt->bindValue(':uploadi', $member->uname);
			$stmt->bindValue(':uploadn', $member->memberRank . ' ' . $member->memberName);
			$stmt->bindValue(':name', $name);
			$stmt->bindValue(':data', $data);
			$stmt->bindValue(':comments', $comments);
			$stmt->bindValue(':ctype', $ctype);
			$stmt->bindValue(':created', $time);
			$stmt->bindValue(':md5', md5($data).sha1($data));
			$stmt->bindValue(':monly', $memberonly ? 1 : 0);
			$stmt->bindValue(':iphot', $isphoto ? 1 : 0);
			$stmt->bindValue(':slide', 0);
			$stmt->bindValue(':aid', $_ACCOUNT->id);

			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }

			return new self (array (
				'ID' => $id,
				'Name' => $name,
				'Data' => $data,
				'Comments' => $comments,
				'Created' => $time,
				'MemberOnly' => $memberonly,
				'Member' => $member,
				'IsPhoto' => $isphoto,
				'ForSlideshow' => false,
				'ContentType' => $ctype,
				'Size' => strlen($data),
				'UploadN' => $member->memberRank . ' ' . $member->memberName,
				'UploadI' => $member->uname,
				'MD5' => md5($data).sha1($data)
			));
		}

		public static function Get ($fid, $readonly=false) {
			global $_ACCOUNT;
			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['FileData']." WHERE ID = :id AND (".DB_TABLES['FileData'].".AccountID=:aid OR ".DB_TABLES['FileData'].".AccountID='www');");
			$stmt->bindValue(':id', $fid);
			$stmt->bindValue(':aid', $_ACCOUNT->id);
			$data = DB_Utils::ExecutePDOStatement($stmt);
			if (count($data) != 1) {
				return false;
			}
			$data = $data[0];
			$data['MemberOnly'] = $data['MemberOnly'] == 1; 
			$data['IsPhoto'] = $data['IsPhoto'] == 1;
			$data['Member'] = Member::Estimate($data['UploadI']);
			$data['MemberName'] = $data['UploadN'];
			$data['readonly'] = $readonly;
			$data['Size'] = strlen($data['Data']);
			return new self ($data);
		}

		public function __construct ($data) {
			$this->data = $data;
			foreach ($data as $k => $v) {
				$this->$k = $v;
			}
		}

		public function save () {
			if ($this->readonly) {
				return false;
			}

			global $_ACCOUNT;

			$pdo = DB_Utils::CreateConnection();
			$test = $this->Member ? 't' : 'f';

			$this->MD5 = md5($this->Data).sha1($this->Data);

			$stmt = $pdo->prepare("UPDATE ".DB_TABLES['FileData']." SET name=:name, 
			data=:data, comments=:comments, md5=:md5, memberonly=:monly, 
			UploadN=:uploadn, UploadI=:uploadi, IsPhoto=:iphot, ForSlideshow=:slide, Created=:create 
			WHERE ID=:id AND (AccountID=:aid OR AccountID='www');");
			$stmt->bindValue(":name", $this->Name);
			$stmt->bindValue(":data", $this->Data);
			$stmt->bindValue(":comments", $this->Comments);
			$stmt->bindValue(":md5", md5($this->Data).sha1($this->Data));
			$stmt->bindValue(":uploadn", $this->UploadN);
			$stmt->bindValue(':uploadi', $this->UploadI);
			$stmt->bindValue(":monly", $this->MemberOnly ? 1 : 0);
			$stmt->bindValue(':iphot', $this->IsPhoto ? 1 : 0);
			$stmt->bindValue(':slide', $this->ForSlideshow ? 1 : 0);
			$stmt->bindValue(':create', $this->Created);
			$stmt->bindValue(":id", $this->ID);
			$stmt->bindValue(':aid', $_ACCOUNT->id);

			if (!$stmt->execute()) {
				trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            }

			return true;
		}

		public function __destruct () {
			if (!$this->destroyed) {
				$this->save();
				$this->destroyed = true;
			}
		}

		public function remove () {
			if (!$this->destroyed) {
				global $_ACCOUNT;
				$this->destroyed = true;
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['FileData']." WHERE ID=:id AND AccountID=:aid;");
				$stmt->bindValue(':aid', $_ACCOUNT->id);
				$stmt->bindValue(":id", $this->ID);
				
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            	}

				$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['FilePhotoAssignments']." WHERE FileID = :id AND AccountID = :aid;");
				$stmt->bindValue(':aid', $_ACCOUNT->id);
				$stmt->bindValue(":id", $this->ID);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            	}

				$stmt = $pdo->prepare("DELETE FROM ".DB_TABLES['FileEventAssignments']." WHERE FileID = :id AND AccountID = :aid;");
				$stmt->bindValue(':aid', $_ACCOUNT->id);
				$stmt->bindValue(":id", $this->ID);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], E_USER_WARNING);
            	}

				return true;
			} else {
				return false;
			}
		}

		public function _destroy () {
			$this->destroyed = true;
		}
	}