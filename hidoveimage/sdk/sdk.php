<?php
/**
 * PHP SDK for tietuku.cn
 *
 * @author Tears <i@ltteam.cn>
 */

/**
 * 贴图库 Token 生成类
 *
 * 生成机制说明请大家参考贴图库开放平台文档：{@link http://open.tietuku.cn/doc#safe-token}
 *
 * @package TieTuKu
 * @author Tears
 * @version 1.0
 */
class TieTuKuToken
{
    /**
     * @ignore
     */
    public $accesskey;
    /**
     * @ignore
     */
    public $secretkey;
    /**
     * @ignore
     */
    private $base64param;
    /**
     * 构造函数
     *
     * @access public
     * @param mixed $accesskey 贴图库平台accesskey
     * @param mixed $secretkey 贴图库平台secretkey
     * @return void
     */
    public function __construct($accesskey, $secretkey)
    {
        if ($accesskey == '' || $secretkey == '') {
            return false;
        }

        $this->accesskey = $accesskey;
        $this->secretkey = $secretkey;
    }
    /**
     * 将参数进行JSON格式化并且进行url安全的base64编码
     *
     * @param array $param 接口所需要的参数
     * @return mixed 返回该类 可进行连续操作
     */
    public function dealParam($param)
    {
        $this->base64param = $this->URLSafeBase64Encode(json_encode($param));
        return $this;
    }
    /**
     * 生成Token方法
     * 需要先调用dealParam方法否则返回false
     *
     * @return string 成功生成的Token 失败返回false
     */
    public function createToken()
    {
        /*
        if(empty($this->base64param)) return false;
        $sign = $this->signEncode($this->base64param,$this->secretkey);
        return $this->accesskey.':'.$this->URLSafeBase64Encode($sign).':'.$this->base64param;
         */
        return $this->accesskey;
    }
    /**
     * Token hash加密方法
     *
     * @param string $str 需要进行hash加密的字符串
     * @param string $key secretkey
     * @return string hash_hmac sha1 加密后的字符串
     */
    public function signEncode($str, $key)
    {
        $hmac_sha1_str = "";
        if (function_exists('hash_hmac')) {
            $hmac_sha1_str = hash_hmac("sha1", $str, $key, true);
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac_sha1_str = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $str))));
        }
        return $hmac_sha1_str;
    }
    /**
     * url安全的base64编码 URLSafeBase64Encode
     *
     * @param string $str 需要进行url安全的base64编码的字符串
     * @return string 返回url安全的base64编码字符串
     */
    public function URLSafeBase64Encode($str)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($str));
    }
}
/**
 * 贴图库 客户端操作类
 *
 *
 * @package TieTuKu
 * @author Tears
 * @version 1.0
 */
class TTKClient
{

    /**
     * Set up the API root URL.
     *
     * @ignore
     */
    public $upload_host = "https://img.abcyun.co/api/upload";
    /**
     * Set timeout default.
     *
     * @ignore
     */
    public $timeout = 60;
    /**
     * Set CURL timeout.
     *
     * @ignore
     */
    public $CURLtimeout = 30;
    /**
     * 构造函数
     *
     * @access public
     * @param mixed $accesskey 贴图库平台accesskey
     * @param mixed $secretkey 贴图库平台secretkey
     * @return void
     */
    public function __construct($accesskey, $secretkey)
    {
        $this->op_Token = new TieTuKuToken($accesskey, $secretkey);
        # $this->op_Token = $accesskey;
    }

    /**
     * 上传单个文件到贴图库
     *
     * 对应API：{@link http://open.tietuku.cn/doc#upload}
     *
     * @access public
     * @param int $aid 相册ID
     * @param array $file 上传的文件。
     * @param array $filename 图片名称
     * @param array $is_ssl 返回是否ssl
     * @return string 如果$file!=null 返回请求接口的json数据否则只返回Token
     */
    public function uploadFile($aid, $file = null, $filename = null, $is_ssl = 0)
    {
        $url = $this->upload_host;
        $param['deadline'] = time() + $this->timeout;
        $param['album'] = $aid;
        $param['httptype'] = $is_ssl ? 2 : 0;
        $Token = $this->op_Token->dealParam($param)->createToken();
        $data['Token'] = $Token;
        $data['file'] = '@' . $file . (empty($filename) ? '' : (';filename=' . $filename));
        return empty($file) ? $Token : $this->post($url, $data);
    }

    /**
     * 上传网络文件到贴图库 (只支持单个连接)
     *
     * @access public
     * @param int $aid 相册ID
     * @param string $fileurl 网络图片地址
     * @return string 如果$fileurl!=null 返回请求接口的json数据否则只返回Token
     */
    public function uploadFromWeb($aid, $fileurl = null, $is_ssl = 0)
    {
        $url = $this->upload_host;
        $param['deadline'] = time() + $this->timeout;
        $param['aid'] = $aid;
        $param['from'] = 'web';
        $param['httptype'] = $is_ssl ? 2 : 0;
        var_dump($param);exit;
        $Token = $this->op_Token->dealParam($param)->createToken();
        $data['Token'] = $Token;
        $data['fileurl'] = $fileurl;
        return empty($url) ? $Token : $this->post($url, $data);
    }
    /**
     * 对接口post数据
     *
     *
     * @access public
     * @param string $url 接口请求地址。
     * @param array $data 需要post的数据
     * @return string 返回的json数据
     */
    public function post($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->CURLtimeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}
