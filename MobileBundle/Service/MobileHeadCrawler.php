<?php
/**
 * Created by Moonpie Studio
 * User: Administrator
 * Date: 2017/9/28
 * Time: 15:25
 */

namespace MobileBundle\Service;


use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MobileBundle\Repository\MobileDetailRepository;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\DomCrawler\Crawler;

class MobileHeadCrawler {
    const FETCH_HEAD_URL = 'http://www.jihaoba.com/haoduan/%s/';
    const FETCH_BODY_URL = 'http://www.jihaoba.com/haoduan/%d/%s.htm';
    const QUERY_URL = 'https://mp.weixin.qq.com/cgi-bin/safecenterstatus';

    const CODE_FETCH_USER = 0;
    const CODE_NO_WECHAT_LONG = 200101;
    const CODE_NO_WECHAT = -101;
    const CODE_WECHAT_NO_PROTECT_LONG = 200102;
    const CODE_WECHAT_NO_PROTECT = -102;
    const CODE_NOT_SUBSCRIBE = -103;
    const CODE_NOT_SUBSCRIBE_LONG = 200103;
    const CODE_OVER_ADD_NUM_LONG = 200104;
    const CODE_OVER_ADD_NUM = -104;
    const CODE_USER_OVER_ADD_NUM_LONG = 200105;
    const CODE_USER_OVER_ADD_NUM = -105;
    const CODE_USER_BIND_ALREADY_LONG = 200106;
    const CODE_USER_BIND_ALREADY = -106;
    /**
     * 该公众号还未绑主管理员帐号，请绑定后再添加运营者微信号
     */
    const CODE_HAS_NO_MANAGER_LONG = 200107;
    const CODE_HAS_NO_MANAGER = -107;
    /**
     *该公众号已经绑定或邀请满25个运营者微信号，请尽快联系个人微信号进行确认
     */
    const CODE_OVER_INVITE_NUM_LONG = 200108;
    const CODE_OVER_INVITE_NUM = -108;
    /**
     *操作过于频繁，请稍后重试
     */
    const CODE_WECHAT_FORBIDE_OPERATE = -110;
    /**
     *该公众号已经绑定或邀请满5个长期运营者微信号，请尽快联系个人微信号进行确认
     */
    const CODE_HAS_LONG_MANAGER = -115;
    /**
     *该公众号已经绑定或邀请满20个短期运营者微信号，请尽快联系个人微信号进行确认
     */
    const CODE_HAS_SHORT_MANAGER = -116;
    /**
     *新管理员必须和主体身份一致
     */
    const CODE_MANAGE_ID_SHOULD_EQ = -118;
    protected $client;
    public function __construct(Client $client) {
        $this->client = $client;
    }
    public function fetchThirdNum($domain) {
        $nums = [];
        $url = sprintf(self::FETCH_HEAD_URL, $domain);
        $crawler = $this->client->request('GET', $url);

        $crawler->filter('.hd_result')->eq(0)->filter('.hd_mar > .hd_number > a')->each(function(Crawler $node)use(&$nums){
            $text = $node->text();
            $nums[] = substr($text, -3);
        });
        return $nums;
    }
    public function fetchFourNum($third, $domain) {
        $url = sprintf(self::FETCH_BODY_URL, $third, $domain);
        $crawler = $this->client->request('GET', $url);
        $idx = 0;
        $return = [];
        $crawler->filter('ul.hd-city')->eq(1)->filter('li')->each(function(Crawler $node)use(&$idx,&$return){
            $class = $node->attr('class');
            switch ($class) {
                case 'hd-city01':
                    $return[$idx]['prev_code'] = $node->text();
                    $return[$idx]['code'] = substr($node->text(), 3);
                    break;
                case 'hd-city02':
                    $return[$idx]['province'] = $node->text();
                    break;
                case 'hd-city03':
                    $return[$idx]['city'] = $node->text();
                    break;
                case 'hd-city04':
                    $return[$idx]['zipcode'] = $node->text();
                    break;
                case 'hd-city06':
                    $return[$idx]['carrier'] = $node->text();
                    break;
                case 'hd-city07':
                    $return[$idx++]['card_type'] = $node->text();
                    break;
            }
        });
        return $return;
    }
    public function sendQueryWechat($cookieString, $iterator, $resolve, $reject) {
        //$path = parse_url($referrer);
        //parse_str($path['query'], $query);
        $items = explode(';', $cookieString);
        $return = [];
        foreach($items as $item){
            list($key, $value) = explode('=', $item);
            $return[trim($key)] = trim($value);
        }
        /**
         * @var \GuzzleHttp\Client $http
         */
        $http = $this->client->getClient();
        $cookie = CookieJar::fromArray($return, 'mp.weixin.qq.com');
        $options = [
            //'headers'=>[
            //    'Host'=>'mp.weixin.qq.com',
            //    'Origin'=>'https://mp.weixin.qq.com',
            //    'Referer'=>$referrer,
            //    'User-Agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            //    'X-Requested-With'=>'XMLHttpRequest',
            //],
            'cookies'=>$cookie,
            'delay'=>6000,
            //'form_params'=>[
            //    'token'=>$query['token'],
            //    'lang'=>$query['lang'],
            //    'f'=>'json',
            //    'ajax'=>1,
            //    'action'=>'check_user',
            //    'expire_time'=>0
            //]
        ];
        //unset($options['headers'], $options['form_params']);
        $setting = ['concurrency'=>1,'options'=>$options,'fulfilled'=>$resolve, 'rejected'=>$reject];
        $pool = new Pool($http, $iterator, $setting);
        $promise = $pool->promise();
        return $promise;
    }
    public function queryWechatHeaders($refer) {
        return [
            'Host'=>'mp.weixin.qq.com',
            'Origin'=>'https://mp.weixin.qq.com',
            'Referer'=>$refer,
            'User-Agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'X-Requested-With'=>'XMLHttpRequest',
        ];
    }
    public function queryWechatQuery($refer, $username) {
        $path = parse_url($refer);
        parse_str($path['query'], $query);
        $query['f'] = 'json';
        $query['ajax'] = 1;
        $query['action'] = 'check_user';
        $query['expire_time'] = 0;
        $query['username'] = $username;
        return $query;
    }

