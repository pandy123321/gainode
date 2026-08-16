<?php

namespace support\utils;

class Ip2Regions
{
    /**
     * @var \Ip2Region
     */
    private $client = null;

    public function __construct( $dbPathV4 = null, $dbPathV6 = null)
    {
        if(empty($dbPathV4)){
            $dbPathV4 = resource_path("region/ip2region_v4.xdb");
        }
        if(empty($dbPathV6)){
            $dbPathV6 = resource_path("region/ip2region_v6.xdb");
        }
        $this->client = new \Ip2Region('file', $dbPathV4,$dbPathV6);
    }

    public function getIpInfo($ip): array
    {
        return $this->client->getIpInfo($ip);
    }
}
