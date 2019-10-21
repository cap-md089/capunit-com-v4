<?php
	require_once (BASE_DIR."lib/general.php");
	require_once (BASE_DIR."lib/Notify.php");


	class Output {
		public static function doGet ($e, $c, $l, $m, $a) {
			if (!$l) {return ['error' => 411];}

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
						FROM EventManagement.Data_Member
						LEFT JOIN EventManagement.UserAccountInfo ON Data_Member.CAPID=UserAccountInfo.CAPID
						LEFT JOIN EventManagement.SignInData ON Data_Member.CAPID=SignInData.CAPID
						WHERE Data_Member.ORGID IN (SELECT UnitId FROM Accounts WHERE AccountID = :aid)
						AND SignInData.AccountID = :aid
						ORDER BY Data_Member.NameLast;';

					$stmt = $pdo->prepare($sqlstmt);
					$stmt->bindValue(':aid', $a->id);
					$madata = DBUtils::ExecutePDOStatement($stmt);
					$targetexpiring = time()+(60*60*24*30); //one month from now
					$targetexpired = time(); //now
					if (count($madata) > 0) {
						$html = new DetailedListPlus("CAPUnit.com Member Accounts for ".$a->id."<br/>Target date: ".date('n/j/Y',$targetexpired));
						foreach ($madata as $ma) {
								$color = '';
								if(
										($ma['Membership Expiration'] < $targetexpiring) &&
										($ma['Membership Expiration'] >= $targetexpired)	) 
								{ $color = 'color:orange'; 
								} else if ( $ma['Membership Expiration'] < $targetexpired ) 
								{ $color = 'color:red'; }
								$title = "<span style=\"$color\">{$ma['NameLast']}, {$ma['NameFirst']} {$ma['Rank']}</span>";
								$exp = 'CAP Membership Expires on '.date('n/j/Y', $ma['Membership Expiration']);
								if(!!$ma['Last SignIn']) {
										$exp = 'Last SignIn: '.(date("n/j/Y", $ma['Last SignIn']))."<br/>".$exp;
								}
								if(!!$ma['CAPUnit Account']) {
									$delacct = new Link("accountreset", "Remove CAPUnit Account", [$ma['CAPID']]);
								} else {
										$delacct = "No CAPUnit Account";
								}
							$html->addElement($title, $exp , $delacct);
						}
						$html = '<br/>'.$html;
					} else {
						//shouldn't get here...
						$html = "There are no members for this account.";
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


			if (isset($e['uri'][$e['uribase-index']]) &&
				( ($m->hasPermission('PermissionManagement') && $a->hasMember($m)) || $m->IsRioux )
			) {
				$pdo = DBUtils::CreateConnection();
				$data = $e['uri'][$e['uribase-index']];
				$mem = Member::Estimate($data, true);
				if(!!$mem) {
						$html = "<br/>Selected member {$mem->RankName} would be deleted<br/><br/>".(new Link("accountreset", "Refresh List"));
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
			} else {
				$html = "<br/>Invalid Permissions<br/><br/>".(new Link("accountreset", "Refresh List"));
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

		}
	}
	
