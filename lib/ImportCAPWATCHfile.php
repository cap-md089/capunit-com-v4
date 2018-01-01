<?php
	class VN {
		static $vals = [];
		static $header = [];
		static function g ($id) {
			if (!isset($header[$id])) {
				return null;
			}
			return $vals[$header[$id]];
		}
	}

	function ImportCAPWATCH ($member, $id) {
		if (!function_exists("flog")) {
			function flog ($a) {}
		}

		//Retrieve zip file
		flog ("Retrieving CAPWATCH zip file");
		$fname = $member->getCAPWATCHFile($id, tempnam(sys_get_temp_dir(), 'capwatch'));
		// file_exists: Checks whether a file or directory exists
		if (!file_exists(sys_get_temp_dir()."/capwatch_unpack")){
			// This also checks for directories, weird naming convention
			mkdir(sys_get_temp_dir()."/capwatch_unpack");
		}
		$dir = sys_get_temp_dir()."/capwatch_unpack";

		$pdo = DB_Utils::CreateConnection();

		//Import Member.txt file
		flog ("Processing Member");
		if(!system("unzip -u $fname -d $dir Member.txt")) {
			ErrorMSG::Log("Member unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Member unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/Member.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','NameLast','NameFirst','NameMiddle','Gender','DOB','ORGID',
				'Wing','Unit','Rank','Joined','Expiration','RankDate'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("Member.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the Member.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing Member.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Import_Member WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Member Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Member Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			VN::$vals = $m; 
			
			$stmt = $pdo->prepare("DELETE FROM Import_Member WHERE CAPID=:cid;");
			$stmt->bindValue(':cid', $m[0]);
			if (!$stmt->execute()) {
				ErrorMSG::Log("Member Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "Member Delete NOT ORGID error".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Import_Member VALUES (:cid, :nlast, :nfirst, :nmid, :nsuf, 
				:gen, :birthdte, :prof, :edu, :citi, :orgid, :wing, :unit, :rank, :joindte, :expdte, :orgjoindte, :usrid,
				:moddte, :lsc, :type, :rdte, :reg, :mstat, :pstat, :waiv);");

			$stmt->bindValue(':cid', VN::g('CAPID'));
			$stmt->bindValue(':nlast', VN::g('NameLast'));
			$stmt->bindValue(':nfirst', VN::g('NameFirst'));
			$stmt->bindValue(':nmid', VN::g('NameMiddle'));
			$stmt->bindValue(':nsuf', VN::g('NameSuffix'));
			$stmt->bindValue(':gen', VN::g('Gender'));
			$stmt->bindValue(':birthdte', UtilCollection::GetTimestamp(VN::g('DOB')));
			$stmt->bindValue(':prof', VN::g('Profession'));
			$stmt->bindValue(':edu', VN::g('Education'));
			$stmt->bindValue(':citi', VN::g('Citizen'));
			$stmt->bindValue(':orgid', VN::g('ORGID'));
			$stmt->bindValue(':wing', VN::g('Wing'));
			$stmt->bindValue(':unit', VN::g('Unit'));
			$stmt->bindValue(':rank', VN::g('Rank'));
			$stmt->bindValue(':joindte', UtilCollection::GetTimestamp(VN::g('Joined')));
			$stmt->bindValue(':expdte', UtilCollection::GetTimestamp(VN::g('Expiration')));
			$stmt->bindValue(':orgjoindte', UtilCollection::GetTimestamp(VN::g('Joined')));
			$stmt->bindValue(':usrid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':lsc', VN::g('LSCode'));
			$stmt->bindValue(':type', VN::g('Type'));
			$stmt->bindValue(':rdte', UtilCollection::GetTimestamp(VN::g('RankDate')));
			$stmt->bindValue(':reg', VN::g('Region'));
			$stmt->bindValue(':mstat', VN::g('MbrStatus'));
			$stmt->bindValue(':pstat', VN::g('PicStatus'));
			$stmt->bindValue(':waiv', VN::g('CdtWaiver'));

			if (!$stmt->execute()) {
				ErrorMSG::Log("Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "Member Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/Member.txt");
	
		//Import MbrContact.txt file
		flog ("Processing MbrContact");
		if(!system("unzip -u $fname -d $dir MbrContact.txt")) {
			ErrorMSG::Log("MbrContact unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "MbrContact unzip error: ".$stmt->errorInfo()[2];
		}
		$fmem = fopen("$dir/MbrContact.txt", "r");
		fgetcsv($fmem);
		$members = explode("\n", file_get_contents("$dir/MbrContact.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','Type','Priority','Contact','DoNotContact','ORGID'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("MbrContact.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the MbrContact.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing MbrContact.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Import_MbrContact WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("MbrContact Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "MbrContact Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = fgetcsv($fmem); $i < count($members)-1; $i++, $m = fgetcsv($fmem)) {

			$stmt = $pdo->prepare("DELETE FROM Import_MbrContact WHERE CAPID=:cid AND NOT(ORGID=:orgid);");
			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':orgid', $id);
			if (!$stmt->execute()) {
				ErrorMSG::Log("MbrContact Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "MbrContact Delete NOT ORGID error: ".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Import_MbrContact VALUES (:cid, :ctype, :pri, :contact, :uid, 
				:moddte, :dncontact, :contactname, :orgid);");

			$stmt->bindValue(':cid', VN::g('CAPID'));
			$stmt->bindValue(':ctype', VN::g('Type'));
			$stmt->bindValue(':pri', VN::g('Priority'));
			$stmt->bindValue(':contact', VN::g('Contact'));
			$stmt->bindValue(':uid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':dncontact', VN::g('DoNotContact') == "True" ? 1 : 0);
			$stmt->bindValue(':contactname', VN::g('ContactName'));
			$stmt->bindValue(':orgid', VN::g('ORGID'));

			if (!$stmt->execute()) {
				ErrorMSG::Log("MbrContact Insert ORGID: ".$id." CAPID ".$m[0]." Contact ".$m[3]." ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "MbrContact Insert: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/MbrContact.txt");
	
		//Import CadetDutyPositions.txt file
		flog ("Processing CadetDutyPositions");
		if(!system("unzip -u $fname -d $dir CadetDutyPositions.txt")) {
			ErrorMSG::Log("CadetDutyPositions unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetDutyPositions unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/CadetDutyPositions.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','Duty','FunctArea','Lvl','Asst','ORGID'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("CadetDutyPositions.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the CadetDutyPositions.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing CadetDutyPositions.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_CadetDutyPositions WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("CadetDutyPositions Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetDutyPositions Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {

			$stmt = $pdo->prepare("DELETE FROM Data_CadetDutyPositions WHERE CAPID=:cid AND NOT(ORGID=:orgid);");
			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':orgid', $id);
			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetDutyPositions Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetDutyPositions Delete NOT ORGID error: ".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Data_CadetDutyPositions VALUES (:cid, :duty, :farea, :lvl, :asst, :usrid, 
			:moddte, :orgid);");

			$stmt->bindValue(':cid', VN::g('CAPID'));
			$stmt->bindValue(':duty', VN::g('Duty'));
			$stmt->bindValue(':farea', VN::g('FunctArea'));
			$stmt->bindValue(':lvl', VN::g('Lvl'));
			$stmt->bindValue(':asst', VN::g('Asst'));
			$stmt->bindValue(':usrid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':orgid', VN::g('ORGID'));

			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetDutyPositions Insert ORGID: ".$id." CAPID ".$m[0]." Duty ".$m[1]." ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetDutyPositions Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/CadetDutyPositions.txt");

		//Import DutyPosition.txt file
		flog ("Processing DutyPosition");
		if(!system("unzip -u $fname -d $dir DutyPosition.txt")) {
			ErrorMSG::Log("DutyPosition unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "DutyPosition unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/DutyPosition.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','Duty','FunctArea','Lvl','Asst','ORGID'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("DutyPosition.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the DutyPosition.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing DutyPosition.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_DutyPosition WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("DutyPositions Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "DutyPositions Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {

			$stmt = $pdo->prepare("DELETE FROM Data_DutyPosition WHERE CAPID=:cid AND NOT(ORGID=:orgid);");
			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':orgid', $id);
			if (!$stmt->execute()) {
				ErrorMSG::Log("DutyPositions Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "DutyPositions Delete NOT ORGID error: ".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Data_DutyPosition VALUES (:cid, :duty, :farea, :lvl, :asst, :usrid, 
			:moddte, :orgid);");

			$stmt->bindValue(':cid', VN::g('CAPID'));
			$stmt->bindValue(':duty', VN::g('Duty'));
			$stmt->bindValue(':farea', VN::g('FunctArea'));
			$stmt->bindValue(':lvl', VN::g('Lvl'));
			$stmt->bindValue(':asst', VN::g('Asst'));
			$stmt->bindValue(':usrid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':orgid', VN::g('ORGID'));

			if (!$stmt->execute()) {
				ErrorMSG::Log("DutyPositions Insert ORGID: ".$id." CAPID ".$m[0]." Duty ".$m[1]." ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "DutyPositions Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/DutyPosition.txt");

		//Import CadetAchv.txt file
		flog ("Processing CadetAchv");
		if(!system("unzip -u $fname -d $dir CadetAchv.txt")) {
			ErrorMSG::Log("CadetAchv unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetAchv unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/CadetAchv.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','CadetAchvID','DateCreated'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("CadetAchv.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the CadetAchv.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing CadetAchv.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_CadetAchv WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("CadetAchv Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetAchv Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {

			$stmt = $pdo->prepare("DELETE FROM Data_CadetAchv WHERE CAPID=:cid AND NOT(ORGID=:orgid);");
			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':orgid', $id);
			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetAchv Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetAchv Delete NOT ORGID error: ".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Data_CadetAchv VALUES (:cid, :cachvid, :phfitdte, :lldte, :llscore,
			:aedte, :aescore, :aemod, :atest, :moralldte, :apart, :otherreq, :sdarpt, :uid, :moddte, :frstusr, 
			:createdte, :drilldte, :drillscore, :leadcurr, :oath, :aebookvalue, :milerun, :shuttlerun, :sitreach,
			:pushups, :curlups, :orgid);");

			$stmt->bindValue(':cid', VN::g('CAPID'));
			$stmt->bindValue(':cachvid', VN::g('CadetAchivID'));
			$stmt->bindValue(':phfitdte', UtilCollection::GetTimestamp(VN::g('PhyFitTest')));
			$stmt->bindValue(':lldte', UtilCollection::GetTimestamp(VN::g('LeadLabDateP')));
			$stmt->bindValue(':llscore', VN::g('LeadLabScore'));
			$stmt->bindValue(':aedte', UtilCollection::GetTimestamp(VN::g('AEDateP')));
			$stmt->bindValue(':aescore', VN::g('AEScore'));
			$stmt->bindValue(':aemod', VN::g('AEMod'));
			$stmt->bindValue(':atest', VN::g('AETest'));
			$stmt->bindValue(':moralldte', UtilCollection::GetTimestamp(VN::g('MoralLDateP')));
			$stmt->bindValue(':apart', VN::g('ActivePart') == "True" ? 1 : 0);
			$stmt->bindValue(':otherreq', VN::g('OtherReq') == "True" ? 1 : 0);
			$stmt->bindValue(':sdarpt', VN::g('SDAReport') == "True" ? 1 : 0);
			$stmt->bindValue(':uid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':frstusr', VN::g('FirstUsr'));
			$stmt->bindValue(':createdte', UtilCollection::GetTimestamp(VN::g('DateCreated')));
			$stmt->bindValue(':drilldte', UtilCollection::GetTimestamp(VN::g('DrillDate')));
			$stmt->bindValue(':drillscore', VN::g('DrillScore'));
			$stmt->bindValue(':leadcurr', VN::g('LeadCurr'));
			$stmt->bindValue(':oath', VN::g('CadetOath') == "True" ? 1 : 0);
			$stmt->bindValue(':aebookvalue', VN::g('AEBookValue'));
			$stmt->bindValue(':milerun', VN::g('MileRun'));
			$stmt->bindValue(':shuttlerun', VN::g('ShuttleRun'));
			$stmt->bindValue(':sitreach', VN::g('SitAndReach'));
			$stmt->bindValue(':pushups', VN::g('PushUps'));
			$stmt->bindValue(':curlups', VN::g('CurlUps'));
			$stmt->bindValue(':orgid', $id);

			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetAchv Insert ORGID: ".$id." CAPID ".$m[0]." Achv ".$m[1]." ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetAchievement Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/CadetAchv.txt");

		//Import CadetAchvAprs.txt file
		flog ("Processing CadetAchvAprs");
		if(!system("unzip -u $fname -d $dir CadetAchvAprs.txt")) {
			ErrorMSG::Log("CadetAchvAprs unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetAchvAprs unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/CadetAchvAprs.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','CadetAchvID','Status','AprCAPID','DspReason'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("CadetAchvAprs.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the CadetAchvAprs.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing CadetAchvAprs.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_CadetAchvAprs WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("CadetAchvAprs Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetAchvAprs Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {

			$stmt = $pdo->prepare("DELETE FROM Data_CadetAchvAprs WHERE CAPID=:cid AND NOT(ORGID=:orgid);");
			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':orgid', $id);
			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetAchvAprs Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetAchvAprs Delete NOT ORGID error: ".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Data_CadetAchvAprs VALUES (:cid, :cachvid, :status, :aprcid,
			:dspreason, :awardno, :JRWaiver, :uid, :moddte, :firstusr, :createdte, :printedCert, :orgid);");

			$stmt->bindValue(':cid', VN::g('CAPID'));
			$stmt->bindValue(':cachvid', VN::g('CadetAchvID'));
			$stmt->bindValue(':status', VN::g('Status'));
			$stmt->bindValue(':aprcid', VN::g('AprCAPID'));
			$stmt->bindValue(':dspreason', VN::g('DspReason'));
			$stmt->bindValue(':awardno', VN::g('AwardNo'));
			$stmt->bindValue(':JRWaiver', VN::g('JROTCWaiver') == "True" ? 1 : 0);
			$stmt->bindValue(':uid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':firstusr', VN::g('FirstUsr'));
			$stmt->bindValue(':createdte', UtilCollection::GetTimestamp(VN::g('DateCreated')));
			$stmt->bindValue(':printedCert', VN::g('PrintedCert'));
			$stmt->bindValue(':orgid', $id);

			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetAchvAprs Insert ORGID: ".$id." CAPID ".$m[0]." Achv ".$m[1]." ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetAchvAprs Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/CadetAchvAprs.txt");

		//Import Organization.txt file
		flog ("Processing Organization");
		if(!system("unzip -u $fname -d $dir Organization.txt")) {
			ErrorMSG::Log("Organization unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Organization unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/Organization.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['ORGID','Region','Wing','Unit','NextLevel','Name','Type','Status','Scope'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("Organization.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the Organization.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing Organization.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_Organization;");
		if (!$stmt->execute()) {
			ErrorMSG::Log("Organization Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Organization Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			$stmt = $pdo->prepare("INSERT INTO Data_Organization VALUES (:orgid, :region, :wing, :unit, 
			:nextlevel, :uname, :utype, :charterdte, :status, :scope, :uid, :moddte, :firstusr, :createdte, 
			:recvddte, :orgnotes);");

			$stmt->bindValue(':orgid', VN::g('ORGID'));
			$stmt->bindValue(':region', VN::g('Region'));
			$stmt->bindValue(':wing', VN::g('Wing'));
			$stmt->bindValue(':unit', VN::g('Unit'));
			$stmt->bindValue(':nextlevel', VN::g('NextLevel'));
			$stmt->bindValue(':uname', VN::g('Name'));
			$stmt->bindValue(':utype', VN::g('Type'));
			$stmt->bindValue(':charterdte', UtilCollection::GetTimestamp(VN::g('DateChartered')));
			$stmt->bindValue(':status', VN::g('Status'));
			$stmt->bindValue(':scope', VN::g('Scope'));
			$stmt->bindValue(':uid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
			$stmt->bindValue(':firstusr', VN::g('FirstUsr'));
			$stmt->bindValue(':createdte', UtilCollection::GetTimestamp(VN::g('DateCreated')));
			$stmt->bindValue(':recvddte', UtilCollection::GetTimestamp(VN::g('DateReceived')));
			$stmt->bindValue(':orgnotes', VN::g('OrgNotes'));

			if (!$stmt->execute()) {
				ErrorMSG::Log("Organization Insert ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "Organization Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/Organization.txt");

		//Import OrgAddresses.txt file
		flog ("Processing Organization Addresses");
		if(!system("unzip -u $fname -d $dir OrgAddresses.txt")) {
			ErrorMSG::Log("OrgAddresses unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "OrgAddresses unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/OrgAddresses.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['ORGID','Type','Priority','Addr1','Addr2','City','State','Zip'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("OrgAddresses.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the OrgAddresses.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing OrgAddresses.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_OrgAddresses;");
		if (!$stmt->execute()) {
			ErrorMSG::Log("OrgAddresses Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "OrgAddresses Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			$stmt = $pdo->prepare("INSERT INTO Data_OrgAddresses VALUES (:orgid, :wing, :unit, :type, :pri,
			:addr1, :addr2, :city, :state, :zip, :lat, :long, :usrid, :moddte);");

			$stmt->bindValue(':orgid', VN::g('ORGID'));
			$stmt->bindValue(':wing', VN::g('Wing'));
			$stmt->bindValue(':unit', VN::g('Unit'));
			$stmt->bindValue(':type', VN::g('Type'));
			$stmt->bindValue(':pri', VN::g('Priority'));
			$stmt->bindValue(':addr1', VN::g('Addr1'));
			$stmt->bindValue(':addr2', VN::g('Addr2'));
			$stmt->bindValue(':city', VN::g('City'));
			$stmt->bindValue(':state', VN::g('State'));
			$stmt->bindValue(':zip', VN::g('Zip'));
			$stmt->bindValue(':lat', VN::g('Latitude'));
			$stmt->bindValue(':long', VN::g('Longitude'));
			$stmt->bindValue(':usrid', VN::g('UsrID'));
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));

			if (!$stmt->execute()) {
				ErrorMSG::Log("OrgAddresses Insert ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "OrgAddresses Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/OrgAddresses.txt");

		//Import OrgContacts.txt file
		flog ("Processing Organization Contacts");
		if(!system("unzip -u $fname -d $dir OrgContact.txt")) {
			ErrorMSG::Log("OrgContact unzip: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "OrgContact unzip error: ".$stmt->errorInfo()[2];
		}
		$members = explode("\n", file_get_contents("$dir/OrgContact.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['ORGID','Type','Priority','Contact'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("OrgContact.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the OrgContact.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing OrgContact.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
		$stmt = $pdo->prepare("DELETE FROM Data_OrgContact;");
		if (!$stmt->execute()) {
			ErrorMSG::Log("OrgContact Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "OrgContact Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			$stmt = $pdo->prepare("INSERT INTO Data_OrgContact VALUES (:orgid, :wing, :unit, :ctype, :pri, 
				:contact, :uid, :dtemod);");

			$stmt->bindValue(':orgid', VN::g('ORGID'));
			$stmt->bindValue(':wing', VN::g('Wing'));
			$stmt->bindValue(':unit', VN::g('Unit'));
			$stmt->bindValue(':ctype', VN::g('Type'));
			$stmt->bindValue(':pri', VN::g('Priority'));
			$stmt->bindValue(':contact', VN::g('Contact'));
			$stmt->bindValue(':uid', VN::g('UsrID'));
			$stmt->bindValue(':dtemod', UtilCollection::GetTimestamp(VN::g('DateMod')));

			if (!$stmt->execute()) {
				ErrorMSG::Log("OrgContact Insert ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "OrgContact Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/OrgContact.txt");

		//Run member update procedure
		//UPDATE existing Member data, leaving newly imported members in Import_Member table
		$stmt = $pdo->prepare("CALL UpdateMemberByUnit(:orgid);");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Stored Procedure Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Update Stored Procedure Failed: ".$stmt->errorInfo()[2];
		}
		// $stmt = $pdo->prepare("CALL UpdateMemberMatch(:orgid);");
		// $stmt->bindValue(':orgid', $id);
		// if (!$stmt->execute()) {
		// 	ErrorMSG::Log("Update Procedure Match Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
		// 	return "Update Procedure Match Failed: ".$stmt->errorInfo()[2];
		// }
		// $stmt = $pdo->prepare("CALL UpdateMemberNoMatch(:orgid);");
		// $stmt->bindValue(':orgid', $id);
		// if (!$stmt->execute()) {
		// 	ErrorMSG::Log("Update Procedure No Match Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
		// 	return "Update Procedure No Match Failed: ".$stmt->errorInfo()[2];
		// }

		//clean up
		unlink("$fname");
		
		return 0;
	}
