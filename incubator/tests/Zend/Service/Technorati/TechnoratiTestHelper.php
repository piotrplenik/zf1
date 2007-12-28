<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service_Technorati
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id$
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Test helper
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../TestHelper.php';

/**
 * Patch for default timezone in PHP >= 5.1.0
 */
if (!ini_get('date.timezone')) {
    date_default_timezone_set(@date_default_timezone_get());
}

/**
 * @see Zend_Service_Technorati
 */
require_once 'Zend/Service/Technorati.php';


PHPUnit_Util_Filter::addFileToFilter(__FILE__);


/**
 * @category   Zend
 * @package    Zend_Service_Technorati
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Technorati_TechnoratiTestHelper
{
    public static function getTestFilePath($file)
    {
        return dirname(__FILE__) . '/_files/' . $file;
    }

    public static function getTestFileContentAsDom($file)
    {
        $dom = new DOMDocument();
        $dom->load(self::getTestFilePath($file));
        return $dom;
    }

    public static function getTestFileElementsAsDom($file, $exp = '//item')
    {
        $dom = self::getTestFileContentAsDom($file);
        $xpath = new DOMXPath($dom);
        return $xpath->query($exp);
    }

    public static function getTestFileElementAsDom($file, $exp = '//item', $item = 0)
    {
        $dom = self::getTestFileContentAsDom($file);
        $xpath = new DOMXPath($dom);
        $domElements = $xpath->query($exp);
        return $domElements->item($item);
    }

    public static function skipInvalidArgumentTypeTests()
    {
        // PHP < 5.2.0 returns a fatal error
        // instead of a catchable Exception (ZF-2334)
        return version_compare(phpversion(), "5.2.0", "<");
    }
}
