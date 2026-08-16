<?php

namespace support\web3;

use Elliptic\EC;
use kornrunner\Keccak;

class AccountCreator
{
    /**
     * @param $chain {TRON,EVM}
     * @return array
     */
    public static function createAccount($chain)
    {
        // 生成随机私钥
        $privateKey = bin2hex(random_bytes(32));

        // 使用椭圆曲线加密从私钥生成公钥
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey);
        $publicKey = $key->getPublic(false, 'hex');

        // 移除公钥前缀04
        $publicKey = substr($publicKey, 2);

        // 计算 Keccak-256 哈希
        $hash = Keccak::hash(hex2bin($publicKey), 256);

        if($chain=='TRON'){
            $prefix = '41';
        }
        elseif($chain=='EVM'){
            $prefix = '0x';
        }
        else{
            throw new \Exception('Invalid chain');
        }

        // 取最后20字节作为地址
        $address = $prefix . substr($hash, -40);    // Tron地址以41开头，EVM以0x开头
        $cdata = [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
            'wallet_address' => $address,
        ];
        if($prefix=='41'){
            $cdata['descr'] = $address;
            $cdata['wallet_address'] = self::toBase58Check($address);
        }
        return $cdata;
    }

    /**
     * 从私钥生成钱包地址
     * @param string $privateKey 私钥
     * @param string $chain {TRON,EVM}
     */
    public static function privateKeyToAddress(string $privateKey,$chain="TRON"): string
    {
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($privateKey);
        $publicKey = $keyPair->getPublic(false, 'hex');

        // 移除公钥前缀04
        $publicKeyBytes = hex2bin(substr($publicKey, 2));
        $hash = Keccak::hash($publicKeyBytes, 256);

        if($chain=='TRON'){
            // Tron地址以41开头
            $addressHex = '41' . substr($hash, -40);
            // 转换为Base58Check
            return self::toBase58Check($addressHex);
        }
        elseif($chain=='EVM'){
            // 取最后20字节并添加0x前缀
            return '0x' . substr($hash, -40);
        }
        else{
            throw new \Exception('Invalid chain');
        }
    }

    private static function toBase58Check($hexString) {
        // 1. 将十六进制字符串转换为字节数组
        $bytes = array_map('hexdec', str_split($hexString, 2));

        // 2. 计算双SHA256哈希的前4字节作为校验和
        $hash1 = hash('sha256', pack('C*', ...$bytes), true);
        $hash2 = hash('sha256', $hash1, true);
        $checksum = substr($hash2, 0, 4);

        // 3. 添加校验和
        $bytesWithChecksum = array_merge(
            $bytes,
            unpack('C*', $checksum)
        );

        // 4. 转换为Base58
        return self::encodeBase58($bytesWithChecksum);
    }

    private static function encodeBase58($byteArray)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);

        // 处理前导零
        $leadingZeros = 0;
        while ($leadingZeros < count($byteArray) && $byteArray[$leadingZeros] === 0) {
            $leadingZeros++;
        }

        // 转换为大整数
        $number = gmp_init(0);
        foreach ($byteArray as $byte) {
            $number = gmp_mul($number, 256);
            $number = gmp_add($number, $byte);
        }

        // 转换为Base58
        $result = '';
        while (gmp_cmp($number, 0) > 0) {
            list($number, $remainder) = gmp_div_qr($number, $base);
            $result = $alphabet[gmp_intval($remainder)] . $result;
        }
        // 添加前导零对应的'1'
        for ($i = 0; $i < $leadingZeros; $i++) {
            $result = $alphabet[0] . $result;
        }
        return $result;
    }

    // Base58解码
    private static function decodeBase58(string $base58): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);

        // 处理前导零
        $leadingZeros = 0;
        while ($leadingZeros < strlen($base58) && $base58[$leadingZeros] === $alphabet[0]) {
            $leadingZeros++;
        }

        // 转换为大整数
        $number = gmp_init(0);
        for ($i = $leadingZeros; $i < strlen($base58); $i++) {
            $char = $base58[$i];
            $pos = strpos($alphabet, $char);
            if ($pos === false) return '';
            $number = gmp_mul($number, $base);
            $number = gmp_add($number, $pos);
        }

        // 转换为字节数组
        $bytes = [];
        while (gmp_cmp($number, 0) > 0) {
            list($number, $remainder) = gmp_div_qr($number, 256);
            $bytes[] = gmp_intval($remainder);
        }
        $bytes = array_reverse($bytes);

        // 添加前导零
        for ($i = 0; $i < $leadingZeros; $i++) {
            array_unshift($bytes, 0);
        }

        return pack('C*', ...$bytes);
    }

    // 验证Tron地址格式
    public static function isValidTronAddress(string $address): bool
    {
        if (strlen($address) !== 34) return false;

        // 解码Base58地址
        $decoded = self::decodeBase58($address);
        if (strlen($decoded) < 4) return false;

        // 分离地址和校验和
        $data = substr($decoded, 0, -4);
        $checksum = substr($decoded, -4);

        // 计算校验和
        $hash1 = hash('sha256', $data, true);
        $hash2 = hash('sha256', $hash1, true);
        $expectedChecksum = substr($hash2, 0, 4);

        return $checksum === $expectedChecksum;
    }
}
