<?php
/**
 * Copyright (c) 2017 Josh P (joshp.xyz).
 */

namespace J0sh0nat0r\SimpleCache\Tests;

/**
 * Tests a driver
 */
abstract class DriverTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * An instance of the driver to test.
     *
     * @var \J0sh0nat0r\SimpleCache\IDriver
     */
    protected $driver;

    public function tearDown()
    {
        $this->driver->clear();

        unset($this->driver);
    }

    public function testSet()
    {
        $this->assertTrue($this->driver->put('foo', 'bar', 0));
    }

    /**
     * @depends testSet
     */
    public function testHas()
    {
        $this->assertFalse($this->driver->has('foo'));

        $this->assertTrue($this->driver->put('foo', 'bar', 0));

        $this->assertTrue($this->driver->has('foo'));
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $this->driver->put('foo', 'bar', 0);

        $this->assertEquals('bar', $this->driver->get('foo'));
        $this->assertNull($this->driver->get('baz'));
    }

    /**
     * @depends testSet
     * @depends testHas
     */
    public function testRemove()
    {
        $this->driver->put('foo', 'bar', 0);

        $this->assertTrue($this->driver->remove('foo'));
        $this->assertFalse($this->driver->has('foo'));
    }

    /**
     * @depends testSet
     * @depends testHas
     */
    public function testClear()
    {
        $this->driver->put('foo', 'bar', 0);
        $this->driver->put('baz', 'qux', 0);

        $this->driver->clear();

        $this->assertFalse($this->driver->has('foo'));
        $this->assertFalse($this->driver->has('baz'));
    }

    /**
     * @depends testSet
     * @depends testHas
     */
    public function testItemExpiration()
    {
        $this->driver->put('foo', 'bar', 1);

        sleep(2);

        $this->assertFalse($this->driver->has('foo'));
    }

    /**
     * @depends testSet
     * @depends testGet
     */
    public function testItemOverwriting()
    {
        $this->driver->put('foo', 'bar', 0);

        $this->assertEquals('bar', $this->driver->get('foo'));

        $this->assertTrue($this->driver->put('foo', 'baz', 10));

        $this->assertEquals('baz', $this->driver->get('foo'));
    }
}