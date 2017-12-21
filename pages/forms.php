<?php
	function generatePromotionReport () {
		$pdo = DB_Utils::CreateConnection();

		$stmt = $pdo->prepare('SELECT CdtAchvEnum.Grade AS CurrentGrade, qryCurrentApprovalWithEligibility.FullName, qryCurrentApprovalWithEligibility.CadetAchvID, CdtAchvEnum_1.AchvName, CdtAchvEnum_1.Grade AS NextGrade, IIf([qryCurrentApprovalWithEligibility].[capid]>10000,[qryCurrentApprovalWithEligibility].[capid],"") AS CAPID, qryCurrentApprovalWithEligibility.Eligible, IIf([eligible]<Now(),"E",IIf(([eligible]-30)<Now(),"<","*")) AS Eligibility, qryCurrentApprovalWithEligibility.Expiration, IIf(([expiration]-30)<Now(),"Y","") AS Expire30, IIf([qryCurrentApprovalWithEligibility].[CAPID]<100000,"True",[qryCurrentApprovalWithEligibility].[ActivePart]) AS ActivePart, IIf([PhyFitTest]=#1/1/1900#,IIf([CdtWaiver]="PTWaiver",[CdtWaiver]),[PhyFitTest]) AS Phys, IIf([LeadershipTest]="Yes",IIf([LeadLabDateP]=#1/1/1900#,"",[LeadLabDateP]),"N/A") AS LeadLab, IIf([Drill]<>"No",IIf([DrillDate]=#1/1/1900#,[Drill],[DrillDate]),"N/A") AS DrillTest, IIf([drilldate]=#1/1/1900#,[tblAchievementRequirements].[DrillItems],"") AS DrillItems, IIf([CadetOath]="True","Yes","") AS OathTest, IIf([CharacterDevelopment]="Yes",IIf([MoralLDateP]=#1/1/1900#,"",[MoralLDateP]),"N/A") AS CharDev, IIf([Aerospace]="Yes",IIf([AEDateP]=#1/1/1900#,"",[AEDateP]),"N/A") AS AeroEd, IIf([StaffDutyAnalysis]="Yes",IIf([SDAReport]="False","","Yes"),"N/A") AS SDA, IIf([Mentor]="Yes",IIf([OtherReq]="False","","Yes"),"N/A") AS CMentor, IIf([LeadLab]="","Y",IIf(IsNull([LeadLab]),"Y",IIf([aeroed]="","Y",IIf([SDA]="","Y","")))) AS Individual, IIf(IsNull([Phys]),"Y",IIf([OathTest]="","Y",IIf([CharDev]="","Y",IIf([cmentor]="","Y",IIf(Len([DrillTest])=1,"Y",""))))) AS Meeting, tblAssignments.Flight, IIf([qryMemberFullName].[FullName]=",","",[qryMemberFullName].[FullName]) AS MentorName, qryMemberFullName.Flight AS MentorFlight, qryCurrentApprovalWithEligibility.Status, IIf(Not (IsNull([tblAssignments].[Flight])),IIf(Not (IsNull([qryMemberFullName].[Flight])),IIf([tblAssignments].[Flight]<>[qryMemberFullName].[Flight],"No","Yes"),""),"") AS MentorMatch, CadetDutyTitlesRanked.Duty, IIf(Len([MentorName])>0,[MentorName],IIf([qryCadetDutyRanked].[MinOfRank]>0,IIf([MbrStatus]="EXPIRED","",IIf(Left([tblassignments.flight],1)="S",IIf(Left([duty],10)="Cadet Flig","",[Duty]),IIf(Left([duty],10)="Cadet Flig",Left([tblassignments.flight],1) & " " & [Duty],[Duty]))))) AS MentorOrDuty, qryCadetDutyRanked.MinOfRank, qryCurrentApprovalWithEligibility.MbrStatus
FROM (((((((((qryCurrentApprovalWithEligibility LEFT JOIN tblAchievementRequirements ON qryCurrentApprovalWithEligibility.CadetAchvID = tblAchievementRequirements.CadetAchvID) LEFT JOIN CdtAchvEnum ON qryCurrentApprovalWithEligibility.GradeID = CdtAchvEnum.CadetAchvID) LEFT JOIN CdtAchvEnum AS CdtAchvEnum_1 ON qryCurrentApprovalWithEligibility.CadetAchvID = CdtAchvEnum_1.CadetAchvID) LEFT JOIN qryPTWaiver ON qryCurrentApprovalWithEligibility.CAPID = qryPTWaiver.CAPID) LEFT JOIN tblAssignments ON qryCurrentApprovalWithEligibility.CAPID = tblAssignments.CAPID) LEFT JOIN CadetAchv ON (qryCurrentApprovalWithEligibility.CurrentAchievement = CadetAchv.CadetAchvID) AND (qryCurrentApprovalWithEligibility.CAPID = CadetAchv.CAPID)) LEFT JOIN qryUnionMentorAssignments ON qryCurrentApprovalWithEligibility.CAPID = qryUnionMentorAssignments.CAPID) LEFT JOIN qryMemberFullName ON qryUnionMentorAssignments.MentorID = qryMemberFullName.CAPID) LEFT JOIN qryCadetDutyRanked ON qryCurrentApprovalWithEligibility.CAPID = qryCadetDutyRanked.CAPID) LEFT JOIN CadetDutyTitlesRanked ON qryCadetDutyRanked.MinOfRank = CadetDutyTitlesRanked.Rank
ORDER BY qryCurrentApprovalWithEligibility.FullName;');
	}

	function generateMusterSheet () {

	}

	function generatePTSheet () {

	}

	class Output {
		public static function doGet ($e, $c, $l, $m) {

		}

		public static function doPost ($e, $c, $l, $m) {

		}
	}