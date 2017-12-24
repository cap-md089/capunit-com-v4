
<?php
	define ("USER_REQUIRED", false);
	
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$pdo = DB_Utils::CreateConnection();
			
			// $stmt = $pdo->prepare("SELECT `path`, `postid` FROM `BlogPhotos` ORDER BY `timestamp` DESC".(isset($e['raw']['embed'])&&$e['raw']['embed']=='true'?" LIMIT 0, 12;":";"));
			// $pics = DB_Utils::ExecutePDOStatement($stmt);

			$pics_per_page = Registry::Get("PhotoLibrary.PPP");

			$stmt = $pdo->prepare("SELECT ID FROM ".DB_TABLES['FileData']." WHERE (AccountID = :aid OR AccountID = 'www') AND IsPhoto = 1 ORDER BY `Created` DESC LIMIT ".((isset($e['raw']['page'])?(int)$e['raw']['page']:0)*$pics_per_page).",$pics_per_page;");
			$stmt->bindValue(':aid', $a->id);
			$pics = DBUtils::ExecutePDOStatement($stmt);
			echo $stmt->errorInfo()[2];

			$html = '';

			if ((isset($e['raw']['page'])?(int)$e['raw']['page']:0) < 1 && $l && $m->hasDutyPosition(['Public Affairs Officer', 'Cadet Public Affairs Officer', 'Cadet Public Affairs NCO'])) {
				$form = new AsyncForm (Null, "Upload photos");
				$form->addField('photos', 'Upload photos', 'file');
				$form->addField('comments', 'Add a comment', 'textarea');
				$form->addHiddenField('function', 'upload');
				$html .= $form;
			}

			if ((isset($e['raw']['page'])?(int)$e['raw']['page']:0) < 1) {
				$html .= '<div id="photoLibraryBox" style="clear:both;margin:20px 0;padding-bottom:20px">';
				$html .= <<<SCRIPT
<script>
	var pageCount = 1,
		ready = true,
		photoLibraryLoaded = !!photoLibraryLoaded;
	function getPictures (data) {
		$("#photoLibraryBox").append(parseReturn(data).MainBody);
		pageCount+=1;
		if (Math.max($(document).height(), $("#mother").height()) < $(window).height()) {
			getHtml("/photolibrary/?page="+pageCount, null, null, getPictures, null, null, true, true);
		}
		setTimeout(function () {
			ready = true;
		}, 100);
	};
	if (!photoLibraryLoaded) {
		$(window).scroll(function() {
			console.log("Scroll!");
			if($(window).scrollTop() + $(window).height() > $(document).height() - 100 && ready) {
				console.log("At the bottom; " + $(window).scrollTop() + ", " + $(window).height() + ", "+ $(document).height());
				ready = false;
				getHtml("/photolibrary/?page="+pageCount, null, null, getPictures, null, null, true, true);
			}
		});
		photoLibraryLoaded = true;
	};
</script>
SCRIPT;
			}

			foreach ($pics as $pic) {
				//$url = (new Link('blog', null, ['view', $pic['postid']]))->getURL();
				$fpic = File::Get($pic['ID'], true);
				$html .= "<div class=\"image-box\"><a target=\"_blank\" onclick=\"return !!viewImage(this);\" href=\"\"><img class=\"image\" src=\"/".HOST_SUB_DIR."filedownloader/".$pic['ID']."?ajax=true\" /><span class=\"comments\" style=\"display:none\">{$fpic->Comments}</span></a></div>";
			}

			if ((isset($e['raw']['page'])?(int)$e['raw']['page']:0) < 1) {
				$html .= "</div><div style=\"clear:both;\"></div>";
			}

			return [
				'body' => $html,
				'title' => 'View photos'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error'=>411];}
			if ($m->hasDutyPosition(['Public Affairs Officer', 'Cadet Public Affairs Officer', 'Cadet Public Affairs NCO'])) {return ['error' => 401];}
			File::Get($e['form-data']['photos'][0])->Comment = $e['form-data']['comments'];
		}
	}
?>