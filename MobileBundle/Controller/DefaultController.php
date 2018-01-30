<?php

namespace MobileBundle\Controller;

use GuzzleHttp\Psr7\Response;
use MobileBundle\Entity\MobileBody;
use MobileBundle\Entity\MobileDetail;
use MobileBundle\Entity\MobileHead;
use MobileBundle\Service\MobileHeadCrawler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction($city = 'nanyang')
    {

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(MobileHead::class);
        $list = $repository->getListByName($city);
        if(empty($list)){
            $service = $this->get('mobile_head');
            $nums = $service->fetchThirdNum($city);
            $result = $repository->saveDomainNum($city, $nums);
            if($result){
                $list = $repository->getListByName($city);
            }else{
                $this->redirect('/');
            }
        }
        $vars = [
            'currentCityCode'=>$city,
            'pageTitle'=>'城市手机号码获取列表'
        ];
        //var_dump($list[0]->getBodies());exit;
        $vars['list'] = $list;
        //首先根据城市代码，获取连接
        $vars['sync_url'] = $this->generateUrl('mobile_sync_code');
        $vars['detail_url'] = $this->generateUrl('mobile_sync_details');
        $vars['load_body_url'] = $this->generateUrl('mobile_load_body');
        $vars['rep'] = $em->getRepository(MobileBody::class);
        return $this->render('MobileBundle:Default:index.html.twig', $vars);
    }
    public function syncCodeAction(Request $request) {
        $return = ['status'=>-1, 'message'=>'系统未知错误'];
        $post = $request->request;
        $domain = $post->get('city');
        if(!$domain){
            $return['message'] = '请选择城市代码';
        }else {
            $code = $post->get('code');
            if(!$code){
                $return['message'] = '请选择手机号位';
            }else{
                $em = $this->getDoctrine()->getManager();
                $headRep = $em->getRepository(MobileHead::class);
                $head = $headRep->loadByCodeAndDomain($code, $domain);
                if(!$head){
                    $return['message'] = '抱歉没有找到指定的地区手机信息';
                }else {
                    $service = $this->get('mobile_head');
                    $items = $service->fetchFourNum($code, $domain);
                    if (!$items) {
                        $return['message'] = '没有获取到号段信息';
                    } else {
                        $repository = $em->getRepository(MobileBody::class);
                        $result = $repository->saveBodyItems($head, $items);
                        if(empty($result)){
                            $return['message'] = '已经没有更多号段信息了';
                        }else{
                            $return['status'] = 1;
                            $return['message'] = '';
                            $return['data'] = [
                                'items'=>$items,
                            ];
                        }
                    }
                }
            }
        }

        return $this->json($return);
    }
    public function syncDetailsAction(Request $request) {
        set_time_limit(0);
        $return = ['status'=>-1, 'message'=>'系统未知错误'];
        $post = $request->request;
        $prevCode = $post->get('code');
        if(!$prevCode){
            $return['message'] = '缺少手机号段信息';
            return $this->json($return);
        }
        $em = $this->getDoctrine()->getManager();
        $reposity = $em->getRepository(MobileBody::class);
        $body = $reposity->loadByPrevCode($prevCode);
        if(!$body){
            $return['message'] = '没有找到指定手机号段信息';
            return $this->json($return);
        }
        $detailRep = $em->getRepository(MobileDetail::class);
        if($reposity->hasFillAllBy($body)){
            $return['message'] = '抱歉，此号段手机号信息已经存储完毕';
            return $this->json($return);
        }
        $result = $detailRep->buildDetailsFor($body);
        if(!$result){
            $return['message'] = '抱歉，生成手机号码信息出现错误';
            return $this->json($return);
        }
        $return['message'] = '';
        $return['status'] = 1;
        $return['data'] = [
            'total'=>$detailRep->calcTotalNum($body),
        ];
        return $this->json($return);
    }
    public function queryWechatAction(Request $request) {
        if($request->isXmlHttpRequest()){
            set_time_limit(0);
            $return = ['status'=>-1, 'message'=>'系统未知错误'];
            //$refer = 'https://mp.weixin.qq.com/cgi-bin/safecenterstatus?action=admins&t=setting/safe-admins&token=1380078097&lang=zh_CN';
            $post = $request->request;
            $refer = $post->get('refer_url');
            if(!$refer){
                $return['message'] = '请选择url';
                return $this->json($return);
            }
            $cookie = $post->get('refer_cookie');
            if(!$refer) {
                $return['message'] = '请选择cookie信息';
                return $this->json($return);
            }
            $hasError = false;
            $total = 0;
            $service = $this->get('mobile_head');
            $rep = $this->getDoctrine()->getManager()->getRepository(MobileDetail::class);
            $resolve = function(Response $response, $idx)use(&$return, &$hasError, &$total, $rep){
                $content = $response->getBody()->getContents();
                //file_put_contents('test.log', $content);
                $result = json_decode($content, true);
                if(!$result){
                    $hasError = true;
                    $return['message'] = '解析数据出现错误';
                }else if(!isset($result['base_resp'])){
                    $hasError = true;
                    $return['message'] = '没有找到主要的判断信息';
                }else {
                    $code = intval($result['base_resp']['ret']);
                    switch ($code){
                        case MobileHeadCrawler::CODE_FETCH_USER:
                        case MobileHeadCrawler::CODE_WECHAT_NO_PROTECT_LONG:
                        case MobileHeadCrawler::CODE_WECHAT_NO_PROTECT:
                        case MobileHeadCrawler::CODE_NOT_SUBSCRIBE:
                        case MobileHeadCrawler::CODE_NOT_SUBSCRIBE_LONG:
                        case MobileHeadCrawler::CODE_USER_OVER_ADD_NUM_LONG:
                        case MobileHeadCrawler::CODE_USER_OVER_ADD_NUM:
                        case MobileHeadCrawler::CODE_USER_BIND_ALREADY_LONG:
                        case MobileHeadCrawler::CODE_USER_BIND_ALREADY:
                            $final = $rep->markHasWechatById($idx, true);
                            if($final){
                                $total++;
                            }else{
                                $return['message'] = sprintf('在索引:%d处标记有微信出现错误', $idx);
                                $hasError = true;
                            }
                            //标记有微信
                            break;
                        case MobileHeadCrawler::CODE_NO_WECHAT:
                            //标记无微信
                            $final = $rep->markHasWechatById($idx, false);
                            if($final){
                                $total++;
                            }else{
                                $return['message'] = sprintf('在索引:%d处标记没有微信出现错误', $idx);
                                $hasError = true;
                            }
                            $total++;
                            break;
                        case -110:
                            $hasError = true;
                            $return['message'] = sprintf('抱歉，微信已标记此公众号操作频繁，请更换公众号信息,共处理%d条记录', $total);
                            break;
                        default:
                            $hasError = true;
                            $return['message'] = sprintf('抱歉，第%d个请求微信查询信息出现错误：code: %s, message: %s', $idx, $code, $result['base_resp']['err_msg']);
                            break;
                    }
                }
            };
            $reject = function($reason, $idx)use(&$return, &$hasError){
                $hasError = true;
                $return['message'] = "抱歉，第{$idx}个请求出现错误：{$reason}";
            };
            $promise = $service->sendQueryWechat($cookie, $service->iterateDetailsQuery($rep, $refer), $resolve,$reject);
            $promise->wait();
            if(!$hasError){
                $return['message'] = '';
                $return['status'] = 1;
                $return['data'] = [
                    'total'=>$total,
                ];
            }
            return $this->json($return);
        }
        $vars = [
            'pageTitle'=>'批量查询手机号微信信息',
            'actionUrl'=>$request->getRequestUri(),
        ];
        return $this->render('MobileBundle:Default:query.html.twig', $vars);
    }

    /**
     * 加载手机号段部分
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function loadBodiesAction(Request $request) {
        $return = ['status'=>-1, 'message'=>'系统未知错误'];
        $post = $request->request;
        $id = $post->getAlnum('head_id', 0);
        if(!$id){
            $return['message'] = '请选择需要加载的手机号位';
            return $this->json($return);
        }
        $em = $this->getDoctrine()->getManager();
        $rep = $em->getRepository(MobileHead::class);
        $head = $rep->find($id);
        if(!$head){
            $return['message'] = '没有找到指定手机号位';
            return $this->json($return);
        }
        $return['status'] = 1;
        $return['message'] = '';
        $return['data'] = [
            'items'=>[],
        ];
        $iterator = $head->getBodies()->getIterator();
        $repBody = $em->getRepository(MobileBody::class);
        foreach($iterator as $k=>$item){
            $return['data']['items'][$k] = [
                'prev_code'=>$item->getPrevCode(),
                'require_build'=>!$repBody->hasFillAllBy($item),
            ];
        }
        return $this->json($return);
    }
}
