<?php
	define ("USER_REQUIRED", true);
	
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}


			$parse = new Parsedown();

            if (isset($e['uri'][$e['uribase-index']]) && $e['uri'][$e['uribase-index']] == 'view') {
                $html = '';

                $id = $e['uri'][$e['uribase-index']+1];
				
				if ($l) {
					if (($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
						$del = new AsyncButton("blog", "Delete post", "deletePost");
						$del = $del->getHtml($id);
						$link = ' '.(new Link('blog', 'Edit blog post', ['edit', $id])).' '.$del;
					} else {
						$link = '';
					}
				} else {
					$link = '';
				}

                $pdo = DB_Utils::CreateConnection();

                $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['Blog']." WHERE `id` = :bid AND AccountID = :aid;");
                $stmt->bindValue(":bid", $id);
				$stmt->bindValue(':aid', $a->id);
                $blogp = DB_Utils::ExecutePDOStatement($stmt);

                $stmt = $pdo->prepare("SELECT NameFirst, NameMiddle, NameLast, NameSuffix, Rank FROM ".DB_TABLES['Member']." WHERE `CAPID` = :cid AND ORGID in {$a->orgSQL};");
                $stmt->bindValue(":cid", $blogp[0]['acapid']);
                $user = DB_Utils::ExecutePDOStatement($stmt);

				$memname = $user[0]['NameFirst'] . ' ' . substr($user[0]['NameMiddle'], 0, 1) . ' ' . $user[0]['NameLast'] . ' ' . $user[0]['NameSuffix'];

				$blog = $blogp[0];
				$desc = substr($blog['content'], 0, 150);
				if (!$desc) $desc = $blog['content'];
				else $desc .= '...';
				
				$parsed = $parse->parse($blog['content']);

				$GLOBALS['refs'] = [];
				$parsed = preg_replace_callback(
					'/<h[1-6]>(.*)<\/h[1-6]>/',
					function ($matches) {
						global $refs;
						$tag = substr($matches[0], 0, 3).' id="blogRef'.count($refs).'"'.substr($matches[0], 3);
						array_push($GLOBALS['refs'], $matches[1]);
						return $tag;
					},
					$parsed
				);

				$refs = $GLOBALS['refs'];
				$side = [];
				for ($i = 0; $i < count($refs); $i++) {
					$side[] = [
						'Type' => 'samesource',
						'Target' => 'blogRef'.$i,
						'Text' => $refs[$i]
					];
				}

                $url = new Link ('blog', '', ['view', $blog['id']]);
                $html = "<div><a href=\"%s\" onclick=\"return !!AJAXLinkClick(this);\"><h2 class=\"blog-title\">%s</h2></a><div><span class=\"author\">%s$link</span><span class=\"date\">%s</span></div><div style=\"height:5px;clear:both;\"></div>\n\t<div class=\"blog-post\">%s</div>\n";
                $html = sprintf($html, $url->getURL(), $blog['title'], $user[0]['Rank'].' '.$memname, date ('D, d M Y', $blog['posted']),$parsed);

                /*$stmt = $pdo->prepare("SELECT `path` FROM `BlogPhotos` WHERE `postid` = :bid AND AccoutnID = :aid ORDER BY `timestamp` DESC;");
                $stmt->bindValue(":bid", $id);
				$stmt->bindValue(':aid', $a->id);
                $photos = DB_Utils::ExecutePDOStatement($stmt);

                $html .= "\n\t<div id=\"photo-bank\">\n";
                if (count($photos) > 0) {
                    foreach ($photos as $path) {
                        $html .= "\t\t<div class=\"image-box\"><a onclick=\"return !!viewImage(this);\" href=\"#\"><img class=\"image\" src=\"/".HOST_SUB_DIR."user-uploads".$path[0]."\" /></a></div>\n";
                    }
                }
                $html .= "\t</div>\n";*/

				$stmt = $pdo->prepare("SELECT FileID FROM ".DB_TABLES['FilePhotoAssignments']." WHERE BID = :bid AND AccountID = :aid;");
				$stmt->bindValue(":bid", $id);
				$stmt->bindValue(':aid', $a->id);
				$data = DBUtils::ExecutePDOStatement($stmt);
				$photos = [];
				foreach ($data as $d) {
					$photos[] = File::Get($d['FileID']);
				}

				$html .= "<div id=\"photo-bank\">";
				foreach ($photos as $photo) {
					$html .= "<div class=\"image-box\"><a onclick=\"return !!viewImage(this);\" href = \"#\"><img class=\"image\" src=\"data:".$photo->ContentType.";base64,".base64_encode($photo->Data)."\" /><span class=\"comment\">".$photo->Comments."</span></a></div>";
				}
				$html .= "</div>";
				

                $html .= "</div>\n";
                $html .= "<div style=\"height:5px;clear:both;\"></div>";
				
                return [
					'body' => [
						'MainBody' => $html,
						'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
							[
								'Target' => '/',
								'Text' => 'Home'
							],
							[
								'Target' => '/blog',
								'Text' => 'News'
							],
							[
								'Target' => '/blog/view/'.$blog['id'],
								'Text' => $blog['title']
							]
						]),
						'SideNavigation' => UtilCollection::GenerateSideNavigation($side),
						'Description' => $desc
					],
					'title' => $blog['title']
				];
            } else if (isset($e['uri'][$e['uribase-index']]) && $e['uri'][$e['uribase-index']] == 'edit') {
                if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
					$pdo = DB_Utils::CreateConnection();
                    $form = new AsyncForm("blog", "Edit blog post");
                    $id = $e['uri'][$e['uribase-index']+1];

					$link = new Link('blog', 'View blog post', ['view', $id]);

                    $stmt = $pdo->prepare("SELECT * FROM ".DB_TABLES['Blog']." WHERE `id` = :bid AND AccountID = :aid;");
                    $stmt->bindValue(":bid", $id);
					$stmt->bindValue(':aid', $a->id);
                    $blogp = DB_Utils::ExecutePDOStatement($stmt);

					$form->addField('newName', 'New Name', 'text', Null, [
						'value' => $blogp[0]['title']
					]);
					$form->addField('newText', 'New Text', 'textarea', Null, [
						'value' => $blogp[0]['content']
					]);
					$form->addField('newPhotos', 'New Photos', 'file');

					$form->addHiddenField('function', 'edit');
					$form->addHiddenField('postId', $blogp[0]['id']);

					$title = $blogp[0]['title'];
					$blogp2 = $blogp;

					$stmt = $pdo->prepare("SELECT FilePhotoAssignments.FileID FROM ".DB_TABLES['FilePhotoAssignments']." AS FilePhotoAssignments INNER JOIN ".DB_TABLES['Blog']." AS Blog On Blog.id = FilePhotoAssignments.BID WHERE (Blog.id = :bid AND FilePhotoAssignments.AccountID = :aid);");
					$stmt->bindValue(':bid', $id);
					$stmt->bindValue(':aid', $a->id);
					$blogp = DB_Utils::ExecutePDOStatement($stmt);
					$pics = '<div id="photo-bank">';
					foreach ($blogp as $pic) {
						$file = File::Get($pic['FileID']);
						$butt = new AsyncButton("blog", $file->Name . "<span class=\"bigfatred rightFloat\">X</span><div class=\"image-box popupimage\"><img class=\"image\" src=\"/".HOST_SUB_DIR."filedownloader/".$pic['FileID']."?ajax=true\" /></div>", "deletePhotos", "width600px popupimagecontainer");
						$pics .= $butt->getHtml($file->ID);
					}
					$pics .= "</div>";

                    return [
						'title' => $title,
						'body' => [
							'MainBody' => $link . $form . $pics,
							'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
								[
									'Target' => '/',
									'Text' => 'Home'
								],
								[
									'Target' => '/blog',
									'Text' => 'News'
								],
								[
									'Target' => '/blog/view/'.$id,
									'Text' => $blogp2[0]['title']
								],
								[
									'Target' => '/blog/edit/'.$id,
									'Text' => 'Edit \''.$blogp2[0]['title'].'\''
								]
							])
						]
					];
                } else if (!$l) {
                    return [
                        'error' => '411'
                    ];
                } else if (!($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
                    return [
                        'error' => '401'
                    ];
                }
            } else if (isset($e['uri'][$e['uribase-index']]) && $e['uri'][$e['uribase-index']] == 'post' && ($l && $m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
                $form = new AsyncForm ("blog", "Create blog post");
				$form->addField('postName', 'Post name', 'text');
				$form->addField('postText', 'Post text', 'textarea');
				$form->addField('photos', 'Upload photos', 'file');
				
				$form->setOption('reload', false);

				$form->addHiddenField('function', 'post');

				return [
					'title' => 'Post blog post',
					'body' => $form->getHtml()
				];
            } else {
                $html = '';
				if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]))) {
					$l1 = new Link("page", "View pages", ['list']);
					$l2 = new Link("page", "Add page", ['add']);
					$l3 = new Link("blog", "Post blog post", ['post']);
					$html .= <<<HTM
<div class="adminsection">
<h2 class="title">Feel like adding to the blog?</h2>
<div>
	$l1 | $l2 | $l3
</div>
</div>
HTM;
				}
                $template = "<div id=\"post%s\">\n\t<a href=\"%s\" target=\"_blank\" onclick=\"return !!AJAXLinkClick(this);\"><h2 class=\"blog-title\">%s</h2></a>\n\t<div><span class=\"author\">%s</span><span class=\"date\">%s</span></div>\n\t<div style=\"clear:both\"></div><div class=\"blog-post\">%s</div>\n<div><a href=\"%s\" target=\"_blank\" onclick=\"return !!AJAXLinkClick(this);\">Read more...</a></div>\n</div>\n";

                $pdo = DB_Utils::CreateConnection();

                $ustmt = $pdo->prepare('SELECT CAPID, NameFirst, NameMiddle, NameLast, NameSuffix, Rank FROM '.DB_TABLES['Member'].' WHERE ORGID in '.$a->orgSQL.';');
                $users = DB_Utils::ExecutePDOStatement($ustmt);

                $stmt = $pdo->prepare('SELECT * FROM Blog WHERE AccountID = :aid ORDER BY `posted` DESC;');
				$stmt->bindValue(":aid", $a->id);
				$blogp = DB_Utils::ExecutePDOStatement($stmt);
				$links = [];
				if ($l && $m->hasDutyPosition(['Cadet Public Affairs Officer', 'Cadet Public Affairs NCO', 'Public Affairs Officer'])) {
					$links = [
						[
							'Type' => 'link',
							'Target' => '/page/list',
							'Text' => 'View pages'
						],
						[
							'Type' => 'link',
							'Target' => '/page/add',
							'Text' => 'Add page'
						],
						[
							'Type' => 'link',
							'Target' => '/blog/post',
							'Text' => 'Post blog post'
						]
					];
				}

                for ($i = 0; $i < count($blogp); $i++) {
                    $blog = $blogp[$i];
                    $author = '';
                    for ($j = 0; $j < count($users); $j++) {
                        if ($users[$j]['CAPID'] == $blog['acapid']) {
                            $author = $users[$j]['Rank'] . ' ' . $users[$j]['NameFirst'] . ' ' . substr($users[$j]['NameMiddle'], 0, 1) . ' ' . $users[$j]['NameLast'] . ' ' . $users[$j]['NameSuffix'];
                            break;
                        }
                    }
                    $content = substr($blog['content'], 0, 497);
                    if (!$content) $content = $blog['content'];
                    else $content .= "...";
                    $url = new Link ('blog', '', ['view', $blog['id']]);
                    $html .= sprintf($template, $i, $url->getURL(), $blog['title'], $author, date ('D, d M Y', $blog['posted']), $parse->parse($content), $url->getURL());
                    if ($i < count($blogp) - 1) {
                        $html .= "<hr class=\"hr-divider\" />\n";
					}
					$links[] = [
						'Target' => "post$i",
						'Type' => 'ref',
						'Text' => $blog['title']
					];
                }
				$html = [
					'MainBody' => $html,
					'SideNavigation' => UtilCollection::GenerateSideNavigation($links),
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/blog',
							'Text' => 'News'
						]
					]),
					'Description' => 'View the most recent news for CAP-'.$a
				];

                return [
					'body' => $html,
					'title' => 'News'
				];
            }
        }

        public static function doPost ($e, $c, $l, $m, $a) {
			if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"]) )) {
				$pdo = DB_Utils::CreateConnection();
				if (isset($e['raw']['function']) && $e['raw']['function'] == 'edit') {
					$blogid = $e['raw']['postId'];
					$files = [];
					if (isset($e['form-data']['newPhotos'])) {
						$v1 = true;
						$data;
						foreach ($e['form-data']['newPhotos'] as $file) {
							$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FilePhotoAssignments']." VALUES (:fid, :bid, :aid);");
							$stmt->bindValue(':fid', $file);
							$data = File::Get($file);
							$data->IsPhoto = true;
							$data->save();
							unset($data);
							$stmt->bindValue(':bid', $blogid);
							$stmt->bindvalue(':aid', $a->id);
							$v1 = $stmt->execute() && $v1;
						}
					} else {
						$v1 = false;
					}
					$stmt = $pdo->prepare('UPDATE '.DB_TABLES['Blog'].' SET title=:title, content=:content WHERE id=:id AND AccountID = :aid;');
					$stmt->bindValue(':title', $e['raw']['newName']);
					$stmt->bindValue(':content', $e['raw']['newText']);
					$stmt->bindValue(':id', $e['raw']['postId']);
					$stmt->bindValue(':aid', $a->id);
					$v2 = $stmt->execute();
					return var_export($v1, true).var_export($v2, true);
				} else if (isset($e['raw']['function']) && $e['raw']['function'] == 'post') {
					$stmt = $pdo->prepare('SELECT(SELECT MAX(id) FROM '.DB_TABLES['Blog'].' WHERE AccountID=:aid)+1 AS EventNumber');
					$stmt->bindValue(":aid", $a->id);
					$data = DBUtils::ExecutePDOStatement($stmt);
					$blogid = (int)$data[0]['EventNumber'];
					$stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['Blog'].' (id, title, acapid, content, posted, AccountID) VALUES (:id, :title, :capid, :content, :time, :aid);');
					$stmt->bindValue(':id', $blogid);
					$stmt->bindValue(':title', $e['raw']['postName']);
					$stmt->bindValue(':capid', $m->uname);
					$stmt->bindValue(':content', $e['raw']['postText']);
					$stmt->bindValue(':time', time());
					$stmt->bindValue(':aid', $a->id);
					$v2 = $stmt->execute();
					if (isset($e['form-data']['photos'])) {
						$v1 = true;
						$data;
						foreach ($e['form-data']['photos'] as $file) {
							$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['FilePhotoAssignments']." VALUES (:fid, :bid, :aid);");
							$stmt->bindValue(':fid', $file);
							$data = File::Get($file);
							$data->IsPhoto = true;
							$data->save();
							unset($data);
							$stmt->bindValue(':bid', $blogid);
							$stmt->bindvalue(':aid', $a->id);
							$v1 = $stmt->execute() && $v1;
						}
					} else {
						$v1 = false;
					}
					return JSSnippet::PageRedirect('blog', ['view', $blogid]);
				} else {
					return [
						'error' => '421'
					];
				}
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
			}
        }

		public static function doPut ($e, $c, $l, $m, $a) {
			if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"])) && $e['parameter']['predata'] == 'photo') {
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("DELETE FROM `".DB_TABLES['FilePhotoAssignments']."` WHERE `FileID` = :id AND AccountID = :aid");
				$stmt->bindParam(":id", $e['parameter']['data']);
				$stmt->bindParam(":aid", $a->id);
				return $stmt->execute() ? "Photo deleted" : "Some error occurred";
			} else if ($l && ($m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"])) && $e['parameter']['predata'] == 'post') {
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("DELETE FROM `".DB_TABLES['Blog']."` WHERE `id`=:id AND `AccountID` = :aid;");
				$stmt->bindValue(":id", $e['parameter']['data']);
				$stmt->bindValue(":aid", $a->id);
				return $stmt->execute() ? "Post deleted" : "Some error occured";
			} else {
				if (!$l) {
					return [
						'error' => '411'
					];
				}
				if ($m->perms["PostBlogPost"] == 0) {
					return [
						'error' => '401'
					];
				}
			}
		}
    }