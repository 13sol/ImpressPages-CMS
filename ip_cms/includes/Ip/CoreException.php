<?php
/**
 * @package ImpressPages
 * @copyright   Copyright (C) 2011 ImpressPages LTD.
 * @license GNU/GPL, see ip_license.html
 */

namespace Ip;


if (!defined('CMS')) exit;

/**
 * IpCmsException class
 */
class CoreException extends \Exception
{
    //error codes
    const DB = 0;
    const VIEW = 1;
    const EVENT = 2;
    const REVISION = 3;
    const WIDGET = 4;
    const SECURITY = 5;
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, \Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}