<?php
	define ("USER_REQUIRED", true);
	class Output {
		public static function doGet($e, $c, $l, $m, $a) {
			if (!$l || !$m->hasPermission('Developer')) {
				ob_start();
?>
<h2>This is not the page you are looking for</h2>
We are sorry, the page <?php echo ltrim(explode("?", $_SERVER['REQUEST_URI'])[0], '/'); ?> does not exist.<br />
<a href="#" onclick="history.go(-1);">Go back a page</a>.
<?php
				return [
					'body' => ob_get_clean()
				];
			}

			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare("select id, timestamp, context, enumber, errname, message, badfile, badline, remarks from ".DB_TABLES['ErrorMessages']." where id in (select min(id) from ".DB_TABLES['ErrorMessages']." where resolved = 0 and remarks is not null group by message, badfile, badline);");
			$data = DBUtils::ExecutePDOStatement($stmt, true);
			if (count($data) == 0) {
				$stmt = $pdo->prepare("select id, timestamp, context, enumber, errname, message, badfile, badline, remarks from ".DB_TABLES['ErrorMessages']." where id in (select min(id) from ".DB_TABLES['ErrorMessages']." where resolved = 0 group by message, badfile, badline);");
				$data = DB_Utils::ExecutePDOStatement($stmt, true);
			}
			$html = '';
	
			$stmt = $pdo->prepare("select min(id) as id, count(*) as c from ".DB_TABLES['ErrorMessages']." where resolved = 0 group by message, badfile, badline;");
			$counts = DBUtils::ExecutePDOStatement($stmt, true);

			$butt = new AsyncButton ('errremark', 'Issue resolved?', 'reload', 'rightFloat');

			$links = [];

			foreach ($data as $datum) {
				$stmt = $pdo->prepare("select capid from ".DB_TABLES['ErrorMessages']." where message = :msg and resolved = 0;");
				$stmt->bindValue(':msg', $datum['message']);
				$cdata = DBUtils::ExecutePDOStatement($stmt);
				$ncdata = [];
				foreach ($cdata as $c) {
					if (array_search($c['capid'], $ncdata)) continue;
					if ($c['capid'] == '') continue;
					$ncdata[] = $c['capid'];
				}
				$ncdata = array_unique($ncdata);
				asort($ncdata);
				$capids = '';
				foreach ($ncdata as $capid) {
					if ($capid == '0' || !isset($capid) || $capid == '') continue;
					$mem = Member::Estimate($capid);
					if ($mem) {
						$capids .= "$capid: $mem->RankName, ";
					} else {
						$capids .= "$capid, ";
					}
				}
				$amount = 0;
				foreach ($counts as $count) {
					if ($count['id'] == $datum['id']) {
						$amount = $count['c'];
					}
				}
				$capids = rtrim($capids, ', ');
				$butth = $butt->getHtml($datum['id']);
				$id = $datum['id'];
				$time = date('D, d M Y H:i:s', $datum['timestamp']);
				$details = $datum['context'];
				$enumber = $datum['enumber'];
				$errname = $datum['errname'];
				$message = $datum['message'];
				$badfile = $datum['badfile'];
				$badline = $datum['badline'];
				$remark = $datum['remarks'];
				$safemsg = urlencode($message);
				$html .= <<<EOD
<div style="clear:both">
<h2 class="title" style="border-bottom: 1px solid #2b357b" id="error$id">Issue #$id (Occurred $amount times) $butth</h4>
<section>
Time: $time<br />
Error type: $enumber ({$errname})<br />
Error: $message (<a target="_blank" href="https://google.com/search?q=$safemsg">Google it</a>)<br />
File: {$badfile}:{$badline}<br />
People experiencing this problem:<br />
<p style="margin: 15px">
$capids
</p>
User remarks:<br />
<p style="margin: 15px">
$remark
</p>
</section>
<section style="overflow:scroll; max-height: 700px">
<xmp>
$details
</xmp>
</section>
</div>
EOD;
				$file = explode('/', implode('/', explode('\\', $badfile)));
				$file = $file[count($file)-1];
				$links[] = [
					'Type' => 'ref',
					'Target' => "error$id",
					'Text' => "Error #$id: $file:$badline"
				];
			}

$body = '';
// $body = "<pre>".implode("\n",
// 	array_slice(
// 		explode("\n",
// 			file_get_contents("/var/log/".
// 				(AWS_SERVER?"httpd/md089_ssl_error_log":"apache2/error.log")
// 			)
// 		), -10, 10
// 	)
// );
// $body = '<h2 class="title">Error Log</h2>'.$body;

			if (count($data) == 0) {
				$html = <<<EOD
<h2 class="title">No errors!</h2>
EOD;
			}

			return [
				'body' => [
					'MainBody' => $body.$html,
					'SideNavigation' => UtilCollection::GenerateSideNavigation($links),
					'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						],
						[
							'Text' => 'Administration',
							'Target' => '/admin'
						],
						[
							'Text' => 'Errors',
							'Target' => '/errremark'
						]
					])
				],
				'title' => 'Errors'
			];
		}

		public static function doPost ($e, $c, $l, $m) {
			$pdo = DB_Utils::CreateConnection();
			$stmt = $pdo->prepare("UPDATE ".DB_TABLES['ErrorMessages']." SET remarks=:remark WHERE id=:id;");
			$stmt->bindValue(':remark',$e['raw']['remarks']);
			$stmt->bindValue(':id', $e['raw']['id']);
			DBUtils::ExecutePDOStatement($stmt);
			return JSSnippet::$PageReload;
		}

		public static function doPut ($e, $c, $l, $m) {
			if ($l && $m->perms['Developer'] == 1) {
				$pdo = DB_Utils::CreateConnection();
				$stmt = $pdo->prepare("SELECT message, badfile, badline from ".DB_TABLES['ErrorMessages']." where id = :id");
				$stmt->bindValue(":id", $e['parameter']['data']);
				$data = DBUtils::ExecutePDOStatement($stmt);
				$stmt = $pdo->prepare("UPDATE ".DB_TABLES['ErrorMessages']." SET resolved=1 WHERE message=:msg, badfile=:file, badline=:line;");
				$stmt->bindValue(':msg', $data[0]['message']);
				$stmt->bindValue(':file', $data[0]['badfile']);
				$stmt->bindValue(':line', $data[0]['badline']);
				return $stmt->execute() ? 'Issue resolved' : 'Database issue';
			}
		}
	}
?>
