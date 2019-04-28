<?php
	define ("USER_REQUIRED", true);

	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];
			$html = "<h3>Welcome, $m->memberRank $m->memberName</h3>";
			$pdo = DB_Utils::CreateConnection();

			$funcs = scandir(BASE_DIR."pluggables");

			$links = [];

			for ($i = 0; $i < count($funcs); $i++) {
				$func = $funcs[$i];
				if ($func == '.' || $func == '..') {continue;}
				$func = explode(".", $func);
				if (count($func) > 2) {continue;}
				$func = $func[0];
				require_once (BASE_DIR."pluggables/".$func.".php");
				$d = $func($e, $c, $l, $m, $a);
				if ($d != '') {
					$html .= "<div id=\"{$func}Section\" class=\"adminsection\">" . $d['text'] . "</div>";
					$links[] = [
						'Type' => 'ref',
						'Target' => "{$func}Section",
						'Text' => $d['title']
					];
				}
			}

			return [
				'title' => 'Administration',
				'body' => [
					'MainBody' => $html,
					'SideNavigation' => UtilCollection::GenerateSideNavigation($links),
					'Breadcrumbs' => UtilCollection::GenerateBreadCrumbs([
						[
							'Text' => 'Home',
							'Target' => '/'
						],
						[
							'Text' => 'Administration',
							'Target' => '/admin'
						]
					])
				]
			];
		}
	}
