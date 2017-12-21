<?php
	require_once "config.php";
	require_once BASE_DIR . "lib/templates.php";
	ob_start();
?>
<header class="desktop">
	<div class="bars" id="topbarhelper" style="height:0px"></div>
	<div class="bars" id="topbar">
		<div class="aligner">
			<ul>
				<li class="menu-button"><a href="#" id="menu-button"></a></li>
				<li class="head-link"><a href="https://www.capmembers.com" target="_blank" class="headlink">CAP Members.com</a></li>
				<li class="head-link"><a href="https://www.marylandcivilairpatrol.org/" target="_blank" class="headlink">Maryland Wing</a></li>
				<li class="head-link last"><a href="https://www.capnhq.gov/" target="_blank" class="headlink">CAP National Headquarters</a></li>
			</ul>
		</div>
	</div>
	<div id="head">
		<div id="headcontent"></div>
	</div>
	<div class="bars" id="navbarhelper" style="height:0px"></div>
	<div class="bars" id="navbar" style="margin-top:-25px">
		<div class="aligner">
			<ul id="#menu" class="signedout">
				<li>
					<?php
						echo new Link ("", "Home") . "\n";
					?>
				</li>
				<li>
					<?php
						echo new Link ("blog", "News") . "\n";
					?>
				</li>
				<li>
					<?php
						echo new Link("calendar", "Calendar") . "\n";
					?>
				</li>
				<li>
					<?php
						echo new Link("photolibrary", "Photo Library") . "\n";
					?>
				</li>
				<li>
					<?php
						echo new Link("teamlist", "Team List") . "\n";
					?>
				</li>
				<?php
					$pdo = DB_Utils::CreateConnection();
					$stmt = $pdo->prepare("SELECT `name`, `id` FROM `".DB_TABLES['BlogPages']."` WHERE `parentname` = '' AND AccountID = :aid;");
					global $_ACCOUNT;
					$stmt->bindValue(':aid', $_ACCOUNT->id);
					$links = DB_Utils::ExecutePDOStatement($stmt);
					function addPage ($id, $name) {
						if ($id == 'none' || $id == 'hidden') return;
						global $_ACCOUNT;
						$pdo = DBUtils::CreateConnection();
						$html = '<li>';
						$html .= new Link ("page", $name, [$id]);
						$stmt = $pdo->prepare("SELECT `name`, `id` FROM `".DB_TABLES['BlogPages']."` WHERE `parentname` = :pid AND AccountID = :aid;");
						$stmt->bindValue(':pid', $id);
						$stmt->bindValue(':aid', $_ACCOUNT->id);
						$data = DBUtils::ExecutePDOStatement($stmt);
						if (count($data)) {
							$html .= "<ul class=\"submenu\">";
							foreach ($data as $datum) {
								$html .= addPage($datum['id'], $datum['name']);
							}
							$html .= "</ul>";
						}
						return $html . '</li>';
					}
					foreach ($links as $datum) {
						echo addPage($datum['id'], $datum['name']);
					}
				?>
				<li id="signIn_linkli">
					<a href="#" onclick="return false;" id="signIn_link">Sign In</a>
				</li>
				<li id="signOut_linkli">
					<a href="#" onclick="return false;" id="signOut_link">Sign Out</a>
				</li>
				<li>
					<?php
						echo new Link ("admin", "Administration", Null, Null, 'adminlink') . "\n";
					?>
				</li>
				<li class="reload">
					<a onclick="return getHtml(window.location.pathname+window.location.search)" href="#" class="reload">Reload</a>
				</li>
				<li class="top" style="width:140px">
					<a href="#" class="top">Go back up</a>
				</li>
			</ul>
		</div>
		<div id="modal"></div>
	</div>
	<div id="banner"></div>
</header>
<?php
	$form = new AsyncForm ('/signin', 'Sign in', 'hidden', 'signIn');

	$form->
		addField ("name", "CAP ID")->
		addField ("password", "Password", "password")->
		setSubmitInfo ("Log in");

	echo $form;
?>
<?php define ("HEADER_HTML", ob_get_clean()); ?>
