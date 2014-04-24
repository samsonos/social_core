<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 22.04.14 at 16:04
 */
namespace samson\social\tests;

define('__VENDOR_PATH', '/var/www.prod/vendor/');

require_once(__VENDOR_PATH.'samsonos/php/core/samson.php');
require_once(__DIR__.'/../Core.php');

class CoreTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthorize()
    {
        // Create object instance
        $core = new \samson\social\Core(getcwd());

        $this->assertEquals(0, 0);
        //$core->authorize();
    }
}
 