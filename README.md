HipSupport 
==========

[![Latest Stable Version](http://img.shields.io/packagist/v/estey/hipsupport.svg)](https://packagist.org/packages/estey/hipsupport) [![Build Status](https://travis-ci.org/BradEstey/hipsupport.svg?branch=1.0)](https://travis-ci.org/BradEstey/hipsupport) [![Coverage Status](https://img.shields.io/coveralls/BradEstey/hipsupport.svg)](https://coveralls.io/r/BradEstey/hipsupport?branch=1.0)

HipSupport is a Laravel 4 package that facilitates the creation of a live chat support system ontop of HipChat's API. If you are already using Laravel 4 and HipChat, then you can have a fully functional live chat system up and running in minutes. [Try a live demo!](http://www.bradestey.com/projects/hipsupport/demo)


- [How it Works](#how-it-works)
- [Installation](#installation)
- [Configuration](#configuration)
- [Artisan Commands](#artisan-commands)
- [Quickstart](#quickstart)
- [Named Users](#named-users)
- [Limitations](#limitations)

How it Works
------------

HipChat released a [jQuery HipChat Plugin](http://blog.hipchat.com/2013/08/20/embedding-hipchat/) that allows users to embed a [HipChat Web Client](http://help.hipchat.com/knowledgebase/articles/238941-embedding-hipchat) into their web pages so that users can now access public chat rooms anonymously. HipSupport uses [HipChat's API](https://github.com/hipchat/hipchat-php) to dynamically create a new public chat room for each incoming chat request and send a notification to your company's users of this new chat session. From there, your company's users can join the room and chat with the user.

![HipChat Embedded Chat Client](http://www.bradestey.com/img/projects/hipsupport/hipchat-embed.png "HipChat Embedded Chat Client")

Installation
------------

Install this package through Composer by editing your project's `composer.json` file to require `estey/hipsupport`.

``` json
{
    "require": {
        "laravel/framework": "4.0.*",
        "estey/hipsupport": "1.0.*"
    }
}
```

Then, update Composer:

``` bash
composer update
```

Open `app/config/app.php`, and add the service provider to the `providers` array:

```
'Estey\HipSupport\HipSupportServiceProvider'
```

Add the facade to the `aliases` array at the bottom of `app/config/app.php`.

```
'HipSupport' => 'Estey\HipSupport\HipSupportFacade'
```

Configuration
-------------

To publish the configuration file, run:

``` bash
php artisan config:publish estey/hipsupport
```

The config file will be located at `app/config/packages/estey/hipsupport/config.php`. An Admin token is required in the `token` field and a user with access to add rooms is required in the `owner_user_id` field. 

The `room_name` format should be something unique and by default appends `Y-m-d H:m` to the end of the Room name. 

``` php
[
    'room_name' => 'Live Chat ' . date('Y-m-d H:m')
]
```

Or you can leave this blank and assign it at runtime with whatever you want. Like the user's IP Address or whatever. If a room name already exists then a number will be appended to the end of the name. (This comes at the cost of an extra API request, so be mindful of that. See the <a href="#limitations">Limitations</a> section for more details.)

To send notifications, you must define the `room_id` in the `notification` array. If the `room_id` is `null` then no notification will be sent.


Artisan Commands
----------------

There are two Artisan commands to help you get started. `php artisan hipsupport:online` and `php artisan hipsupport:offline`. These two commands take your live chat online and.. offline. Online accepts a parameter to define how many minutes to bring HipSupport online. 

``` bash
php artisan hipsupport:online 480
```

The above command will bring HipSupport online for 8 hours. That way you don't have to remember to turn it off. Check if HipSupport is online using the `HipSupport::isOnline()` method. 

Quickstart
----------

To create an absolute basic installation, create a route to handle your incoming chat requests. If HipSupport is Offline then `HipSupport::init()` will return `false`.

``` php
Route::post('chat', ['before' => 'csrf', function() {
    $room = HipSupport::init();
    if ($room) {
        return Redirect::to($room->hipsupport_url);
    }
}]);
```

In your view, add a form to post into your chat route when HipSupport is online.

```
@if (HipSupport::isOnline())
  {{ Form::open(array('url' => 'chat')) }}
    {{ Form::submit('Start Live Chat') }}
  {{ Form::close() }}
@endif
```

By using JavaScript, you can open the chat up on an iFrame, in an iFrame inside a modal, or open the chat in a new window. The chat screen is simple and automatically resizes to the size of its container. To handle an ajax request, your route would look something like this:

``` php
Route::post('chat', ['before' => 'csrf', function() {
    $room = HipSupport::init();

    if ($room) {
        if (Request::ajax()) {
            return Response::json(['url' => $room->hipsupport_url]);
        }
        return Redirect::to($room->hipsupport_url);
    }
}]);
```



### Named Users

HipChat's API doesn't currently provide a way to name Guests. If you pass `HipSupport::init(array('anonymous' => false))` then HipChat will prompt the user to enter there name, but the page is kind of clunky, isn't responsive and kills the illusion of a live chat system. Alternatively, you can add a form and a layer of validation in front of the `HipSupport::init()` method and pass the user's inputs into the `room_name` and notification `message`. You can even save the user's data (User ID, Name, Email, etc) in your database associated to the chat's `room_id` so that you can attribute the chat history to specific users.

Here's an example of an extremely basic validation.

``` php
Route::post('chat', ['before' => 'csrf', function() {
    $validator = Validator::make(Input::all(), ['name' => 'required|min:5']);

    if ($validator->fails()) {
        // Redirect with Errors or send Errors via JSON Response... 
    }   

    $room = HipSupport::init([
        'room_name' => 'Live Chat with ' . Input::get('name'),
        'notification' => [
            'message' => Input::get('name') . ' would like to chat.'
        ]
    ]);

    // Save the name and $room->room_id into the database.
    if ($room) {
        if (Request::ajax()) {
            return Response::json(['url' => $room->hipsupport_url]);
        }
        return Redirect::to($room->hipsupport_url);
    }
}]);
```

Limitations
-----------

HipChat's API currently limits API requests to 100 requests per 5 minutes. Each `HipSupport::init()` call eats 3 requests (check if room name exists, create room and notify room). Read more on [HipChat's rate limiting](https://www.hipchat.com/docs/api/rate_limiting).

Too Many Rooms!
---------------

It's probably a good idea to adopt some form of consistant naming of dynamically created rooms. In the next version of HipSupport I plan on added some more Artisan commands to help mass delete or mass archive rooms that have been inactive for a specified amount of time and whose room name contains a given string.

License
-------

The MIT License (MIT). Please see [License File](https://github.com/BradEstey/hipsupport/blob/master/LICENSE) for more information.