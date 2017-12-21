<?php
	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			$html = '';
			if (isset($e['parameter']['query'])) {
				$pdo = DBUtils::CreateConnection();
				$parse = new Parsedown();

				$stmt = $pdo->prepare("(select `content`, `title`, match (`content`, `title`) against (:query) as score, 'post' as `type`, `id` from `Blog` where AccountID = :aid) union
	(select `text`, `name`, match (`text`, `name`) against (:query) as score, 'page' as `type`, `id` from `pages` where AccountID = :aid)
	order by score desc;");
				$stmt->bindValue(':aid', $a->id);
				$stmt->bindValue(':query', $e['parameter']['query']);
				$data = DBUtils::ExecutePDOStatement($stmt);
				$html = '';
				$btemplate = "<div>\n\t<a href=\"%s\" target=\"_blank\" onclick=\"return !!AJAXLinkClick(this);\"><h2 class=\"blog-title\">%s</h2></a>\n\t<div><span class=\"author\">%s</span><span class=\"date\">%s</span></div>\n\t<div style=\"clear:both\"></div><div class=\"blog-post\">%s</div>\n<div><a href=\"%s\" target=\"_blank\" onclick=\"return !!AJAXLinkClick(this);\">Read more...</a></div>\n</div>\n";
				$ptemplate = "<div>\n\t<a href=\"%s\" target=\"_blank\" onclick=\"return !!AJAXLinkClick(this);\"><h2 class=\"blog-title\">%s</h2></a>\n\t<div><div style=\"clear:both\"></div><div class=\"blog-post\">%s</div>\n<div><a href=\"%s\" target=\"_blank\" onclick=\"return !!AJAXLinkClick(this);\">Read more...</a></div>\n</div>\n";

				foreach ($data as $datum) {
					if ($datum['score'] > 0) {
						if ($datum['type'] == 'post') {
							$stmt = $pdo->prepare("SELECT * FROM Blog WHERE id = :id AND AccountID = :aid;");
							$stmt->bindValue(':id', $datum['id']);
							$stmt->bindValue(':aid', $a->id);
							$d = DBUtils::ExecutePDOStatement($stmt)[0];
							$author = Member::Estimate($d['acapid']);
							$author = "$author->memberRank $author->memberName";
							$content = substr($d['content'], 0, 497);
							if (!$content) $content = $d['content'];
							else $content .= "...";
							$url = new Link ('blog', '', ['view', $d['id']]);
							$html .= sprintf($btemplate, $url->getURL(), $d['title'], $author, date ('D, d M Y', $d['posted']), $parse->parse($content), $url->getURL());
						} else {
							$content = substr($datum['content'], 0, 497);
							if (!$content) $content = $datum['content'];
							else $content .= "...";
							$url = new Link ('page', '', [$datum['id']]);
							$html .= sprintf($ptemplate, $url->getURL(), $datum['title'], $parse->parse($content), $url->getURL());
						}
					}
				}

				if ($html == '') {
					$html = '<h2>Sorry, nothing matched your search query</h2>';
				}
			} else {
				$html = '<h2>Sorry, nothing matched your search query';
			}

			$form = new AsyncForm('main');
			
			$p = isset($e['parameter']['query']) ? $e['parameter']['query'] : Null;

			$form->
				addField("search", "nolabel", 'text', 'mainSearch', ['placeholder' => 'Search'], $p, 'searchLeft')->
				setSubmitInfo('Search', '', '', 'searchLeft');

			$form->reload = false;			

			$html = $form . $html;

            return [
                'body' => $html,
                'title' => 'Search'
			];
		}

		public static function doPost ($e, $c, $l, $m, $a) {
			return [
                'body' => JSSnippet::PageRedirect('search', [], [
                    'query' => urlencode($e['form-data']['search'])
                ])
            ];
		}
	}