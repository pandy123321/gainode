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

use library\dict\ErrorDict;
use library\service\sys\LangKeyService;

/**
 * Class AuthorizeException
 * BE-11 / issue-20260825-0002：继承 DomainException，使未登录/令牌失效
 * 按 05§7 映射为 AUTH_UNAUTHENTICATED / 401（原先兜底 INTERNAL_ERROR/500）。
 * @package support\exception
 */
class AuthorizeException extends DomainException
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        if(!empty($message)){
            $langKeyService = new LangKeyService();
            $langKeyService->saveTranslateValue($message,0,'exception',get_class($this));
        }
        parent::__construct(ErrorDict::AUTH_UNAUTHENTICATED, $message, $previous);
    }
}