    /**
     * 测试部分
     * @param array $mobiles
     * @param       $refer
     * @return \Generator
     */
    public function iterateQuery(array $mobiles, $refer) {
        $header = $this->queryWechatHeaders($refer);
        foreach($mobiles as $mobile){
            $query = $this->queryWechatQuery($refer, $mobile);
            yield new Request('post', self::QUERY_URL, $header, http_build_query($query));
        }
    }

    /**
     * 正式部分
     * @param MobileDetailRepository $repository
     * @param                        $refer
     * @param                        $limit
     * @return \Generator
     */
    public function iterateDetailsQuery(MobileDetailRepository $repository, $refer, $limit = 1) {
        $items = $repository->iterateUnhandleResults($limit);
        $header = $this->queryWechatHeaders($refer);
        foreach($items as $item) {
            $entity = $item[0];
            $mobile = $entity->getContent();
            $id = $entity->getId();
            $query = $this->queryWechatQuery($refer, $mobile);
            yield $id => new Request('post', self::QUERY_URL, $header, http_build_query($query));
        }
    }
    public function requestQueryWechat($referrer, $cookieString, $username) {
        $path = parse_url($referrer);
        parse_str($path['query'], $query);
        $items = explode(';', $cookieString);
        $return = [];
        foreach($items as $item){
            list($key, $value) = explode('=', $item);
            $return[trim($key)] = trim($value);
        }
        /**
         * @var \GuzzleHttp\Client $http
         */
        $http = $this->client->getClient();
        $cookie = CookieJar::fromArray($return, 'mp.weixin.qq.com');
        $options = [
            'headers'=>[
                'Host'=>'mp.weixin.qq.com',
                'Origin'=>'https://mp.weixin.qq.com',
                'Referer'=>$referrer,
                'User-Agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                'X-Requested-With'=>'XMLHttpRequest',
            ],
            'cookies'=>$cookie,
            'delay'=>6000,
            'form_params'=>[
                'token'=>$query['token'],
                'lang'=>$query['lang'],
                'f'=>'json',
                'ajax'=>1,
                'action'=>'check_user',
                'username'=>$username,
                'expire_time'=>0
            ]
        ];
        return $http->post(self::QUERY_URL, $options);
    }
    public function sendRequest($referrer, $cookieString, $username) {
        $path = parse_url($referrer);
        parse_str($path['query'], $query);
        $items = explode(';', $cookieString);
        $return = [];
        foreach($items as $item){
            list($key, $value) = explode('=', $item);
            $return[trim($key)] = trim($value);
        }
        /**
         * @var \GuzzleHttp\Client $http
         */
        $http = $this->client->getClient();
        $cookie = CookieJar::fromArray($return, 'mp.weixin.qq.com');
        $options = [
            'headers'=>[
                'Host'=>'mp.weixin.qq.com',
                'Origin'=>'https://mp.weixin.qq.com',
                'Referer'=>$referrer,
                'User-Agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                'X-Requested-With'=>'XMLHttpRequest',
            ],
            'cookies'=>$cookie,
            'delay'=>6000,
            'form_params'=>[
                'token'=>$query['token'],
                'lang'=>$query['lang'],
                'f'=>'json',
                'ajax'=>1,
                'action'=>'check_user',
                'username'=>$username,
                'expire_time'=>0
            ]
        ];
        $request = new Request('POST', self::QUERY_URL, $options['headers'], http_build_query($options['form_params']));
        unset($options['headers'], $options['form_params']);
        return $http->send($request, $options);
    }
    public function isMarkAsHasWechatCode($code) {
        switch ($code){
            case self::CODE_FETCH_USER:
            case self::CODE_WECHAT_NO_PROTECT_LONG:
            case self::CODE_WECHAT_NO_PROTECT:
            case self::CODE_NOT_SUBSCRIBE:
            case self::CODE_NOT_SUBSCRIBE_LONG:
            case self::CODE_USER_OVER_ADD_NUM_LONG:
            case self::CODE_USER_OVER_ADD_NUM:
            case self::CODE_USER_BIND_ALREADY_LONG:
            case self::CODE_USER_BIND_ALREADY:
                return true;
            default:
                return false;
        }
    }
}