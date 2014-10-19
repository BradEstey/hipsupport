<?php 

namespace Estey\HipSupport\Test;

use Mockery as m;
use Estey\HipSupport\HipSupport;
use ReflectionMethod;
use PHPUnit_Framework_TestCase;

class HipSupportTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->cache = m::mock('Illuminate\Cache\CacheManager');
        $this->config = m::mock('Illuminate\Config\Repository');
        $this->hipchat = m::mock('HipChat\HipChat');
        $this->hipsupport = new HipSupport(
            $this->hipchat,
            $this->config,
            $this->cache
        );

        $this->mock_config = array(
            'token' => 'foobar',
            'owner_user_id' => 123456,
            'room_name' => 'FooBar',
            'welcome_msg' => 'foo',
            'timezone' => 'utc',
            'notification' => array(
                'room_id' => 123456,
                'from' => 'HipSupport',
                'message' => 'foo',
                'message_format' => 'html',
                'notify' => true,
                'color' => 'green'
            )
        );
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Make a new HipSupport object.
     * 
     * @return Estey\HipSupport\HipSupport
     */
    public function initHipSupport()
    {
        return new HipSupport($this->hipchat, $this->config, $this->cache);
    }

    /**
     * Reflection.
     * 
     * @param string $method
     * @return method
     */
    public function reflection($method)
    {
        $method = new ReflectionMethod('Estey\HipSupport\HipSupport', $method);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Test init() method when offline.
     */
    public function testInitOffline()
    {
        $this->cache
            ->shouldReceive('has')
            ->with('hipsupport')
            ->once()
            ->andReturn(false);
        $this->assertFalse($this->hipsupport->init());
    }

    /**
     * Test init() method when online.
     */
    public function testInit()
    {
        $this->cache
            ->shouldReceive('has')
            ->with('hipsupport')
            ->once()
            ->andReturn(true);

        $this->config
            ->shouldReceive('get')
            ->andReturn(
                $this->mock_config,
                $this->mock_config['notification']
            );

        $room = (object) array(
            'room' => (object) array(
                'name' => 'FooBar 1',
                'guest_access_url' => 'https://www.hipchat.com/abcd123AB'
            )
        );

        $this->hipchat
            ->shouldReceive('room_exists')
            ->andReturn(true, false);

        $this->hipchat
            ->shouldReceive('create_room')
            ->with('FooBar 1', 123456, null, null, true)
            ->once()
            ->andReturn($room);
        $this->hipchat
            ->shouldReceive('message_room')
            ->with(123456, 'HipSupport', 'foo', true, 'green', 'html')
            ->once()
            ->andReturn(true);

        $room->room->hipsupport_hash = 'abcd123AB';
        $room->room->hipsupport_url = 'https://www.hipchat.com/abcd123AB' .
                                      '?welcome_msg=foo&timezone=utc&minimal' .
                                      '=true&anonymous=true';

        $this->assertSame($this->hipsupport->init(), $room->room);
    }

    /**
     * Test createRoom() method fails.
     */
    public function testCreateRoomOneFail()
    {
        $room = (object) array('room' => (object) array('name' => 'foo 1'));
        
        $this->hipchat
            ->shouldReceive('get_room')
            ->andReturn(true, false);
        $this->config
            ->shouldReceive('get')
            ->with('hipsupport::config.owner_user_id')
            ->once()
            ->andReturn(654321);
        $this->hipchat
            ->shouldReceive('room_exists')
            ->andReturn(true, false);
        $this->hipchat
            ->shouldReceive('create_room')
            ->with('foo 1', 654321, null, null, true)
            ->once()
            ->andReturn($room);
        $this->assertEquals(
            $this->hipsupport->createRoom('foo'),
            $room->room
        );
    }

    /**
     * Test createRoom() method fails.
     */
    public function testCreateRoomTwoFails()
    {
        $room = (object) array('room' => (object) array('name' => 'bar 2'));
        $this->hipchat
            ->shouldReceive('room_exists')
            ->andReturn(true, true, false);
        $this->hipchat
            ->shouldReceive('create_room')
            ->with('bar 2', 123456, null, null, true)
            ->once()
            ->andReturn($room);
        $this->assertEquals(
            $this->hipsupport->createRoom('bar', 123456),
            $room->room
        );
    }

    /**
     * Test createRoom() method.
     */
    public function testCreateRoom()
    {
        $room = (object) array('room' => (object) array('name' => 'baz'));
        $this->hipchat
            ->shouldReceive('room_exists')
            ->andReturn(false);
        $this->hipchat
            ->shouldReceive('create_room')
            ->with('baz', 123456, null, null, true)
            ->once()
            ->andReturn($room);
        $this->assertEquals(
            $this->hipsupport->createRoom('baz', 123456),
            $room->room
        );
    }

    /**
     * Test getHipChat() method.
     */
    public function testGetHipChat()
    {
        $this->assertSame($this->hipsupport->getHipChat(), $this->hipchat);
    }

    /**
     * Test online() method.
     */
    public function testOnline()
    {
        $this->cache
            ->shouldReceive('forever')
            ->with('hipsupport', true)
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->hipsupport->online());

        $this->cache
            ->shouldReceive('put')
            ->with('hipsupport', true, 400)
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->hipsupport->online(400));
    }

    /**
     * Test offline() method.
     */
    public function testOffline()
    {
        $this->cache
            ->shouldReceive('forget')
            ->with('hipsupport')
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->hipsupport->offline());
    }

    /**
     * Test isOnline() method.
     */
    public function testIsOnline()
    {
        $this->cache
            ->shouldReceive('has')
            ->with('hipsupport')
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->hipsupport->isOnline());
    }

    /**
     * Test mergeConfig() method.
     */
    public function testMergeConfig()
    {
        $method = $this->reflection('mergeConfig');
        
        $options = array('foo' => 'bar', 'notification' => null);
        $this->config
            ->shouldReceive('get')
            ->with('hipsupport::config')
            ->once()
            ->andReturn($options);

        $this->assertEquals(
            $method->invoke($this->initHipSupport(), array('foo' => 'baz')),
            array('foo' => 'baz', 'notification' => null)
        );
    }

    /**
     * Test megeConfig() method with notification.
     */
    public function testMergeConfigNotification()
    {
        $method = $this->reflection('mergeConfig');
        $notification = array('foo' => 1, 'bar' => 2);
        $options = array('foo' => 'bar', 'notification' => $notification);
        $this->config
            ->shouldReceive('get')
            ->andReturn($options, $notification);

        $this->assertEquals(
            $method->invoke(
                $this->initHipSupport(),
                array('foo' => 'baz', 'notification' => array('foo' => 5))
            ),
            array(
                'foo' => 'baz',
                'notification' => array('foo' => 5, 'bar' => 2)
            )
        );

    }

    /**
     * Test megeConfig() method with no notification.
     */
    public function testMergeConfigNullNotification()
    {
        $method = $this->reflection('mergeConfig');
        $notification = array('foo' => 1, 'bar' => 2);
        $options = array('foo' => 'bar', 'notification' => null);
        $this->config->shouldReceive('get')->andReturn($options, null);
        $this->assertEquals(
            $method->invoke(
                $this->initHipSupport(),
                array('foo' => 'baz', 'notification' => $notification)
            ),
            array('foo' => 'baz', 'notification' => $notification)
        );
    }

    /**
     * Test getHashFromUrl() method.
     */
    public function testGetHashFromUrl()
    {
        $method = $this->reflection('getHashFromUrl');
        $url = 'https://www.hipchat.com/abcd123AB';
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), $url),
            'abcd123AB'
        );
    }

    /**
     * Test appendUrlOptions() method.
     */
    public function testAppendUrlOptions()
    {
        $method = $this->reflection('appendUrlOptions');
        $url = 'https://www.hipchat.com/abcd123AB';
        $final_url = 'https://www.hipchat.com/abcd123AB?welcome_msg=' .
                     'foo&timezone=utc&minimal=true&anonymous=true';
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), $url, $this->mock_config),
            $final_url
        );

        $options = array_merge(
            $this->mock_config,
            array('welcome_msg' => 'foo bar baz foo\'s')
        );
        $final_url = 'https://www.hipchat.com/abcd123AB?welcome_msg=' .
                     'foo+bar+baz+foo%27s&timezone=utc&minimal=true&' .
                     'anonymous=true';
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), $url, $options),
            $final_url
        );

        $options = array_merge($this->mock_config, array('anonymous' => false));
        $final_url = 'https://www.hipchat.com/abcd123AB?welcome_msg=' .
                     'foo&timezone=utc&anonymous=false&minimal=true';
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), $url, $options),
            $final_url
        );
    }

    /**
     * Test booleanToString() method.
     */
    public function testBooleanToString()
    {
        $method = $this->reflection('booleanToString');
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), ''),
            'false'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), 0),
            'false'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), '0'),
            'false'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), false),
            'false'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), 'false'),
            'false'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), 'true'),
            'true'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), true),
            'true'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), 1),
            'true'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), '1'),
            'true'
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), 'bar'),
            'true'
        );
    }

    /**
     * Test notify() method.
     */
    public function testNotify()
    {
        $method = $this->reflection('notify');
        $options = $this->mock_config;

        $this->assertEquals(
            $method->invoke(
                $this->initHipSupport(),
                array('notification' => null)
            ),
            false
        );
        $this->assertEquals(
            $method->invoke($this->initHipSupport(), array()),
            false
        );
        $this->assertEquals(
            $method->invoke(
                $this->initHipSupport(),
                array('notification' => array())
            ),
            false
        );
        $this->assertEquals(
            $method->invoke(
                $this->initHipSupport(),
                array('notification' => array('room_id' => null))
            ),
            false
        );

        $this->hipchat
            ->shouldReceive('message_room')
            ->with(123456, 'HipSupport', 'foo', true, 'green', 'html')
            ->once()
            ->andReturn(true);

        $this->assertEquals(
            $method->invoke($this->initHipSupport(), $options),
            true
        );

        $this->hipchat
            ->shouldReceive('message_room')
            ->with(
                123456,
                'HipSupport',
                'Room name is foo.',
                true,
                'green',
                'html'
            )->once()
            ->andReturn(true);
        $options['notification']['message'] = 'Room name is [room_name].';
        $this->assertEquals(
            $method->invoke(
                $this->initHipSupport(),
                $options,
                (object) array('name' => 'foo')
            ),
            true
        );
    }
}
