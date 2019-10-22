<?php
	require_once (BASE_DIR."lib/general.php");
	require_once (BASE_DIR."lib/Notify.php");


	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}

			if (isset($e['uri'][$e['uribase-index']]) &&
				( ($m->hasPermission('PermissionManagement') && $a->hasMember($m)) || $m->IsRioux )
			) {
				$pdo = DBUtils::CreateConnection();
				$data = $e['uri'][$e['uribase-index']];
				$mem = Member::Estimate($data, true);
				if(!!$mem) {
					$sqlstmt = "SELECT UserID FROM UserAccountInfo WHERE CAPID=:cid";
					$stmt = $pdo->prepare($sqlstmt);
					$stmt->bindValue(':cid', $mem->uname);
					$madata = DBUtils::ExecutePDOStatement($stmt);
					if (count($madata) > 0) {
							$pwsql = "DELETE FROM UserPasswordData WHERE UserID=:uid";
							$stmt = $pdo->prepare($pwsql);
							$stmt->bindValue(':uid', $madata[0]['UserID']);
							$stmt->execute();
							$uasql = "DELETE FROM UserAccountInfo WHERE UserID=:uid";
							$stmt = $pdo->prepare($uasql);
							$stmt->bindValue(':uid', $madata[0]['UserID']);
							$stmt->execute();
					}
					$html = "<br/>Selected member {$mem->RankName} account has been deleted<br/><br/>";
					$html .= (new Link("accountreset", "Refresh Member Account List"));
				} else {
						$html = "<br/>No valid CAPID<br/><br/>".(new Link("accountreset", "Refresh List"));
				}

				return [
					'body' => [
						'MainBody' => $html,
						'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
							[
								'Target' => '/',
								'Text' => 'Home'
							],
							[
								'Target' => '/accountreset',
								'Text' => 'Account Reset'
							]
						])
					]
				];
			}
				
			if (! (($m->hasPermission('PermissionsManagement') && $a->hasMember($m)) || $m->IsRioux )
			)
			{
					return ['error' => 401];
			} else {
					$pdo = DBUtils::CreateConnection();

					$sqlstmt = 'SELECT Data_Member.CAPID, Data_Member.Rank, Data_Member.NameLast, Data_Member.NameFirst,
							Data_Member.Expiration as "Membership Expiration",
							IF(UserAccountInfo.Status, "Yes", "No") as "CAPUnit Account",
							SignInData.LastAccessTime as "Last SignIn"
						FROM Data_Member
						LEFT JOIN UserAccountInfo ON Data_Member.CAPID=UserAccountInfo.CAPID
						LEFT JOIN SignInData ON Data_Member.CAPID=SignInData.CAPID
						WHERE Data_Member.ORGID IN (SELECT UnitId FROM Accounts WHERE AccountID = :aid)
						AND SignInData.AccountID = :aid
						ORDER BY Data_Member.NameLast, Data_Member.NameFirst;';

					$stmt = $pdo->prepare($sqlstmt);
					$stmt->bindValue(':aid', $a->id);
					$madata = DBUtils::ExecutePDOStatement($stmt);
					$targetexpiring = time()+(60*60*24*30); //one month from now
					$targetexpired = time(); //now
					if (count($madata) > 0) {
						$html = new DetailedListPlus("CAPUnit.com Member Accounts for ".$a->id);
						foreach ($madata as $ma) {
								$color = '';
								if(
										($ma['Membership Expiration'] < $targetexpiring) &&
										($ma['Membership Expiration'] >= $targetexpired)	) 
								{ $color = 'color:orange'; 
								} else if ( $ma['Membership Expiration'] < $targetexpired ) 
								{ $color = 'color:red'; }
								$title = "<span style=\"$color\">{$ma['NameLast']}, {$ma['NameFirst']} {$ma['Rank']}</span>";
								if($color == 'color:red') {
										$exp = 'CAP Membership Expired on '.date('n/j/Y', $ma['Membership Expiration']);
								} else {
										$exp = 'CAP Membership Expires on '.date('n/j/Y', $ma['Membership Expiration']);
								}
								if($ma['CAPUnit Account'] == "Yes") {
										$exp = 'Last SignIn: '.(date("n/j/Y", $ma['Last SignIn']))."<br/>".$exp;
								}
								if($ma['CAPUnit Account'] == "Yes") {
									$delacct = new Link("accountreset", "Remove CAPUnit Account", [$ma['CAPID']]);
								} else {
										$delacct = Null;
								}
							$html->addElement($title, $exp , $delacct);
						}
						$html = '<br/>'.$html;
					} else {
						//shouldn't get here...
						$html = "There are no members for this account.";
					}

					$desc = "<br/><h4>On this page each member assigned to the units associated with the unit account $a->id ";
					$desc .= "is listed, according to the latest CAPWATCH Import execution.  Members who are within ";
					$desc .= "30 days of their CAP membership expiring appear in orange text, while members whose CAP ";
					$desc .= "membership has already expired appear in red text.  Expired member information will ";
					$desc .= "continue to populate CAPWATCH downloads for 90 days beyond the membership expiration date.<br/><br/>";
					$desc .= "Also on this page, CAPUnit.com accounts for unit members may be reset by clicking on ";
					$desc .= "the \"Remove CAPUnit Account\" link to the right of the member name.  Removing the ";
					$desc .= "CAPUnit account is equivalent to a password reset.  In order to reestablish their login ";
					$desc .= "credentials, the member must follow the 'Create new account' process.  Removing the ";
					$desc .= "CAPUnit account does not delete any personnel data, it only removes the login and password, ";
					$desc .= "allowing the member to establish a new login and password via the create account interface.  Clicking on the ";
					$desc .= "triangle icon to the left of the name expands the view to display last CAPUnit.com login ";
					$desc .= "date (if an account is present) and the member's CAP membership expiration date.</h4><br/>";

					return [
						'body' => [
							'MainBody' => $desc.$html,
							'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
								[
									'Target' => '/',
									'Text' => 'Home'
								],
								[
									'Target' => '/accountreset',
									'Text' => 'Account Reset'
								]
							])
						]
					];
			}



		}
	}
	
