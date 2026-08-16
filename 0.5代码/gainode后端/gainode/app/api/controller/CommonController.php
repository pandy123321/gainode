<?php

namespace app\api\controller;

use library\service\member\LevelService;
use library\service\sys\ArticleService;
use library\service\sys\CountryService;
use library\service\sys\LangService;
use support\controller\Api;
use support\Response;
use support\utils\Ip2Regions;

/**
 * 公共管理
 */
class CommonController extends Api
{

    /**
     * 获取语言列表
     * @method GET
     * @url /api/common/getLangList
     * @responseField string $code 语言编码
     * @responseField string $name 语言名称
     * @responseField string $locale 本地语言名称
     * @responseField string $image 语言图标
     * @return Response
     */
    public function getLangList(): Response
    {
        try{
            $langService = new LangService();
            $data = $langService->getLangList();
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取国家数据
     * @method GET
     * @url /api/common/getCountryList
     * @responseField string $id ID
     * @responseField string $name 国家名称
     * @responseField string $code 二字码
     * @responseField string $flag 图标
     * @responseField string $three_code 三字码
     * @responseField string $dial 电话区号
     * @return Response
     */
    public function getCountryList(): Response
    {
        try{
            $lang = $this->request->getLanguage();
            $countryService = new CountryService();
            $data = $countryService->getCountryList($lang);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取帮助内容
     * @method GET
     * @url /api/common/getHelpList
     * @responseField string $title 标题
     * @responseField string $content 内容
     * @return Response
     */
    public function getHelpList(): Response
    {
        try{
            $lang = $this->request->getLanguage();
            $articleService = new ArticleService();
            $data = $articleService->getHelpList($lang);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }



    /**
     * 获取等级数据
     * @method GET
     * @url /api/common/getLevelList
     * @responseField int $id 等级ID
     * @responseField string $name 等级名称
     * @responseField int $grade 等级编号
     * @responseField float $discount 分成比例百分比
     * @responseField float $profit 收益率百分比
     * @responseField float $money 可投入金额
     * @responseField float $amount 业绩额度
     * @responseField int $invite_cnt 邀请数量
     * @responseField string $descr 描述
     * @return Response
     */
    public function getLevelList(): Response
    {
        try{
            $levelService = new LevelService();
            $result = $levelService->getSelectList(0);
            return $this->json($result);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取IP数据
     * @param string $ip IP地址
     * @method GET
     * @url /api/common/getIpInfo
     * @responseField string $country 国家
     * @responseField string $province 省份
     * @responseField string $city 城市
     * @responseField string $isp 运营商
     * @responseField string $ip  IP
     * @responseField string $version 版本
     * @responseField string $region 地区
     * @return Response
     */
    public function getIpInfo(): Response
    {
        try{
            $ip = $this->request->get('ip');
            if(empty($ip)){
                $ip = $this->request->getRealIp();
            }
            $searcher = new Ip2Regions();
            $data = $searcher->getIpInfo($ip);
            $arr = explode('|',$data['region']);
            $data['ip'] = $ip;
            $data['region'] = $arr[0];
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
