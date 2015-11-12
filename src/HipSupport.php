<?php 

namespace Estey\HipSupport;

use HipChat\HipChat;
use Illuminate\Config\Repository;
use Illuminate\Cache\CacheManager;

class HipSupport
{
    /**
     * HipChat instance.
     * @var HipChat\HipChat
     */
    protected $hipchat;

    /**
     * Config Instance.
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * The cache manager instance.
     * @var Illuminate\Cache\CacheManger
     */
    protected $cache;

    /**
     * Create a new HipSupport instance.
     *
     * @param HipChat\HipChat $hipchat
     * @param Illuminate\Config\Repository $config
     * @param Illuminate\Cache\CacheManger $cache
     * @return void
     */
    public function __construct(
        HipChat $hipchat,
        Repository $config,
        CacheManager $cache
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->hipchat = $hipchat;
    }

    /**
     * Initiate HipSupport chat session. Create new room,
     * return the web client URL to the room.
     *
     * @param array $options
     * @return stdClass|boolean
     */
    public function init(array $options = [])
    {
        if (!$this->isOnline()) {
            return false;
        }

        // Merge $options with Config settings.
        $options = $this->mergeConfig($options);
        $room = $this->createRoom(
            $options['room_name'],
            $options['owner_user_id']
        );

        // Split the hash from the room's Guest Access URL
        $room->hipsupport_hash = $this->getHashFromUrl($room->guest_access_url);

        // Append the HipChat web client parameters.
        $room->hipsupport_url = $this->appendUrlOptions(
            $room->guest_access_url,
            $options
        );

        // Notify the given room that a new room has been created.
        $this->notify($options, $room);

        return $room;
    }

    /**
     * Create a public new room with guest access on.
     *
     * @param string $name  
     * @param integer $owner_user_id  
     * @return stdClass
     */
    public function createRoom($name, $owner_user_id = null)
    {
        $i = 1;
        $room_name = $name;

        $owner_user_id = $owner_user_id ?: $this->config->get(
            'hipsupport::config.owner_user_id'
        );

        // Make sure that a room doesn't already exist with this name.
        // If one does exist, add a number to the end of the name.
        while ($this->hipchat->room_exists($room_name)) {
            $room_name = $name . ' ' . $i++;
        }

        // Create room.
        $room = $this->hipchat->create_room(
            $room_name,
            $owner_user_id,
            null,
            null,
            true
        );

        return $room->room;
    }

    /**
     * Public access to this HipChat API instance.
     *
     * @return HipChat\HipChat
     */
    public function getHipChat()
    {
        return $this->hipchat;
    }

    /**
     * Take HipSupport online.
     *
     * @param integer $minutes
     * @return boolean
     */
    public function online($minutes = null)
    {
        if (!$minutes) {
            return $this->cache->forever('hipsupport', true);
        }

        return $this->cache->put('hipsupport', true, $minutes);
    }

    /**
     * Take HipSupport offline.
     *
     * @return boolean
     */
    public function offline()
    {
        return $this->cache->forget('hipsupport');
    }

    /**
     * Check to see if HipSupport is online.
     *
     * @return boolean
     */
    public function isOnline()
    {
        return $this->cache->has('hipsupport');
    }

    /**
     * Merge config array with given options.
     *
     * @param array $options   
     * @return array
     */
    protected function mergeConfig($options)
    {
        $options = array_merge(
            $this->config->get('hipsupport::config'),
            $options
        );

        if (isset($options['notification'])
            and is_array($options['notification'])
        ) {
            $options['notification'] = array_merge(
                (array) $this->config->get('hipsupport::config.notification'),
                $options['notification']
            );
        }

        return $options;
    }

    /**
     * Get the hash from a Guest Access URL
     *
     * @param string $url
     * @return string
     */
    protected function getHashFromUrl($url)
    {
        $url = explode('/', $url);
        return end($url);
    }

    /**
     * Get the hash from a Guest Access URL
     *
     * @param string $url
     * @param array $options
     * @return string
     */
    protected function appendUrlOptions($url, $options)
    {
        $options = array_only($options, [
            'welcome_msg',
            'timezone',
            'anonymous',
            'minimal'
        ]);

        $boolean_keys = ['minimal', 'anonymous'];
        foreach ($boolean_keys as $key) {
            if (isset($options[$key])) {
                $options[$key] = $this->booleanToString($options[$key]);
            } else {
                $options[$key] = 'true';
            }
        }

        return $url . '?' . http_build_query($options);
    }

    /**
     * HipChat's Guest Access URL needs booleans set to strings.
     * Convert all falsy variables to a 'false' string. Otherwise
     * return 'true' as the default.
     *
     * @param string $var
     * @return string
     */
    protected function booleanToString($var)
    {
        if ((boolean) $var === false or $var === 'false') {
            return 'false';
        }
        return 'true';
    }

    /**
     * Send notification that a new room has been created.
     *
     * @param array $options
     * @param object|null $room
     * @return boolean
     */
    protected function notify($options, $room = null)
    {
        if (!isset($options['notification']) or !$options['notification']) {
            return false;
        }

        extract($options['notification']);
        
        if (!isset($room_id) or !$room_id) {
            return false;
        }

        if ($room) {
            $message = str_replace('[room_name]', $room->name, $message);
            $message = str_replace('[room_id]', $room_id, $message);
        }

        return (boolean) $this->hipchat->message_room(
            $room_id,
            $from,
            $message,
            $notify,
            $color,
            $message_format
        );
    }
}
