<?php

if (php_sapi_name () == 'cli') {

require_once __DIR__.'/vendor/autoload.php';

$GOOGLE_CREDENTIAL_PATH = '../../credentials/md089.json';
//putenv('GOOGLE_APPLICATION_CREDENTIALS=../../credentials/md089.json');
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes(Google_Service_Calendar::CALENDAR);

$service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
$calendarId = 'capstmarys@gmail.com';
/*$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);*/
$optParams = array(
  'q' => 'MD-089',
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
);
$results = $service->events->listEvents($calendarId, $optParams);
echo count($results->getItems());

if (count($results->getItems()) == 0) {
  echo " No  event with private key found.\n";
} else {
  echo " Upcoming events:\n";
  foreach ($results->getItems() as $event) {
    $start = $event->start->dateTime;
    if (empty($start)) {
      $start = $event->start->date;
    }
    $event->setColorId("11");
    $service->events->update($calendarId, $event->getId(), $event);
    echo $event->getSummary()."-event located\n";
  }
}

// Refer to the PHP quickstart on how to setup the environment:
// https://developers.google.com/google-apps/calendar/quickstart/php
// Change the scope to Google_Service_Calendar::CALENDAR and delete any stored
// credentials.

$event = new Google_Service_Calendar_Event(array(
  'summary' => 'Color Guard Practice',
  'location' => 'Airport',
  'description' => 'Need to escape all special characters...',
  'start' => array(
    'dateTime' => '2017-11-18T10:30:00-05:00',
    'timeZone' => 'America/New_York',
  ),
  'end' => array(
    'dateTime' => '2017-11-18T11:00:00-05:00',
    'timeZone' => 'America/New_York',
  ),
  'colorId' => '11',
));

//$event = $service->events->insert($calendarId, $event);
//echo "event added\n";

}
/*

// Refer to the PHP quickstart on how to setup the environment:
// https://developers.google.com/google-apps/calendar/quickstart/php
// Change the scope to Google_Service_Calendar::CALENDAR and delete any stored
// credentials.

$event = new Google_Service_Calendar_Event(array(
  'summary' => 'Google I/O 2015',
  'location' => '800 Howard St., San Francisco, CA 94103',
  'description' => 'A chance to hear more about Google\'s developer products.',
  'start' => array(
    'dateTime' => '2015-05-28T09:00:00-07:00',
    'timeZone' => 'America/Los_Angeles',
  ),
  'end' => array(
    'dateTime' => '2015-05-28T17:00:00-07:00',
    'timeZone' => 'America/Los_Angeles',
  ),
  'recurrence' => array(
    'RRULE:FREQ=DAILY;COUNT=2'
  ),
  'attendees' => array(
    array('email' => 'lpage@example.com'),
    array('email' => 'sbrin@example.com'),
  ),
  'reminders' => array(
    'useDefault' => FALSE,
    'overrides' => array(
      array('method' => 'email', 'minutes' => 24 * 60),
      array('method' => 'popup', 'minutes' => 10),
    ),
  ),
));

$calendarId = 'primary';
$event = $service->events->insert($calendarId, $event);
printf('Event created: %s\n', $event->htmlLink);




$colors = $service->colors->get();

// Print available calendarListEntry colors.
foreach ($colors->getCalendar() as $key => $color) {
  echo "colorId : {$key}\n";
  echo "  Background: {$color->getBackground()}\n";
  echo "  Foreground: {$color->getForeground()}\n";
}
// Print available event colors.
foreach ($colors->getEvent() as $key => $color) {
  echo "colorId : {$key}\n";
  echo "  Background: {$color->getBackground()}\n";
  echo "  Foreground: {$color->getForeground()}\n";
}








*/
