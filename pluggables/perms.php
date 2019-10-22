<?php
	function perms ($e, $c, $l, $m, $a) {
		$pdo = DBUtils::CreateConnection();
		$html = <<<EOD
<h2 class="title">Administration</h2>
<div>
EOD;
		$t = 0;
		if ($a->hasMember($m) || $m->IsRioux) {
			$t++;
			$l1 = new Link("emailselector", "Selected Member and Parent Email addresses");
			$html .= "$l1<br />";
			$t++;
			$l1 = new Link("emailselectorblank", "Unselected Member and Parent Email addresses");
			$html .= "$l1<br />";
			$t++;
			$l1 = new Link("emailselectorparents", "Selected Parent Email addresses");
			$html .= "$l1<br />";

			$l1 = new Link("changepassword", "Change password");
			$html .= "$l1<br />";

			if ($m->hasPermission('PermissionsManagement')) {
				$t++;
				$l1 = new Link("permmgmt", "Manage permissions");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission('PermissionsManagement')) {
				$t++;
				$l1 = new Link("accountreset", "Reset CAPUnit Accounts");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission('RegistryEdit') || $m->hasPermission('Developer')) {
				$t++;
				$l1 = new Link ("regedit", "Site Configuration");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission('FlightAssign')) {
				$t++;
				$l1 = new Link ("flightassign", "Assign flight members");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission("AssignTasks") && $a->paid) {
				$t++;
				$l1 = new Link ("task", "Assign a task");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission("AssignPosition")) {
				$t++;
				$l1 = new Link("tduties", "Assign Temporary Duties");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission('AddTeam') && $a->paid) {
				$t++;
				$l1 = new Link("teamadd", "Add a team");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission('AddEvent') && ($a->paid || $a->getEventCount() < 5)) {
				$t++;
				$l1 = new Link("eventform", "Add an event");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission('FileManagement')) {
				$t++;
				$l1 = new Link("filemanagement", "Manage files");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission("DownloadCAPWATCH")) {
				$t++;
				$l1 = new Link("importcapwatch", "Import CAPWATCH");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission("ViewAttendance")) {
				$t++;
				$l1 = new Link("viewattendance", "View Attendance");
				$html .= "$l1<br />";
			}
			if ($m->hasPermission("DownloadStaffGuide")) {
				$fileid = Registry::get('Administration.CadetStaffGuideID');
				if ($fileid != '') {
					$t++;
					$l1 = new FileDownloader('Download Cadet Staff Guide', $fileid);
					$html .= "$l1<br />";
				}
			}
		}

		$html .= "</div>";
		if ($t == 0) return '';
		return [
			'text' => $html,
			'title' => 'Administration'
		];
	}
