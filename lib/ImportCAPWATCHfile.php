<?php
require_once (BASE_DIR . "lib/logger.php");
require_once (BASE_DIR . "lib/MyCURL.php");

	class VN {
		static $vals = [];
		static $header = [];
		static function g ($id) {
			if (!isset(self::$header[$id])) {
				return null;
			}
			return self::$vals[self::$header[$id]];
		}
	}

	function ImportCAPWATCH ($member, $id, $pw, $importOrgs) {
$logger = New Logger ("ImportCAPWATCH");

		if (!function_exists("flog")) {
			function flog ($a) {}
		}

		flog ("Retrieving CAPWATCH zip file");

		$url = "https://www.capnhq.gov/CAP.CapWatchAPI.Web/api/cw";
		$payload = array (
				"unitOnly" => "0",
				"ORGID" => $id
		);
		$enc = base64_encode($member->uname.":".$pw);

		$fields_string = '';
		foreach ($payload as $key=>$value) {
				$fields_string .= urlencode($key)."=". urlencode($value) . "&";
		}
		$fields_string = rtrim($fields_string, "&");

		$ch = new MyCURL();
		$ch-> setOpts (array (
				CURLOPT_HTTPHEADER => [
						'Content-Type: application/json',
						'Authorization: Basic '.$enc
				],
		), false);

		$data = $ch->download("$url?$fields_string");
		$_ = $data["body"];
		$fname = BASE_DIR . "temp/CAPWATCH-".$member->uname."-".$id."-".date('Ymd').".zip";
		if (file_exists($fname)) { unlink($fname); }

		file_put_contents($fname, $_);

		if(filesize($fname) == 0) {
				return " CAPWATCH file download failed. Check password: should be eServices password";
		}

//NEW import
		//base 64 encode CAPID:password
		//add header "Authorization" with value "Basic " + base64
		// submit to:  https://www.capnhq.gov/CAP.CapWatchAPI.Web/api/cw?ORGID=916&unitOnly=0
		//save file and process

		//Retrieve zip file
//		$fname = $member->getCAPWATCHFile($id, tempnam(sys_get_temp_dir(), 'capwatch'));
		//$fname = "/tmp/capwatchaPZo6g";
		// file_exists: Checks whether a file or directory exists
		if (!file_exists(sys_get_temp_dir()."/capwatch_unpack")){
			// This also checks for directories, weird naming convention
			mkdir(sys_get_temp_dir()."/capwatch_unpack");
		}
		$dir = sys_get_temp_dir()."/capwatch_unpack";

		$pdo = DB_Utils::CreateConnection();

		$mytime = time();
		$myid = $member->capid;
		$myrn = $member->RankName;
//$logger->Log("id: ".$id, 8);

		$sqlstmt = "INSERT INTO CAPWATCH_Download_Log (ORGID, Timestamp, CAPID, RankName) ";
		$sqlstmt .= "VALUES (:oid, :ts, :cid, :rn) ";
		$sqlstmt .= "ON DUPLICATE KEY UPDATE Timestamp=:ts, CAPID=:cid, RankName=:rn;";
		$stmt = $pdo->prepare($sqlstmt);
		$stmt->bindValue(':oid', $id);
		$stmt->bindValue(':ts', $mytime);
		$stmt->bindValue(':cid', $myid);
		$stmt->bindValue(':rn', $myrn);
		if (!$stmt->execute()) {
			ErrorMSG::Log("CAPWATCH Download Log ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CAPWATCH Download Log error: ".$stmt->errorInfo()[2];
		}

		//Import Member.txt file
		flog ("Processing Member");
		$last_line=system("unzip -op $fname Member.txt > $dir/$id-$member->capid-Member.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("Member unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "Member unzip error.  Please contact helpdesk@capunit.com";
		}
		$members = explode("\n", file_get_contents("$dir/$id-$member->capid-Member.txt"));
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

			$stmt = $pdo->prepare("REPLACE INTO Import_Member VALUES (:ts, :src, :cid, :nlast, :nfirst, :nmid, :nsuf, 
				:gen, :birthdte, :prof, :edu, :citi, :orgid, :wing, :unit, :rank, :joindte, :expdte, :orgjoindte, :usrid,
				:moddte, :lsc, :type, :rdte, :reg, :mstat, :pstat, :waiv);");

			$stmt->bindValue(':ts', time());
			$stmt->bindValue(':src', "C");
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
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "Member Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-Member.txt");

		//Import MbrContact.txt file
		flog ("Processing MbrContact");
		$last_line=system("unzip -op $fname MbrContact.txt > $dir/$id-$member->capid-MbrContact.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("MbrContact unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "MbrContact unzip error.  Please contact helpdesk@capunit.com";
		}
		$members = explode("\n", file_get_contents("$dir/$id-$member->capid-MbrContact.txt"));
		$titleRow = str_getcsv($members[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CAPID','Type','Priority','Contact','DoNotContact'] as $value) {
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
		for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			VN::$vals = $m;

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
			$stmt->bindValue(':orgid', $id);

			if (!$stmt->execute()) {
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "MbrContact Insert: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-MbrContact.txt");

		//Import CadetDutyPositions.txt file
		flog ("Processing CadetDutyPositions");
		$last_line=system("unzip -op $fname CadetDutyPositions.txt > $dir/$id-$member->capid-CadetDutyPositions.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("CadetDutyPositions unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "CadetDutyPositions unzip error.  Please contact helpdesk@capunit.com";
		}
		$members = explode("\n", file_get_contents("$dir/$id-$member->capid-CadetDutyPositions.txt"));
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
		for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			VN::$vals = $m; 

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
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "CadetDutyPositions Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-CadetDutyPositions.txt");

		//Import DutyPosition.txt file
		flog ("Processing DutyPosition");
		$last_line=system("unzip -op $fname DutyPosition.txt > $dir/$id-$member->capid-DutyPosition.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("DutyPosition unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "DutyPosition unzip error.  Please contact helpdesk@capunit.com";
		}
		$members = explode("\n", file_get_contents("$dir/$id-$member->capid-DutyPosition.txt"));
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
		for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			VN::$vals = $m; 

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
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "DutyPositions Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-DutyPosition.txt");

		//Import CadetAchv.txt file
		flog ("Processing CadetAchv");
		$last_line=system("unzip -op $fname CadetAchv.txt > $dir/$id-$member->capid-CadetAchv.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("CadetAchv unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "CadetAchv unzip error.  Please contact helpdesk@capunit.com";
		}
		$members = explode("\n", file_get_contents("$dir/$id-$member->capid-CadetAchv.txt"));
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
		for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			VN::$vals = $m; 

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
			$stmt->bindValue(':cachvid', VN::g('CadetAchvID'));
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
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "CadetAchievement Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-CadetAchv.txt");

		//Import CadetAchvAprs.txt file
		flog ("Processing CadetAchvAprs");
		$last_line=system("unzip -op $fname CadetAchvAprs.txt > $dir/$id-$member->capid-CadetAchvAprs.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("CadetAchvAprs unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "CadetAchvAprs unzip error.  Please contact helpdesk@capunit.com";
		}
		$members = explode("\n", file_get_contents("$dir/$id-$member->capid-CadetAchvAprs.txt"));
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
		for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			VN::$vals = $m;

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
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "CadetAchvAprs Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-CadetAchvAprs.txt");

		//Import CdtAchvEnum.txt file
		flog ("Processing CdtAchvEnum");
		$last_line=system("unzip -op $fname CdtAchvEnum.txt > $dir/$id-$member->capid-CdtAchvEnum.txt",$retval);
		if($retval > 0) {
			ErrorMSG::Log("CdtAchvEnum unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
			return "CdtAchvEnum unzip error.  Please contact helpdesk@capunit.com";
		}
		$achvs = explode("\n", file_get_contents("$dir/$id-$member->capid-CdtAchvEnum.txt"));
		$titleRow = str_getcsv($achvs[0]);
		$colIDs = array();
		foreach ($titleRow as $k => $v) {
			$colIDs[$v] = $k;
		}
		foreach (['CadetAchvID','CurAwdNo'] as $value) {
			if (!isset($colIDs[$value])) {
				ErrorMSG::Log("CdtAchvEnum.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				$message = "A required column identifier, ".$value.", was not identified as present ";
				$message .= "in the CdtAchvEnum.txt file.  The CAPWATCH import cannot continue and will halt.";
				errorMailer($member, $message);
				return "Error parsing CdtAchvEnum.txt.  Please contact helpdesk@capunit.com";
			}
		}
		VN::$header = $colIDs;
/*		$stmt = $pdo->prepare("DELETE FROM Data_CadetAchvAprs WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("CadetAchvAprs Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "CadetAchvAprs Delete ORGID error: ".$stmt->errorInfo()[2];
		}
*/		for ($i = 1, $m = str_getcsv($achvs[$i]); $i < count($achvs)-1; $i++, $m = str_getcsv($achvs[$i])) {
			VN::$vals = $m;

/*			$stmt = $pdo->prepare("DELETE FROM Data_CadetAchvAprs WHERE CAPID=:cid AND NOT(ORGID=:orgid);");
			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':orgid', $id);
			if (!$stmt->execute()) {
				ErrorMSG::Log("CadetAchvAprs Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "CadetAchvAprs Delete NOT ORGID error: ".$stmt->errorInfo()[2];
			}
*/
			$stmt = $pdo->prepare("UPDATE Data_CdtAchvEnum SET CurAwdNo = :curawdno WHERE CadetAchvID = :cachvid;");

			$stmt->bindValue(':cachvid', (int)VN::g('CadetAchvID'));
			$stmt->bindValue(':curawdno', (int)VN::g('CurAwdNo'));

			if (!$stmt->execute()) {
				$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
				$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
				$message .= VN::g('CAPID')." row: ".$i;
				ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
				return "CdtAchvEnum Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/$id-$member->capid-CdtAchvEnum.txt");


		//only import the organization files if the form checkbox is set
		if($importOrgs == "true") {
			//Import Organization.txt file
			flog ("Processing Organization");
			$last_line=system("unzip -op $fname Organization.txt > $dir/$id-$member->capid-Organization.txt",$retval);
			if($retval > 0) {
				ErrorMSG::Log("Organization unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
				return "Organization unzip error.  Please contact helpdesk@capunit.com";
			}
			$members = explode("\n", file_get_contents("$dir/$id-$member->capid-Organization.txt"));
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
			$stmt = $pdo->prepare("DELETE FROM Data_Organization WHERE ORGID<9000;");
			if (!$stmt->execute()) {
				ErrorMSG::Log("Organization Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "Organization Delete ORGID error: ".$stmt->errorInfo()[2];
			}
			for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
				VN::$vals = $m; 

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
					$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
					$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
					$message .= VN::g('CAPID')." row: ".$i;
					ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
					return "Organization Insert error: ".$stmt->errorInfo()[2];
				}
			}
			unlink("$dir/$id-$member->capid-Organization.txt");

			//Import OrgAddresses.txt file
			flog ("Processing Organization Addresses");
			$last_line=system("unzip -op $fname OrgAddresses.txt > $dir/$id-$member->capid-OrgAddresses.txt",$retval);
			if($retval > 0) {
				ErrorMSG::Log("OrgAddresses unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
				return "OrgAddresses unzip error.  Please contact helpdesk@capunit.com";
			}
			$members = explode("\n", file_get_contents("$dir/$id-$member->capid-OrgAddresses.txt"));
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
			$stmt = $pdo->prepare("DELETE FROM Data_OrgAddresses WHERE ORGID<9000;");
			if (!$stmt->execute()) {
				ErrorMSG::Log("OrgAddresses Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "OrgAddresses Delete ORGID error: ".$stmt->errorInfo()[2];
			}
			for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
				VN::$vals = $m; 

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
					$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
					$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
					$message .= VN::g('CAPID')." row: ".$i;
					ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
					return "OrgAddresses Insert error: ".$stmt->errorInfo()[2];
				}
			}
			unlink("$dir/$id-$member->capid-OrgAddresses.txt");

			//Import OrgContacts.txt file
			flog ("Processing Organization Contacts");
			$last_line=system("unzip -op $fname OrgContact.txt > $dir/$id-$member->capid-OrgContact.txt",$retval);
			if($retval > 0) {
				ErrorMSG::Log("OrgContact unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
				return "OrgContact unzip error.  Please contact helpdesk@capunit.com";
			}
			$members = explode("\n", file_get_contents("$dir/$id-$member->capid-OrgContact.txt"));
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
			$stmt = $pdo->prepare("DELETE FROM Data_OrgContact WHERE ORGID<9000;");
			if (!$stmt->execute()) {
				ErrorMSG::Log("OrgContact Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "OrgContact Delete ORGID error: ".$stmt->errorInfo()[2];
			}
			for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
				VN::$vals = $m; 

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
					$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
					$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
					$message .= VN::g('CAPID')." row: ".$i;
					ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
					return "OrgContact Insert error: ".$stmt->errorInfo()[2];
				}
			}
			unlink("$dir/$id-$member->capid-OrgContact.txt");

			//Import Commanders.txt file
			flog ("Processing Organization Commanders");
			$last_line=system("unzip -op $fname Commanders.txt > $dir/$id-$member->capid-Commanders.txt",$retval);
			if($retval > 0) {
				ErrorMSG::Log("Commanders unzip: ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.", retval: ".$retval,"ImportCAPWATCHfile.php");
				return "Commanders unzip error.  Please contact helpdesk@capunit.com";
			}
			$members = explode("\n", file_get_contents("$dir/$id-$member->capid-Commanders.txt"));
			$titleRow = str_getcsv($members[0]);
			$colIDs = array();
			foreach ($titleRow as $k => $v) {
				$colIDs[$v] = $k;
			}
			foreach (['ORGID','NameLast','NameFirst','NameMiddle','NameSuffix','Rank'] as $value) {
				if (!isset($colIDs[$value])) {
					ErrorMSG::Log("Commanders.txt missing column header:  ".$value.", ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
					$message = "A required column identifier, ".$value.", was not identified as present ";
					$message .= "in the Commanders.txt file.  The CAPWATCH import cannot continue and will halt.";
					errorMailer($member, $message);
					return "Error parsing Commanders.txt.  Please contact helpdesk@capunit.com";
				}
			}
			VN::$header = $colIDs;
			$stmt = $pdo->prepare("DELETE FROM Data_Commanders WHERE ORGID<9000;");
			if (!$stmt->execute()) {
				ErrorMSG::Log("Commanders Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "Commanders Delete ORGID error: ".$stmt->errorInfo()[2];
			}
			for ($i = 1, $m = str_getcsv($members[$i]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
				VN::$vals = $m;

				$stmt = $pdo->prepare("INSERT INTO Data_Commanders VALUES (:orgid, :region, :wing, :unit, 
				:cid, :assigndte, :usrid, :moddte, :namelast, :namefirst, :namemiddle, :namesuffix, :rank);");

				$stmt->bindValue(':orgid', VN::g('ORGID'));
				$stmt->bindValue(':region', VN::g('Region'));
				$stmt->bindValue(':wing', VN::g('Wing'));
				$stmt->bindValue(':unit', VN::g('Unit'));
				$stmt->bindValue(':cid', VN::g('CAPID'));
				$stmt->bindValue(':assigndte', UtilCollection::GetTimestamp(VN::g('DateAsg')));
				$stmt->bindValue(':usrid', VN::g('UsrID'));
				$stmt->bindValue(':moddte', UtilCollection::GetTimestamp(VN::g('DateMod')));
				$stmt->bindValue(':namelast', VN::g('NameLast'));
				$stmt->bindValue(':namefirst', VN::g('NameFirst'));
				$stmt->bindValue(':namemiddle', VN::g('NameMiddle'));
				$stmt->bindValue(':namesuffix', VN::g('NameSuffix'));
				$stmt->bindValue(':rank', VN::g('Rank'));

				if (!$stmt->execute()) {
					$message = "Member Insert ORGID: ".$id." CAPID ".$m[0]." ".$id.", Member: ".$member->capid.", ";
					$message .= $member->RankName.", fname: ".$fname.":  ".$stmt->errorInfo()[2]." CAPID: ";
					$message .= VN::g('CAPID')." row: ".$i;
					ErrorMSG::Log($message,"ImportCAPWATCHfile.php");
					return "Commanders Insert error: ".$stmt->errorInfo()[2];
				}

			}
			unlink("$dir/$id-$member->capid-Commanders.txt");
			//return " Import Orgs";
		}

		//Run member update procedure
		//UPDATE existing Member data, leaving newly imported members in Import_Member table
		$stmt = $pdo->prepare("CALL UpdateMemberByUnit(:orgid);");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Member Stored Procedure Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname,"ImportCAPWATCHfile.php");
			return "Update Member Stored Procedure Failed";
		}
		$defFlight = Registry::Get("Administration.FlightNames.Default");
		$stmt = $pdo->prepare("CALL Flights_Update(:orgid, :defflight);");
		$stmt->bindValue(':orgid', $id);
		$stmt->bindValue(':defflight', $defFlight);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Flights Stored Procedure Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname,"ImportCAPWATCHfile.php");
			return "Update Flights Stored Procedure Failed";
		}
/*
		$sqlin = "INSERT IGNORE INTO Flights (Flights.CAPID, Flights.AccountID, Flights.Flight) ";
		$sqlin .= "SELECT CAPID_To_Account.CAPID, :orgid AS AccountID, :defflight AS Flight FROM ";
		$sqlin .= "CAPID_To_Account WHERE Type=\"CADET\" AND AccountID=:orgid AND CAPID NOT IN ";
		$sqlin .= "(SELECT Flights.CAPID FROM Flights);";

		$stmt = $pdo-prepare($sqlin);
		$stmt->bindValue(':orgid', $id);
		$stmt->bindValue(':defflight', $defFlight);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Flights Query Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname,"ImportCAPWATCHfile.php");
			return "Update Flights Query Failed";
		}
*/

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
		unlink($fname);

		return 0;
	}
