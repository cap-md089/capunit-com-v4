<?php
	class Permissions {
		/**
		 * @var string[] Permissions for regular members
		 */
		const Member = [
			"EventSignup" => 1,
			"ViewEventSignup" => 1,

			"FlightAssign" => 0,
			"MusterSheet" => 0,
			"PTSheet" => 0,
			"PromotionManagement" => 0,
			"AssignTasks" => 0,
			"AdministerPT" => 0,
			"FileManagement" => 0,
			"ViewAttendance" => 1,

			"AddEvent" => 0,
			"EditEvent" => 0,
			"EventContactSheet" => 0,
			"SignUpEdit" => 0,
			"CopyEvent" => 0,
			"LinkEvent" => 0,
			"ORMOPORD" => 0,
			"DeleteEvent" => 0,
			"AssignPosition" => 0,
			"DownloadStaffGuide" => 0,

			"EventStatusPage" => 0,
			"ProspectiveMemberManagement" => 0,
			"EventLinkList" => 0,
			"AddTeam" => 0,
			"EditTeam" => 0,
			"FileManagement" => 0,
			"ManageBlog" => 0,

			"PermissionsManagement" => 0,
			"DownloadCAPWATCH" => 0,
			"RegistryEdit" => 0,
			"PersonnelFilesDel" => 0,
			"PersonnelFiles" => 0
		];

		/**
		 * @var string[] Permissions for cadet staff
		 */
		const CadetStaff = [
			"EventSignup" => 1,
			"ViewEventSignup" => 1,

			"FlightAssign" => 1,
			"MusterSheet" => 1,
			"PTSheet" => 1,
			"PromotionManagement" => 1,
			"AssignTasks" => 1,
			"AdministerPT" => 2,
			"FileManagement" => 1,
			"DownloadStaffGuide" => 1,
			"ViewAttendance" => 1,

			"AddEvent" => 1, // Add a draft event
			"EditEvent" => 0, // They are already 'POCs' of their own event
			"EventContactSheet" => 0,
			"SignUpEdit" => 0,
			"CopyEvent" => 0,
			"LinkEvent" => 0,
			"ORMOPORD" => 0,
			"DeleteEvent" => 0,
			"AssignPosition" => 0,

			"EventStatusPage" => 0,
			"ProspectiveMemberManagement" => 0,
			"EventLinkList" => 0,
			"AddTeam" => 0,
			"EditTeam" => 0,
			"FileManagement" => 0,
			"ManageBlog" => 0,

			"PermissionsManagement" => 0,
			"DownloadCAPWATCH" => 0,
			"RegistryEdit" => 0,
			"PersonnelFilesDel" => 0,
			"PersonnelFiles" => 0
		];

		/**
		 * @var string[] Permissions for website managers
		 */
		const Manager = [
			"EventSignup" => 1,
			"ViewEventSignup" => 1,

			"FlightAssign" => 1,
			"MusterSheet" => 1,
			"PTSheet" => 1,
			"PromotionManagement" => 1,
			"AssignTasks" => 1,
			"AdministerPT" => 1,
			"FileManagement" => 1,
			"DownloadStaffGuide" => 1,
			"ViewAttendance" => 1,

			"AddEvent" => 2,
			"EditEvent" => 1,
			"EventContactSheet" => 1,
			"SignUpEdit" => 1,
			"CopyEvent" => 1,
			"LinkEvent" => 1,
			"ORMOPORD" => 1,
			"DeleteEvent" => 1,
			"AssignPosition" => 1,

			"EventStatusPage" => 1,
			"ProspectiveMemberManagement" => 1,
			"EventLinkList" => 1,
			"AddTeam" => 1,
			"EditTeam" => 1,
			"FileManagement" => 1,
			"ManageBlog" => 1,

			"PermissionsManagement" => 0,
			"DownloadCAPWATCH" => 0,
			"RegistryEdit" => 0,
			"PersonnelFilesDel" => 0,
			"PersonnelFiles" => 1
		];

		/**
		 * @var string[] Permissions for administrators
		 */
		const Admin = [
			"EventSignup" => 1,
			"ViewEventSignup" => 1,

			"FlightAssign" => 1,
			"MusterSheet" => 1,
			"PTSheet" => 1,
			"PromotionManagement" => 1,
			"AssignTasks" => 1,
			"AdministerPT" => 1,
			"FileManagement" => 1,
			"DownloadStaffGuide" => 1,
			"ViewAttendance" => 1,

			"AddEvent" => 2,
			"EditEvent" => 1,
			"EventContactSheet" => 1,
			"SignUpEdit" => 1,
			"CopyEvent" => 1,
			"LinkEvent" => 1,
			"ORMOPORD" => 1,
			"DeleteEvent" => 1,
			"AssignPosition" => 1,

			"EventStatusPage" => 1,
			"ProspectiveMemberManagement" => 1,
			"EventLinkList" => 1,
			"AddTeam" => 1,
			"EditTeam" => 1,
			"FileManagement" => 1,
			"ManageBlog" => 1,

			"PermissionsManagement" => 1,
			"DownloadCAPWATCH" => 1,
			"RegistryEdit" => 1,
			"PersonnelFilesDel" => 1,
			"PersonnelFiles" => 1
		];

		public static function GetPermissions (\Member $mem) {
			$consts = (new ReflectionClass(__CLASS__))->getConstants();
			return $consts[$mem->AccessLevel];
		}
	}
