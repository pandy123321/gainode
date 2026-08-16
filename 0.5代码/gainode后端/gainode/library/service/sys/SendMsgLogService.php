<?php

namespace library\service\sys;

use library\model\sys\SendMsgLogModel;
use library\dao\sys\SendMsgLogDao;
use support\exception\VerifyException;
use support\extend\Cache;
use support\extend\Log;
use support\extend\Service;

/**
 * Service
 * @method SendMsgLogModel create($data)
 * @method SendMsgLogModel updateOrCreate(array $params,array $data)
 * @method SendMsgLogModel update($id,array $data){
 * @method SendMsgLogModel get($id,string $field = null)
 * @method SendMsgLogModel find($id)
 * @method SendMsgLogModel findOrFail($id)
 * @method SendMsgLogModel firstOrCreate(array $params,array $data)
 * @method SendMsgLogModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class SendMsgLogService extends Service
{
    public function __construct()
    {
        $this->dao = SendMsgLogDao::class;
        parent::__construct();
    }

    public function createSmsLogs($send_type,$send_to,$content,$title=null){
        $code_click = 'click_'.md5($send_type.'_'.$send_to);
        $isSend = Cache::get($code_click);
        if(!empty($isSend)){
            throw new VerifyException("验证码已发送，请等待1分钟后再请求新的验证码",\library\dict\ErrorDict::PleaseDoNotResubmit);
        }
        $sendMsgLog = $this->create([
            'send_type'=>$send_type,
            'send_to'=>$send_to,
            'content'=>$content,
            'title'=>$title
        ]);
        if(empty($sendMsgLog)){
            throw new VerifyException('创建发送记录失败');
        }
//        if($sendMsgLog->send_type=='email'){
//            $mailService = new \support\mailer\SwiftMailer();
//            $res = $mailService->send($send_to,$title,$content);
//            if(!$res){
//                throw new VerifyException($mailService->getErrorMsg());
//            }
//        }
//        else{
//            $smsService = new \support\mailer\Smsbao();
//            $res = $smsService->sendMsg($send_to,$content);
//            if(is_null($res)){
//                throw new \support\exception\VerifyException("发送失败");
//            }
//            elseif(!$res){
//                throw new \support\exception\VerifyException($smsService->getError());
//            }
//        }
        Log::channel('api')->info($content,['type'=>$send_type]);
        Cache::set($code_click,1,60);
        return $sendMsgLog;
    }
}
