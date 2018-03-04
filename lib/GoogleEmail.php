<?php
    require_once (BASE_DIR."lib/DB_Utils.php");
    require_once (BASE_DIR."lib/vendor/autoload.php");
	require_once (BASE_DIR."lib/logger.php");

    class GoogleEmail {
        public static $accountId;
        public static $client;
        public static $service;
		public static $logger;
		public static $loglevel;
        
        public static function init () {
			self::$loglevel = 2;
			self::$logger = new Logger("GoogleEmail");
			global $_ACCOUNT;
			self::$accountId = $_ACCOUNT->id;

			self::$logger->Log("init:: AccountID=: ".$_ACCOUNT->id, self::$loglevel);
            self::$client = new Google_Client();
			if(!self::$client) {self::$logger->Log("init:: client creator returned false", self::$loglevel);} else {self::$logger->Log("init:: client creator returned true", self::$loglevel);}

			//self::$client->setAuthConfig(BASE_DIR.'../credentials/'.$_ACCOUNT->id.'.json');			
			self::$client->setApplicationName("capunit.com Emailer");
            self::$client->useApplicationDefaultCredentials();
            self::$client->setScopes(Google_Service_Gmail::GMAIL_SEND);

            self::$service = new Google_Service_Gmail(self::$client);
			if(!self::$service) {self::$logger->Log("init:: Gmail Service creator returned false", self::$loglevel);} else {self::$logger->Log("init:: Calendar Service creator returned true", self::$loglevel);}

		}


        public static function sendMail ($addresses, $bodyText, $subject) {
            // self::service->setDisplayName("CAPUnit.com");
            // self::service->setReplyToAddress("events@capunit.com");
            $chunkSizeBytes = 1 * 1024 * 1024; // size of chunks we are going to send to google

            //need to implement the multi-part emailer, sample code at link below
            //https://kevinjmcmahon.net/articles/22/html-and-plain-text-multipart-email-/
            $mailBody = <<<EOD
<div style="background-color:#f0f8ff;padding: 20px">
<header style="background:#28497e;padding:10px;height:200px;width:100%;margin:0;padding:0">
    <div style="padding:0;margin:0;background-image:url('https://$_ACCOUNT->id.capunit.com/images/header.png');width:100%;height:100%;background-size:contain;background-repeat:no-repeat;background-position:50% 50%"></div>
</header>
<div style="border: 5px solid #28497e;margin:0;padding: 20px">
$bodyText
</div>
<footer style="background:#28497e;padding:25px;color: white">
    &copy; CAPUnit.com 2017
</footer>
</div>
EOD;
            $bodyText = preg_replace('/<a.*href="(.*)".*>(.*)<\/a>/', '$2 ($1)', $bodyText);
            $mailAltBody = "CAPUnit.com notification\n\n".strip_tags(preg_replace('/<br.*>/', "\n", $bodyText))."\n\nCopyright 2017 CAPUnit.com";

            $mailMessage = $makeMessage($bodyText);
            // code to create mime message
            $message = new Google_Service_Gmail_Message();
            $message->setRaw($mailMessage);

            $result = $mailService->users_messages->send('me', $message);
            self::$logger->Log("result string: ".$result);
            $googleMessageId = $status->getId();
            self::$logger->Log("message id: ".$googleMessageId);
        }

        public static function makeMessage ($addresses, $bodyText, $subject) {
            $envelope["from"]= "events@capunit.com";
            $envelope["to"]  = $addresses;
            $envelope["cc"]  = "grioux.cap@gmail.com";
            $envelope["subject"] = $subject;

            $part1["type"] = TYPEMULTIPART;
            $part1["subtype"] = "mixed";

            $part2["type"] = TYPETEXT;
            $part2["subtype"] = "plain";
            $part2["description"] = "mail body";
            $part2["contents.data"] = $bodyText."\n\n\n\t";
            /*
            $filename = "/tmp/imap.c.gz";
            $fp = fopen($filename, "r");
            $contents = fread($fp, filesize($filename));
            fclose($fp);

            $part3["type"] = TYPEAPPLICATION;
            $part3["encoding"] = ENCBINARY;
            $part3["subtype"] = "octet-stream";
            $part3["description"] = basename($filename);
            $part3["contents.data"] = $contents;
            */
            $body[1] = $part1;
            $body[2] = $part2;
            // $body[3] = $part3;

            $message = imap_mail_compose($envelope, $body);
            self::$logger->Log("imap mail: ".$message);

            return $message;
        }

    }

            // Call the API with the media upload, defer so it doesn't immediately return.
            //https://developers.google.com/api-client-library/php/guide/media_upload
            //https://developers.google.com/gmail/api/v1/reference/users/messages/send
            //https://stackoverflow.com/questions/24503483/reading-messages-from-gmail-in-php-using-gmail-api
            //https://michiel.vanbaak.eu/2016/02/27/sending-big-email-using-google-php-api-client-and-gmail/
            //https://developers.google.com/gmail/api/v1/reference/users/messages
            //https://developers.google.com/gmail/api/v1/reference/users/drafts

            /*
            $googleClient->setDefer(true);
            $result = $mailService->users_messages->send('events@capunit.com', $message);
            // create mediafile upload
            $media = new Google_Http_MediaFileUpload(
                $googleClient,
                $result,
                'message/rfc822',
                $mailMessage,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(strlen($mailMessage));
            // Upload the various chunks. $status will be false until the process is complete.
            $status = false;
            while(!$status) {
                $status = $media->nextChunk();
            }
            $result = false;
            if($status != false) {
              $result = $status;
            }
            // Reset to the client to execute requests immediately in the future.
            $googleClient->setDefer(false);
            */

    


/*

remail
return_path
date
from
reply_to
in_reply_to
subject
to
cc
bcc
message_id
custom_headers

If you can't find a header you need in this list, you can use 'custom_headers'. It is just an array of lines to be appended to the header without any formatting, like this:

$envelope["custom_headers"] = Array("User-Agent: My Mail Client", "My-Header: My Value");
$envelope = [
    //...
    "custom_headers" => [
        "X-SES-CONFIGURATION-SET: example",
        "X-SES-MESSAGE-TAGS: emailType=example"
    ]
];
*/

/*
    {
  "id": string,
  "threadId": string,
  "labelIds": [
    string
  ],
  "snippet": string,
  "historyId": unsigned long,
  "internalDate": long,
  "payload": {
    "partId": string,
    "mimeType": string,
    "filename": string,
    "headers": [
      {
        "name": string,
        "value": string
      }
    ],
    "body": users.messages.attachments Resource,
    "parts": [
      (MessagePart)
    ]
  },
  "sizeEstimate": integer,
  "raw": bytes
}


$envelope["from"]= "joe@example.com";
$envelope["to"]  = "foo@example.com";
$envelope["cc"]  = "bar@example.com";

$part1["type"] = TYPEMULTIPART;
$part1["subtype"] = "mixed";

$filename = "/tmp/imap.c.gz";
$fp = fopen($filename, "r");
$contents = fread($fp, filesize($filename));
fclose($fp);

$part2["type"] = TYPEAPPLICATION;
$part2["encoding"] = ENCBINARY;
$part2["subtype"] = "octet-stream";
$part2["description"] = basename($filename);
$part2["contents.data"] = $contents;

$part3["type"] = TYPETEXT;
$part3["subtype"] = "plain";
$part3["description"] = "description3";
$part3["contents.data"] = "contents.data3\n\n\n\t";

$body[1] = $part1;
$body[2] = $part2;
$body[3] = $part3;



    */