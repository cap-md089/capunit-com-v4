<?php
	function ImportCAPWATCH ($member, $id) {
		if (!function_exists("flog")) {
			function flog ($a) {}
		}

		flog ("Retrieving CAPWATCH text file");
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
		$stmt = $pdo->prepare("DELETE FROM Import_Member WHERE ORGID=:orgid;");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Member Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Member Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {

			$stmt = $pdo->prepare("DELETE FROM Import_Member WHERE CAPID=:cid;");
			$stmt->bindValue(':cid', $m[0]);
			if (!$stmt->execute()) {
				ErrorMSG::Log("Member Delete NOT ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "Member Delete NOT ORGID error".$stmt->errorInfo()[2];
			}

			$stmt = $pdo->prepare("INSERT INTO Import_Member VALUES (:cid, :nlast, :nfirst, :nmid, :nsuf, 
				:gen, :birthdte, :prof, :edu, :citi, :orgid, :wing, :unit, :rank, :joindte, :expdte, :orgjoindte, :usrid,
				:moddte, :lsc, :type, :rdte, :reg, :mstat, :pstat, :waiv);");

			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':nlast', $m[2]);
			$stmt->bindValue(':nfirst', $m[3]);
			$stmt->bindValue(':nmid', $m[4]);
			$stmt->bindValue(':nsuf', $m[5]);
			$stmt->bindValue(':gen', $m[6]);
			$stmt->bindValue(':birthdte', UtilCollection::GetTimestamp($m[7]));
			$stmt->bindValue(':prof', $m[8]);
			$stmt->bindValue(':edu', $m[9]);
			$stmt->bindValue(':citi', $m[10]);
			$stmt->bindValue(':orgid', $m[11]);
			$stmt->bindValue(':wing', $m[12]);
			$stmt->bindValue(':unit', $m[13]);
			$stmt->bindValue(':rank', $m[14]);
			$stmt->bindValue(':joindte', UtilCollection::GetTimestamp($m[15]));
			$stmt->bindValue(':expdte', UtilCollection::GetTimestamp($m[16]));
			$stmt->bindValue(':orgjoindte', UtilCollection::GetTimestamp($m[17]));
			$stmt->bindValue(':usrid', $m[18]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[19]));
			$stmt->bindValue(':lsc', $m[20]);
			$stmt->bindValue(':type', $m[21]);
			$stmt->bindValue(':rdte', UtilCollection::GetTimestamp($m[22]));
			$stmt->bindValue(':reg', $m[23]);
			$stmt->bindValue(':mstat', $m[24]);
			$stmt->bindValue(':pstat', $m[25]);
			$stmt->bindValue(':waiv', $m[27]);

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

			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':ctype', $m[1]);
			$stmt->bindValue(':pri', $m[2]);
			$stmt->bindValue(':contact', $m[3]);
			$stmt->bindValue(':uid', $m[4]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[5]));
			$stmt->bindValue(':dncontact', $m[6] == "True" ? 1 : 0);
			$stmt->bindValue(':contactname', $m[7]);
			$stmt->bindValue(':orgid', $id);

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

			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':duty', $m[1]);
			$stmt->bindValue(':farea', $m[2]);
			$stmt->bindValue(':lvl', $m[3]);
			$stmt->bindValue(':asst', $m[4]);
			$stmt->bindValue(':usrid', $m[5]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[6]));
			$stmt->bindValue(':orgid', $m[7]);

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

			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':duty', $m[1]);
			$stmt->bindValue(':farea', $m[2]);
			$stmt->bindValue(':lvl', $m[3]);
			$stmt->bindValue(':asst', $m[4]);
			$stmt->bindValue(':usrid', $m[5]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[6]));
			$stmt->bindValue(':orgid', $m[7]);

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

			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':cachvid', $m[1]);
			$stmt->bindValue(':phfitdte', UtilCollection::GetTimestamp($m[2]));
			$stmt->bindValue(':lldte', UtilCollection::GetTimestamp($m[3]));
			$stmt->bindValue(':llscore', $m[4]);
			$stmt->bindValue(':aedte', UtilCollection::GetTimestamp($m[5]));
			$stmt->bindValue(':aescore', $m[6]);
			$stmt->bindValue(':aemod', $m[7]);
			$stmt->bindValue(':atest', $m[8]);
			$stmt->bindValue(':moralldte', UtilCollection::GetTimestamp($m[9]));
			$stmt->bindValue(':apart', $m[10] == "True" ? 1 : 0);
			$stmt->bindValue(':otherreq', $m[11] == "True" ? 1 : 0);
			$stmt->bindValue(':sdarpt', $m[12] == "True" ? 1 : 0);
			$stmt->bindValue(':uid', $m[13]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[14]));
			$stmt->bindValue(':frstusr', $m[15]);
			$stmt->bindValue(':createdte', UtilCollection::GetTimestamp($m[16]));
			$stmt->bindValue(':drilldte', UtilCollection::GetTimestamp($m[17]));
			$stmt->bindValue(':drillscore', $m[18]);
			$stmt->bindValue(':leadcurr', $m[19]);
			$stmt->bindValue(':oath', $m[20] == "True" ? 1 : 0);
			$stmt->bindValue(':aebookvalue', $m[21]);
			$stmt->bindValue(':milerun', $m[22]);
			$stmt->bindValue(':shuttlerun', $m[23]);
			$stmt->bindValue(':sitreach', $m[24]);
			$stmt->bindValue(':pushups', $m[25]);
			$stmt->bindValue(':curlups', $m[26]);
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

			$stmt->bindValue(':cid', $m[0]);
			$stmt->bindValue(':cachvid', $m[1]);
			$stmt->bindValue(':status', $m[2]);
			$stmt->bindValue(':aprcid', $m[3]);
			$stmt->bindValue(':dspreason', $m[4]);
			$stmt->bindValue(':awardno', $m[5]);
			$stmt->bindValue(':JRWaiver', $m[6] == "True" ? 1 : 0);
			$stmt->bindValue(':uid', $m[7]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[8]));
			$stmt->bindValue(':firstusr', $m[9]);
			$stmt->bindValue(':createdte', UtilCollection::GetTimestamp($m[10]));
			$stmt->bindValue(':printedCert', $m[11]);
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
		$stmt = $pdo->prepare("DELETE FROM Data_Organization;");
		if (!$stmt->execute()) {
			ErrorMSG::Log("Organization Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Organization Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			$stmt = $pdo->prepare("INSERT INTO Data_Organization VALUES (:orgid, :region, :wing, :unit, 
			:nextlevel, :uname, :utype, :charterdte, :status, :scope, :uid, :moddte, :firstusr, :createdte, 
			:recvddte, :orgnotes);");

			$stmt->bindValue(':orgid', $m[0]);
			$stmt->bindValue(':region', $m[1]);
			$stmt->bindValue(':wing', $m[2]);
			$stmt->bindValue(':unit', $m[3]);
			$stmt->bindValue(':nextlevel', $m[4]);
			$stmt->bindValue(':uname', $m[5]);
			$stmt->bindValue(':utype', $m[6]);
			$stmt->bindValue(':charterdte', UtilCollection::GetTimestamp($m[7]));
			$stmt->bindValue(':status', $m[8]);
			$stmt->bindValue(':scope', $m[9]);
			$stmt->bindValue(':uid', $m[10]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[11]));
			$stmt->bindValue(':firstusr', $m[12]);
			$stmt->bindValue(':createdte', UtilCollection::GetTimestamp($m[13]));
			$stmt->bindValue(':recvddte', UtilCollection::GetTimestamp($m[14]));
			$stmt->bindValue(':orgnotes', $m[15]);

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
		$stmt = $pdo->prepare("DELETE FROM Data_OrgAddresses;");
		if (!$stmt->execute()) {
			ErrorMSG::Log("OrgAddresses Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "OrgAddresses Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			$stmt = $pdo->prepare("INSERT INTO Data_OrgAddresses VALUES (:orgid, :wing, :unit, :type, :pri,
			:addr1, :addr2, :city, :state, :zip, :lat, :long, :usrid, :moddte);");

			$stmt->bindValue(':orgid', $m[0]);
			$stmt->bindValue(':wing', $m[1]);
			$stmt->bindValue(':unit', $m[2]);
			$stmt->bindValue(':type', $m[3]);
			$stmt->bindValue(':pri', $m[4]);
			$stmt->bindValue(':addr1', $m[5]);
			$stmt->bindValue(':addr2', $m[6]);
			$stmt->bindValue(':city', $m[7]);
			$stmt->bindValue(':state', $m[8]);
			$stmt->bindValue(':zip', $m[9]);
			$stmt->bindValue(':lat', $m[10]);
			$stmt->bindValue(':long', $m[11]);
			$stmt->bindValue(':usrid', $m[12]);
			$stmt->bindValue(':moddte', UtilCollection::GetTimestamp($m[13]));

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
		$stmt = $pdo->prepare("DELETE FROM Data_OrgContact;");
		if (!$stmt->execute()) {
			ErrorMSG::Log("OrgContact Delete ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "OrgContact Delete ORGID error: ".$stmt->errorInfo()[2];
		}
		for ($i = 1, $m = str_getcsv($members[1]); $i < count($members)-1; $i++, $m = str_getcsv($members[$i])) {
			$stmt = $pdo->prepare("INSERT INTO Data_OrgContact VALUES (:orgid, :wing, :unit, :ctype, :pri, 
				:contact, :uid, :dtemod);");

			$stmt->bindValue(':orgid', $m[0]);
			$stmt->bindValue(':wing', $m[1]);
			$stmt->bindValue(':unit', $m[2]);
			$stmt->bindValue(':ctype', $m[3]);
			$stmt->bindValue(':pri', $m[4]);
			$stmt->bindValue(':contact', $m[5]);
			$stmt->bindValue(':uid', $m[6]);
			$stmt->bindValue(':dtemod', UtilCollection::GetTimestamp($m[7]));

			if (!$stmt->execute()) {
				ErrorMSG::Log("OrgContact Insert ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
				return "OrgContact Insert error: ".$stmt->errorInfo()[2];
			}
		}
		unlink("$dir/OrgContact.txt");

		//Run member update procedure
		//UPDATE existing Member data, leaving newly imported members in Import_Member table
		$stmt = $pdo->prepare("CALL UpdateMemberExistingMember(:orgid);");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Procedure Existing Member Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Update Procedure Existing Member Failed: ".$stmt->errorInfo()[2];
		}
		$stmt = $pdo->prepare("CALL UpdateMemberMatch(:orgid);");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Procedure Match Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Update Procedure Match Failed: ".$stmt->errorInfo()[2];
		}
		$stmt = $pdo->prepare("CALL UpdateMemberNoMatch(:orgid);");
		$stmt->bindValue(':orgid', $id);
		if (!$stmt->execute()) {
			ErrorMSG::Log("Update Procedure No Match Failed. ORGID: ".$id.", Member: ".$member->capid.", ".$member->RankName.", fname: ".$fname.": ".$stmt->errorInfo()[2],"ImportCAPWATCHfile.php");
			return "Update Procedure No Match Failed: ".$stmt->errorInfo()[2];
		}

		//clean up
		unlink("$fname");
		
		return 0;
	}
