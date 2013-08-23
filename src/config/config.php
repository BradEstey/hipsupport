<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| HipChat API Token
	|--------------------------------------------------------------------------
	|
	| Tokens are used to authenticate with the HipChat API. An Admin token 
	| is required to use HipSupport.
	|
	| https://www.hipchat.com/docs/api/auth
	|
	*/

	'token' => null,

	/*
	|--------------------------------------------------------------------------
	| Owner User ID
	|--------------------------------------------------------------------------
	|
	| An Owner User ID is required when creating a new room. The User ID 
	| must have permission to creating new rooms.
	|
	| https://www.hipchat.com/docs/api/method/rooms/create
	|
	*/

	'owner_user_id' => null,

	/*
	|--------------------------------------------------------------------------
	| Room Name Format
	|--------------------------------------------------------------------------
	|
	| When creating a new room, this format will be used. 
	|
	| https://www.hipchat.com/docs/api/method/rooms/create
	|
	*/

	'room_name' => 'Live Chat ' . date('Y-m-d H:m'),

	/*
	|--------------------------------------------------------------------------
	| Welcome Message
	|--------------------------------------------------------------------------
	|
	| Customizes the welcome message of the room the user will first see
	| when they join the room.
	|
	| http://help.hipchat.com/knowledgebase/articles/238941-embedding-hipchat
	|
	*/

	'welcome_msg' => 'How can we help you?',

	/*
	|--------------------------------------------------------------------------
	| Timezone
	|--------------------------------------------------------------------------
	|
	| The timezone that chat messages should appear to be sent in. The 
	| timezone is required for the HipChat web client in anonymous mode.
	|
	| http://help.hipchat.com/knowledgebase/articles/238941-embedding-hipchat
	|
	*/

	'timezone' => 'utc',

	/*
	|--------------------------------------------------------------------------
	| Notification Settings
	|--------------------------------------------------------------------------
	|
	| Notification settings are for the message that is sent when a new
	| room has been created. If notification is null, rather than an array,
	| or room_id is null, no notification will be sent.
	|
	| room_id 
	|	Required. ID or name of the room that this 
	|	notification should be sent to.
	|
	| from  
	|	Required. Name the message will appear be sent from. Must be 
	|   less than 15 characters long. May contain letters, numbers, 
	|	-, _, and spaces.
	|
	| message
	|	Required. The message body. 10,000 characters max.
	|	Variable that may be used: [room_name]
	|
	| message_format
	|	Determines how the message is treated by our server 
	|	and rendered inside HipChat applications. (text or html)
	|	Default: html
	|
	| notify
	|	Whether or not this message should trigger a notification 
	|	for people in the room. (Make noise, send push notifications,
	|	send emails, etc)
	|	Default: false
	|
	| color
	|	Background color for message. One of "yellow", "red", "green", 
	|	"purple", "gray", or "random".
	|	Default: yellow
	|
	| https://www.hipchat.com/docs/api/method/rooms/message
	|
	*/

	'notification' => array(
		'room_id' => null,
		'from' => 'HipSupport',
		'message' => 'A new live chat session has been initiated.<br />Go to room: <strong>[room_name]</strong>.',
		'message_format' => 'html',
		'notify' => true,		
		'color' => 'green'		
	),

);
