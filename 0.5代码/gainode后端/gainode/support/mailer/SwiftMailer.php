<?php

namespace support\mailer;

use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;

/**
 * 邮件发送接口
 * 587:tls
 * 465:ssl
 */
class SwiftMailer
{
    private $smtpHost;
    private $smtpEmail;
    private $smtpPass;
    private $smtpPort;
    private $fromEmail;
    private $fromName;
    private $encryption;
    public $charset = 'UTF-8';
    public $contentType = 'text/html';

    /**
     * @var Swift_Mailer
     */
    private $mailerClient;
    private $errorMsg;

    public function __construct(array $data=null) {
        error_reporting(0);
        if(empty($data)){
            $this->smtpHost = env('MAIL_HOST', 'smtp.mailgun.org');
            $this->smtpEmail = env('MAIL_USERNAME');
            $this->smtpPass = env('MAIL_PASSWORD');
            $this->smtpPort = env('MAIL_PORT', 587);
            $this->fromEmail = env('MAIL_FROM_ADDRESS');
            $this->fromName = env('MAIL_FROM_NAME');
            $this->encryption = env('MAIL_ENCRYPTION', 'tls');
        }
        else{
            foreach($data as $k=>$v){
                if(isset($this->$k)){
                    $this->$k = $v;
                }
            }
        }
        $transport = new Swift_SmtpTransport($this->smtpHost, $this->smtpPort,$this->encryption);
        $transport->setUsername($this->smtpEmail);
        $transport->setPassword($this->smtpPass);
        $this->mailerClient = new Swift_Mailer($transport);
    }

    /**
     * 单个人发送邮件类
     * @param <$mailto> 发送给谁
     * @param <$mail_title> 邮件主题
     * @param <$mail_body> 邮件内容
     */
    public function send(string $mailto, string $mail_title, string $mail_body,array $attach=[])
    {
        $from = [$this->fromEmail => $this->fromName];
        $to = [$mailto => 'receiver'];
        $message = new Swift_Message($mail_title);
        $message->setFrom($from);
        $message->setTo($to);
        if($this->contentType=='text/html'){
            $message->setBody($mail_body, 'text/html');
        }
        else{
            $message->addPart($mail_body, 'text/plain');
        }
        $failures = '';
        if ($recipients = $this->mailerClient->send($message, $failures)) {
            return true;
        } else {
            $this->errorMsg = $failures;
            return false;
        }
    }

    /**
     * 发送多个邮件信息
     * @param <$mailto> 发送给谁 ["11421412@qq.com"=>"名字"]
     * @param <$mail_title> 邮件主题
     * @param <$mail_body> 邮件内容
     * @param <$attach> 附件
     */
    public function sendAll(array $mailto, string $mail_title,string $mail_body,$attach=[]){
        $from = [$this->fromEmail => $this->fromName ];
        $message = new Swift_Message($mail_title);
        $message->setFrom($from);
        $message->setTo($mailto);
        // 设置邮件回执
//        $message->setReadReceiptTo('ruckcc@126.com');
//        if(!empty($attach)){
        //创建attachment对象，content-type这个参数可以省略
//            $attachment = Swift_Attachment::fromPath('image.jpg', 'image/jpeg')->setFilename('cool.jpg');
        //添加附件
//            $message->attach($attachment);
//        }
//       添加抄送人
//       $message->setCc(['373953541@qq.com' => 'Cc']);
//       添加密送人
//       $message->setBcc(array(
//            'Bcc@qq.com' => 'Bcc'
//       ));
        if($this->contentType=='text/html'){
            $message->setBody($mail_body, 'text/html');
        }
        else{
            $message->addPart($mail_body, 'text/plain');
        }
        $failures = '';
        if ($recipients = $this->mailerClient->send($message, $failures)) {
            return true;
        } else {
            $this->errorMsg = $failures;
            return false;
        }
    }

    public function getErrorMsg(){
        return $this->errorMsg;
    }
}
