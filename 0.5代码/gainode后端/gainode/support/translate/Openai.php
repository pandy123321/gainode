<?php

namespace support\translate;

use support\utils\Curl;

/**
 * Openai操作类
 */
class Openai {

    public static function sendMsg($prompt,$return='message'){
        $api_key = getenv('OPENAI_API_KEY') ?: '';
        $url = 'https://api.openai.com/v1/completions';
        $headers = [
            "Content-Type:application/json",
            "Authorization: Bearer ".$api_key
        ];
        $params = [
            "model"=> "gpt-3.5-turbo",               //text-davinci-003,对话模型的名称
            "prompt"=> $prompt,
            "temperature"=> 0.7,                       //值在[0,1]之间，越大表示回复越具有不确定性
//            "max_tokens"=> 4096,                     //回复最大的字符数
            "top_p"=> 1,
            "frequency_penalty"=> 0,                   //[-2,2]之间，该值越大则更倾向于产生不同的内容
            "presence_penalty"=> 0,                    //[-2,2]之间，该值越大则更倾向于产生不同的内容
            "request_timeout"=>600,                    //请求超时时间，openai接口默认设置为600，对于难问题一般需要较长时间
            "timeout"=>60                              //重试超时时间，在这个时间内，将会自动重试
        ];
        $curl = Curl::getInstance();
        $curl->setHttpHeader($headers);
        $curl->setProxy('152.32.225.34:8888');

        $response = $curl->post($url,json_encode($params),$headers);

        if(!empty($response)){
            $result = json_decode($response,true);
            if($return=='message'){
                return $result['choices'][0]['text'];
            }
            return $result;
        }
        return null;
    }
}
