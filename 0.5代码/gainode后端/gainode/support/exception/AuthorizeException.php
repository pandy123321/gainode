<?php
/**
 * This file is part of cli.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace support\exception;

use Exception;
use library\dict\ErrorDict;
use library\service\sys\LangKeyService;

/**
 * Class AuthorizeException
 * @package support\exception
 */
class AuthorizeException extends Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        if(!empty($message)){
            $langKeyService = new LangKeyService();
            $langKeyService->saveTranslateValue($message,0,'exception',get_class($this));
        }
        parent::__construct($message, ErrorDict::TokenVerificationFailed, $previous);
    }
}
