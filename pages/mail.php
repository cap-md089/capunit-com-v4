<?php
if ($_ACCOUNT->id !== 'md089') die('Nope');
$to = "grioux@gmail.com";
$subject = "Test mail";
$message = "This message contains text intended to be event information.";
$from = "Event Manager <eventmanager@capunit.com>";
$headers = "From:" . $from . "\r\n" . 
'Reply-To: Event Manager <eventmanager@capunit.com>' . "\r\n" . 
'X-Mailer: PHP/' . phpversion();
mail($to,$subject,$message,$headers);
echo "Mail Sent.";
?>
