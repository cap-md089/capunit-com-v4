<?php
	function testImportCAPWATCH ($member) {
		$pdo = DB_Utils::CreateConnection();
		flog ("Member required, listing parameter values");
		flog ("-- CAPID: ".$member->uname);
		flog ("-- Name: ".$member->memberName);
		flog ("-- Rank: ".$member->memberRank);
		flog ("-- Best email: ".$member->getBestEmail());
		flog ("-- Best phone: ".$member->getBestPhone());
		flog ("Running ImportCAPWATCH");
		ImportCAPWATCH($member, 916);
	}