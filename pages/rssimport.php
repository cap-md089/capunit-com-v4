<?php
	class Output {
		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			if (!$m->hasDutyPosition(["Cadet Public Affairs Officer", "Cadet Public Affairs NCO", "Public Affairs Officer"])) return ['error' => 401];

			$file = File::Get($e['form-data']['rssFile'][0]);

			$data = simplexml_load_string($file->Data)->channel;

			Registry::Set("Header.TopText", $data->title);

			$url = explode("/", $data->image->url);
			$file = File::Create($url[count($url)-1], file_get_contents($data->image->url), $m);
			Registry::Set("Website.Logo", $file->ID);
			Registry::Set("Header.LeftImage", $file->ID);

			$pdo = DBUtils::CreateConnection();

			foreach ($data->item as $post) {
				$stmt = $pdo->prepare("SELECT(SELECT MAX(id) FROM ".DB_TABLES['Blog']." WHERE AccountID=:aid)+1 AS id");
				$stmt->bindValue(":aid", $a->id);
				$data = DBUtils::ExecutePDOStatement($stmt);
				$blogid = (int)$data[0]['id'];
				$stmt = $pdo->prepare("INSERT INTO ".DB_TABLES['Blog']." VALUES (:id, :title, :cid, :cont, :post, :aid);");
				$stmt->bindValue(':id', $blogid);
				$stmt->bindValue(":title", $post->title);
				$stmt->bindValue(':post', strtotime($post->pubDate));
				$stmt->bindValue(':cid', $m->uname);
				$text = $post->description.'';
				if (substr($text, 0, 5) == 'Tweet') $text = substr($text, 5);
				$stmt->bindValue(":cont", $text);
				$stmt->bindValue(":aid", $a->id);
				if (!$stmt->execute()) {
					trigger_error($stmt->errorInfo()[2], 512);
				}
			}

			return [
				'body' => '<script>document.location.href="/blog";</script>'
			];
		}
	}