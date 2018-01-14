<?php
	define ("USER_REQUIRED", true);

	function generateHTML ($elems) {

	}

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
			$pdo = DB_Utils::CreateConnection();
			$title = '';
			$parse = new Parsedown();

			if (isset($e['uri'][$e['uribase-index']]) && !in_array($e['uri'][$e['uribase-index']], ['add', 'list', 'edit'])) {
				$stmt = $pdo->prepare("SELECT `text`, `name`, `id`, `parentname` FROM ".DB_TABLES['BlogPages']." WHERE `id` = :pname AND `AccountID` = :aid;");
				$stmt->bindParam(":pname", $e['uri'][$e['uribase-index']]);
				$stmt->bindValue(':aid', $a->id);

				$data = DB_Utils::ExecutePDOStatement($stmt);

				if (count($data) > 0) {
					$html = '';
					$parentname = $data[0]['parentname'];
					$blogpost = $data[0];
					if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {$html .= new Link("page", "Edit", ["edit", $data[0]['id']])."<br /><br />";}
					$ptext = $parse->parse(nl2br($data[0]['text']));


					$GLOBALS['refs'] = [];
					$parsed = preg_replace_callback(
						'/<h[1-6]>(.*)<\/h[1-6]>/',
						function ($matches) {
							$tag = substr($matches[0], 0, 3).' id="blogRef'.count($GLOBALS['refs']).'"'.substr($matches[0], 3);
							array_push($GLOBALS['refs'], $matches[1]);
							return $tag;
						},
						$ptext
					);

					$refs = $GLOBALS['refs'];
					$side = [];
					for ($i = 0; $i < count($refs); $i++) {
						$side[] = [
							'Type' => 'samesource',
							'Text' => $refs[$i],
							'Target' => 'blogRef'.$i
						];
					}

					$html .= $parsed;
					$stmt = $pdo->prepare("SELECT FileID FROM ".DB_TABLES['FileBlogAssignments']." WHERE BID = :bid AND AccountID = :aid;");
					$stmt->bindValue(":bid", $e['uri'][$e['uribase-index']]);
					$stmt->bindValue(':aid', $a->id);
					$title = $data[0]['name'];
					$bd = $data;
					$data = DBUtils::ExecutePDOStatement($stmt, true);
					$photos = [];
					foreach ($data as $d) {
						$photos[] = File::Get($d['FileID']);
					}

					$html .= "<div id=\"photo-bank\">";
					foreach ($photos as $photo) {
						if ($photo) {
							$html .= "<div class=\"image-box\"><a onclick=\"return !!viewImage(this);\" href = \"#\"><img class=\"image\" src=\"data:".$photo->ContentType.";base64,".base64_encode($photo->Data)."\" /><span class=\"comment\">".$photo->Comments."</span></a></div>";
						}
					}
					$html .= "</div>";


					$html .= "</div>\n";
					$html .= "<div style=\"height:5px;clear:both;\"></div>";

					$stmt = $pdo->prepare("SELECT `name`, `id` FROM `".DB_TABLES['BlogPages']."` WHERE `parentname` = :pname AND AccountID = :aid");
					$stmt->bindValue(":pname", $bd[0]['id']);
					$stmt->bindValue(":aid", $a->id);
					$data = DBUtils::ExecutePDOStatement($stmt);

					foreach ($data as $datum) {
						$side[] = [
							'Type' => 'link',
							'Text' => $datum['name'],
							'Target' => '/page/'.$datum['id']
						];
					}
					
					$parents = [
						[
							'Text' => $blogpost['name'],
							'Target' => '/page/'.$blogpost['id']
						]
					];
					while ($parentname != '') {
						$d = $pdo->prepare("SELECT `parentname`, `name` FROM `".DB_TABLES['BlogPages']."` WHERE `id` = :pid AND AccountID = :aid");
						$d->bindValue(':pid', $parentname);
						$d->bindValue(':aid', $a->id);
						$d = DBUtils::ExecutePDOStatement($d);
						$parents[] = [
							'Text' => $d[0]['name'],
							'Target' => '/page/'.$parentname
						];
						$parentname = $d[0]['parentname'];
					}
					$parents[] = [
						'Target' => '/',
						'Text' => 'Home'
					];
					$bc = UtilCollection::GenerateBreadCrumbs(array_reverse($parents));

					$html = [
						'MainBody' => $html,
						'SideNavigation' => UtilCollection::GenerateSideNavigation($side),
						'BreadCrumbs' => $bc
					];
				} else {
					ob_start();
?>
<h2>This is not the page you are looking for</h2>
We are sorry, the page <?php echo ltrim(explode("?", $_SERVER['REQUEST_URI'])[0], '/'); ?> does not exist.<br />
<a href="#" onclick="history.go(-1);">Go back a page</a>.
<?php
					$html = [
						'MainBody' => ob_get_clean()
					];
					$title = '404';
					http_response_code(404);
				}
			} else if (isset($e['uri'][$e['uribase-index']]) && $e['uri'][$e['uribase-index']] == 'list') {
				$stmt = $pdo->prepare("SELECT `name`, `id` FROM `".DB_TABLES['BlogPages']."` WHERE `parentname` = '' AND AccountID=:aid AND NOT `id` = 'none';");
				$stmt->bindValue(':aid', $a->id);
				$data = DB_Utils::ExecutePDOStatement($stmt);
				$list = new DetailedListPlus('Pages');
				function addpage2 ($id, $name) {
					global $_ACCOUNT;
					$pdo = DBUtils::CreateConnection();
					$l = new DetailedListPlus(new Link('page', $name, [$id]));
					$stmt = $pdo->prepare("SELECT `name`, `id` FROM `".DB_TABLES['BlogPages']."` WHERE `parentname` = :pid AND AccountID = :aid;");
					$stmt->bindValue(':pid', $id);
					$stmt->bindValue(':aid', $_ACCOUNT->id);
					$d = DBUtils::ExecutePDOStatement($stmt);
					if (count($d)) {
						foreach ($d as $datum) {
							$l->addElement($datum['name'], addpage2($datum['id'], $datum['name']));
						}
					}
					return $l->getHtml();
				}
				$html = '';
				if ($l) {
					$html .= new Link("page", "Add a page", ["add"]);
				}
				foreach ($data as $datum) {
					$html .= addpage2($datum['id'], $datum['name']);
				}
				$title = 'Page list';
			} else if (isset($e['uri'][$e['uribase-index']]) && $e['uri'][$e['uribase-index']] == 'add' && $l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
				$stmt = $pdo->prepare("SELECT `name`, `id`, `parentname` FROM `".DB_TABLES['BlogPages']."` WHERE AccountID = :aid;");
				$stmt->bindValue(':aid', $a->id);
				$values = DB_Utils::ExecutePDOStatement($stmt);
				$pages = [
					'' => 'None'
				];
				$parents = [];
				foreach ($values as $k) {
					$pages[$k['id']] = $k['name'];
				}
				$form = new AsyncForm("page", "Create Page");
				$form->addField('pageName', 'Name of the page', 'text');
				$form->addField('pageText', 'Page content', 'textarea');
				$form->addField('parent', 'Parent page', 'select', Null, $pages);
				$form->addField('photos', 'Photos', 'file');
				$html = $form->getHtml();
				$title = 'Add page';
			} else if (isset($e['uri'][$e['uribase-index']]) && $e['uri'][$e['uribase-index']] == 'edit' && $l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
				$stmt = $pdo->prepare("SELECT `text`, `name`, `parentname` FROM ".DB_TABLES['BlogPages']." WHERE `id` = :pname AND `AccountID` = :aid;");
				$stmt->bindParam(":pname", $e['uri'][$e['uribase-index']+1]);
				$stmt->bindValue(':aid', $a->id);

				$data = DB_Utils::ExecutePDOStatement($stmt);

				if (count($data) > 0) {
					$form = new AsyncForm ('page', 'Modify Page');
					$form->addField('newName', 'New Name', 'text', Null, [
						'value' => $data[0]['name']
					]);
					$form->addField('newText', 'New Text', 'textarea', Null, [
						'value' => $data[0]['text'],
						1 => 1 + ceil(strlen($data[0]['text'])/50)
					]);
					$stmt = $pdo->prepare("SELECT `name`, `id`, `parentname` FROM `".DB_TABLES['BlogPages']."` WHERE AccountID = :aid;");
					$stmt->bindValue(':aid', $a->id);
					$values = DB_Utils::ExecutePDOStatement($stmt);
					$pages = [
						'' => 'None'
					];
					$parents = [];
					foreach ($values as $k) {
						$pages[$k['id']] = $k['name'];
					}
					$form->addField('parent', 'Parent page', 'select', Null, $pages, $data[0]['parentname']);
					$form->addField('photos', 'Photos', 'file');
					$form->addHiddenField('pageId', $e['uri'][$e['uribase-index']+1]);
					$butt = new AsyncButton ('page', 'Delete page', "deletePageFuncs", 'rightFloat', Null, "PUT", "PUT");
					$html = $butt->getHtml($e['uri'][$e['uribase-index']+1]) . $form;
					$l = (new Link('page', '', [$e['uri'][$e['uribase-index']+1]]))->getURL(false);
					$html = 'Link to this page: '.(new Link('page', $l, [$e['uri'][$e['uribase-index']+1]]))."<br/><br/>".$html;
					$title = $data[0]['name'];
				} else {
					$title = '';
					$html = '';
				}
			} else {
				ob_start();
?>
<h2>This is not the page you are looking for</h2>
We are sorry, the page <?php echo ltrim(explode("?", $_SERVER['REQUEST_URI'])[0], '/'); ?> does not exist.<br />
<a href="#" onclick="history.go(-1);">Go back a page</a>.
<?php
				$html = ob_get_clean();
				$title = '404';
			}


			return [
				'body' => $html,
				'title' => $title
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
			$pdo = DB_Utils::CreateConnection();
			$done = false;
			$err = '';
			if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"])) && isset($e['raw']['newText']) && $e['raw']['newText'][0] != '') {
				$stmt = $pdo->prepare("UPDATE `".DB_TABLES['BlogPages']."` SET `text`=:ptext, `name`=:pname, `parentname`=:parname WHERE `id`=:pid AND AccountID = :aid;");
				$stmt->bindParam(":pname", $e['raw']['newName']);
				$stmt->bindValue(":ptext", $e['raw']['newText']);
				$stmt->bindParam(":pid", $e['raw']['pageId']);
				$stmt->bindValue(":parname", $e['raw']['parent']);
				$stmt->bindValue(':aid', $a->id);
				$done = $stmt->execute();
				if (isset($e['form-data']['photos'])) {
					$v1 = true;
					foreach ($e['form-data']['photos'] as $file) {
						$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileBlogAssignments']." VALUES (:fid, :bid, :aid);");
						$stmt->bindValue(':fid', trim($file));
						$stmt->bindValue(':bid', $e['raw']['pageId']);
						$stmt->bindvalue(':aid', $a->id);
						$v1 = $stmt->execute() && $v1;
						if (!$v1) {
							trigger_error($stmt->errorInfo()[2], 512);
						}
					}
				} else {
					$v1 = false;
				}
				return ($v1?'t':'f').JSSnippet::PageRedirect('page', [strtolower(preg_replace('/ /', '', $e['raw']['pageId']))]);
			} else if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"])) && isset($e['raw']['pageName']) && $e['raw']['pageName'][0] != '') {
				$stmt = $pdo->prepare ("INSERT INTO `".DB_TABLES['BlogPages']."` VALUES (:title, :text, :parent, :id, :aid);");
				$stmt->bindParam(":title", $e['raw']['pageName']);
				$id = strtolower(preg_replace('/ /', '', $e['raw']['pageName']));
				$stmt->bindParam(":id", $id);
				$stmt->bindValue(":text", $e['raw']['pageText']);
				$stmt->bindParam(':parent', $e['raw']['parent']);
				$stmt->bindValue(':aid', $a->id);
				$done = $stmt->execute();
				if (isset($e['form-data']['photos'])) {
					$v1 = true;
					foreach ($e['form-data']['photos'] as $file) {
						$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FileBlogAssignments']." VALUES (:fid, :bid, :aid);");
						$stmt->bindValue(':fid', trim($file));
						$stmt->bindValue(':bid', $id);
						$stmt->bindvalue(':aid', $a->id);
						$v1 = $stmt->execute() && $v1;
						if (!$v1) {
							trigger_error($stmt->errorInfo()[2], 512);
						}
					}
				} else {
					$v1 = false;
				}
				return ($v1?'t':'f').JSSnippet::PageRedirect('page', [strtolower(preg_replace('/ /', '', $e['raw']['pageName']))]);
			} else {
				if (!$l) {
					return [
						'error' => '411'
					];
				}
				if (!($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
					return [
						'error' => '401'
					];
				}
				if ($e['raw']['newHistory'][0] == '') {
					return [
						'error' => '421'
					];
				}
			}
		}

		public static function doPut ($e, $c, $l, $m, $a) {
			if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("DELETE FROM `".DB_TABLES['BlogPages']."` WHERE `id` = :id");
				$stmt->bindParam(":id", $e['parameter']['data']);
				return $stmt->execute() ? "Page deleted" : "Some error occurred";
			} else {
				if (!$l) {
					return [
						'error' => '411'
					];
				}
				if (!($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
					return [
						'error' => '401'
					];
				}
				if ($e['parameter']['data'][0] == '') {
					return [
						'error' => '421'
					];
				}
			}
		}
	}
