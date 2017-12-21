use EventManagement;
(SELECT `content`, `title`, MATCH (`content`, `title`) AGAINST ('mission') AS score FROM `Blog`)
UNION
(SELECT `text`, `name`, MATCH (`text`, `name`) AGAINST ('mission') AS score FROM `pages`)
UNION
(SELECT `EventDescription`, `EventName`, MATCH (`EventDescription`, `EventName`) AGAINST ('mission') AS score FROM `EventInformation`)
ORDER BY score DESC;
/*insert into TEventInformation (EventName, MeetDateTime, MeetLocation, StartDateTime, EventLocation, EndDateTime, PickupDateTime, PickupLocation, TransportationProvided, TransportationDescription, Uniform, DesiredNumParticipants, RegistrationDeadline, ParticipationFeeDue, ParticipationFee, Meals, Activity, HighAdventureDescription, RequiredEquipment, EventWebsite, RequiredForms, Comments, POCJSONData, AcceptSignUps, SignUpDenyMessage, ReceiveEventUpdates, ReceiveSignUpUpdates, PublishToWingCalendar, GroupEventNumber, Complete, Administration, Status, EventTags, Debrief, EventDescription) VALUES
						  ('Test event 1', 1492031278, 'Airport',      1492031278,   'Airport',     1492031278, 1492031278,       'Airport',      0,                      'None'                    , 'BDUs', 16, 							1492031278, 		1492031278, 			10,			'No','Classroom', 'None',						'None',		'http://localhost/','None',	'None',		'{"name":"Andrew","phone":"","email":""}', 0, '', 0,						0,						0,				0,				0,				'',		'Complete',	'',		'',				'');		*/
select * from TEventInformation;