﻿<?php
/* 
* @bill
* @授权管理
* @date:20150515
* @授权管理
*/
class oauthAction extends frontendAction
{
	private $wdata=array();
	
	private $url;
	private $pathurl;	
	public function _initialize() { 
        parent::_initialize();	
		$this->url=	'http://'.$_SERVER['HTTP_HOST'];
		$this->pathurl=$this->url.'/data/upload/reply_image/';	
    }	
	//微信授权中心
	public function index(){
		//第一步 微信授权，获取授权access_token 
		$api=M('weixin')->find();
		$appid = $api['appid'];  
		$secret = $api['secret'];
		$code = $_GET["code"];
		$get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
		$json_obj=$this->post_data($get_token_url);
		$json_obj = json_decode($json_obj,true);
		//第二步 根据openid和access_token查询用户信息--不强制关注用此方法
		$access_token = $json_obj['access_token'];
		$openid = $json_obj['openid'];  
		$get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid;  
		$res_user_info=$this->post_data($get_user_info_url);
		$res_user_info = json_decode($res_user_info,true);
		$openid=$res_user_info['openid'];
		//获取用户详细资料
		$user=D('user')->where(array('openid'=>$openid))->find();
		$data['username']=$res_user_info['nickname'];
		$data['sex']=$res_user_info['sex'];
		$data['province']=$res_user_info['province'];
		$data['city']=$res_user_info['city'];
		$data['thumb']=$res_user_info['headimgurl'];
		$data['img']=$res_user_info['headimgurl'];
		$data['language']=$res_user_info['language'];
		$data['country']=$res_user_info['country'];		
		$ip=get_client_ip();
		$time=time();
		$data['up_time']=$time;
		$data['up_ip']=$ip;
		$data['last_time']=$time;
		$data['last_ip']=$ip;
        $role_id=0;			
		if($user){
			D('user')->where(array('openid'=>$openid))->save($data);
            $role_id=$user['role_id'];
		}else{
			$data['openid']=$openid;
			$data['reg_time']=$time;
			$data['reg_ip']=$ip;
			$data['role_id']=1;
			$data['start_time']=$time;
			$data['end_time']=0;
			D('user')->add($data);
            $role_id=1;
		}    
		session_start();
		$session_id=session_id();
		$_SESSION['user']=$res_user_info;
		$url=$_SESSION['url'];
		unset($_SESSION['url']);
		header('Location:'.$url);
	}
	//接口对接
	public function api(){
		import("ORG.Weixin.Wechat");
		$token='weiyou';
		$weixin = new Wechat($token);
		$this->wdata = $weixin->request();
	
		list($content,$type) = $this->reply($this->wdata);
		$weixin->response($content,$type);
	}

	//关注时操作
	protected function reply($data){
		$this->wdata=$data;
		switch($this->wdata['Event']){
			case 'subscribe'://关注时回复
			// 	//业务处理
			// 	$res=$this->is_subscribe($this->wdata);
			// 	//return array($res,'text');
			// 	//回复处理
			// 	//return array($text,'text');
	//	if ($this->wdata['Event'] == 'subscribe') {
			$random = '/data/www/html/www.wechatw.com/data/tmpopenid/'.$this->wdata['FromUserName'];
			$data = serialize($this->wdata);
			// $result = D('file')->save($openid);
			//$random = random(10, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
			
			$file = fopen($random.".txt","w+");
			if (flock($file,LOCK_EX)){
				fwrite($file , $data);
			  	flock($file,LOCK_UN);
			}
			fclose($file);
	//}
		
		// file_put_contents($openid.'.txt', $this->wdata); 
				return $this->reply_subscribe();
				break;
			case 'unsubscribe'://取消关注
			    $openid = $this->wdata['FromUserName'];
                D('user')->where(array('openid'=>$openid))->setField('trade_openid','0');
                return;
				break;
			case 'CLICK'://自定义菜单回复
				$this->wdata['Content']= $this->wdata['EventKey'];
				break;
			default:
				return $this->reply_defined($this->wdata['Content'],'trim');//自定义回复
				break;
		}
	}

	//处理关注事件
	public function optionopenid(){
		$dir = '/data/www/html/www.wechatw.com/data/tmpopenid';
		if($this->dir_is_empty($dir)){
			//如果为空执行定时程序重新执行
			echo '  not is file';
		}else{
			if($handle = opendir($dir)){   //打开目录
				while(($file = readdir($handle)) !== false){  //读取文件名
					if($file !="." && $file !=".."){    //排除上级目录 和当前目录
						$fp = fopen($dir."/".$file , 'r');
						if ($fp) {
							if(flock($fp , LOCK_EX)){     
								    $fread = fgets($fp);
									$wdata = unserialize($fread);
									// var_dump($fread);
									$res = $this->is_subscribe($wdata);   
									fclose($fp);
								   @unlink($dir."/".$file);
								}else{
                                   echo 'Lock failed!';
								}     
								
							}else{
								echo 'File open failed!';
							} 
						}else{
							echo ' not is File !';
						}  
					}
				}
			}
		
	}

	//文件目录判断
	protected function dir_is_empty($dir){ 
		if($handle = opendir($dir)){
			while($item = readdir($handle)){
				if ($item != '.' && $item != '..')return false;
			}
		} 
		return true;
	}

	//关注时业务操作
	protected function is_subscribe($wdata){
		$EventKey=$wdata['EventKey'];
		$openid=$wdata['FromUserName'];
		$Event=$wdata['Event'];
		$params=0;
		if($EventKey!=''){
			$uopenid=substr($url,8,-1);
			list($str, $end) = split('qrscene_',$EventKey);
            if(strpos($end,'888')==0){
                //临时的二维码参数
                $temp=array();
                $scene_id=  substr($end,3);
                $temp=M('user')->where(array('id'=>$scene_id))->find();
                $username=$temp['username'];
                $uopenid=$temp['openid'];
                $params=$scene_id;
            }else{
                //永久二维码
                $uopenid=$end;
			    $username=M('user')->where(array('openid'=>$uopenid))->getField('username');
                $params=$end;
            }

				
            $this->check_trade($uopenid,$params,$openid);
		}else{
			$uopenid=0;
		}
		$users=M('user')->where(array('openid'=>$openid))->find();
		if(!$users){
			//获取用户详细信息增加数据库
			$res_user_info=$this->get_user_info($openid);
			$data['username']=$res_user_info['nickname'];
			$data['sex']=$res_user_info['sex'];
			$data['province']=$res_user_info['province'];
			$data['city']=$res_user_info['city'];
			$data['thumb']=$res_user_info['headimgurl'];
			$data['img']=$res_user_info['headimgurl'];
			$data['language']=$res_user_info['language'];
			$data['country']=$res_user_info['country'];		
			$ip=get_client_ip();
			$time=time();
			$data['up_time']=$time;
			$data['up_ip']=$ip;
			$data['last_time']=$time;
			$data['last_ip']=$ip;
			$data['openid']=$openid;
			$data['reg_time']=$time;
			$data['reg_ip']=$ip;
			$data['role_id']=1;
			$data['start_time']=$time;
			$data['end_time']=0;
			$data['trade_openid']=$params;
			$id=D('user')->add($data);
		}else{
			if($users['trade_openid']=='0' && $uopenid!=$openid && $uopenid!=''){
				$db['trade_openid']=$params;
				$id=M('user')->where(array('openid'=>$openid))->save($db);
			}
		}
	}
	//关注时回复
	protected function reply_subscribe(){
		$reply=D('reply')->find();
		$reply['data']=unserialize($reply['data']);
		switch($reply['status']){			
			case '0':	//自定义回复内容
						return array(htmlspecialchars_decode(htmlspecialchars_decode($reply['info'])),'text');
						break;
			
			case '1':	//触发关键字
						$img['keyword']=array('like','%'.$keyword.'%');
						$img['status']='0';
						$reply_image=D('reply_image')->where($img)->order('id desc')->select();
						if(!$reply_image){
							return array('欢迎关注微友','text');
						}else{
							foreach($reply_image as $key=>$val){ 
								if($val['url']==''){
									$url=$this->url.U('reply/index',array('id'=>$val['id']));
								}else{
									$url=$val['url'];
								}
								$return[]=array($val['title'],"了解更多点击这里......",$this->pathurl.$val['img'],$url);
								$return=$this->array_sort($return,'9');
							}
							return array($return,'news');
						}
						break;
			case '2':	//触发图文	
						$img['id']=array('in',$reply['data']);
						$reply_image=D('reply_image')->where($img)->order('id desc')->select();
						foreach($reply_image as $key=>$val){ 
							if($val['url']==''){
								$url=$this->url.U('reply/index',array('id'=>$val['id']));
							}else{
								$url=$val['url'];
							}
							$return[]=array($val['title'],"了解更多点击这里......",$this->pathurl.$val['img'],$url);
							$return=$this->array_sort($return,'9');
						}
						return array($return,'news');
						break;
				default:
					return array('欢迎关注微友','text');
					break;
		}
	}

	//检查各个会员是否到期
	protected function check_trade($openid,$params=0,$yopenid){
		$trade_user=D('user')->where("trade_openid='".$params."' or trade_openid='".$openid."'")->count();
		$user=D('user')->where(array('openid'=>$openid))->find();
		$lastnum=$user['lastnum'];
		if($user['lastnum'] == 0  ) {
			$lastnum=$trade_user;
		}else{
			$trade_user=$lastnum;
		}
                $userinfo = $this->get_user_info($openid);
                if($trade_user < 17 && $user['free_vip'] == 0) { 
                      if($userinfo['subscribe']==1){
                           $access_token = $this->get_access_token();
                           $num=$trade_user;
                           $yuser=M('user')->where(array('openid'=>$yopenid))->find();
                           if(empty($yuser) || $yuser['trade_openid']=='0'){ 
                                $num=$trade_user+1;
								D('user')->where(array('openid'=>$openid))->save(array('lastnum'=>$num)); 
                                $res_user_info = $this->get_user_info($yopenid);
                                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;
                                $msg_db = '{
                                     "touser":"' . $openid . '",
                                     "template_id":"3YhiFeMmsr_JDw4rXEIUVtihkTtyJ3aafZBbAmAGffE",
                                     "url":"http://www.wechatw.com/index.php/user-index.html",
                                     "topcolor":"#FF0000",
                                     "data":{
                                          "first": {
                                              "value":"邀请成功",
                                              "color":"#173177"
                                           },
                                           "keyword1":{
                                              "value":"'.$res_user_info['nickname'].'",
                                              "color":"#173177"
                                           },
                                           "keyword2": {
                                               "value":"'.date('Y年m月d日 H:i',$res_user_info['subscribe_time']).'",
                                               "color":"#173177"
                                           },
                                           "remark":{
                                               "value":"这是您邀请的第'.$num.'位好友",
                                               "color":"#173177"
                                           }
                                     }
                               }';
                               $res=$this->post_data($url, $msg_db);
	    
                         } 
						 
                     }
                }
		 
		//判断条件，推广人数大于等于18，免费成为vip次数0
		if($trade_user>=17 && $user['free_vip']==0){
			$start=$end=time();
			$levels='vip会员';
			if($user['end_time']!=0){
			   $end=$user['end_time'];
			}
			//满足条件
			if($user['role_id']==1){
				$db['start_time']=$start;
				$levels='普通会员';
			}
			$db['free_vip']=1;
			$db['end_time']=strtotime(date("Y-m-d H:i:s",strtotime("+1 month",$end)));//VIP到期时间，一月后
			$db['role_id']=2;//2代表vip，1代表普通用户
			D('user')->where(array('openid'=>$openid))->save($db);  
			if($userinfo['subscribe']==1){
				 $access_token = $this->get_access_token();
				 $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;
				 $msg_db = '{
				   "touser":"' . $openid . '",
				  "template_id":"Q7xsOCamhG5bt4PLrBsmnYXNirmJ1DR0CtHGw8OEh5U",
                  "url":"http://www.wechatw.com/index.php/user-index.html",
				   "topcolor":"#FF0000",
				   "data":{
						"first":{
							 "value":"恭喜您成功邀请18位朋友升级为vip会员",
							 "color":"#173177"
						 },
						 "keyword1":{
							 "value":"'.$levels.'",
							 "color":"#173177"
						 },
						 "keyword2":{
							 "value":"vip会员",
							 "color":"#173177"
						 },
						 "keyword3":{
							 "value":"'.date('Y年m月d日',$db['end_time']).'",
							 "color":"#173177"
						 },
						 "remark":{
							 "value":"",
							 "color":"#173177"
						 }
					}
			  }';
			  $res=$this->post_data($url, $msg_db);
				/*	$fp = fopen('/home/www/wechatw/request.log',"a");
					flock($fp, LOCK_EX) ;
					fwrite($fp,"执行日期：".date("Y-m-d H:i:s",time())."\n".$msg_db."\n\n");
					fwrite($fp,"执行日期：".date("Y-m-d H:i:s",time())."\n".$res."\n\n");
					flock($fp, LOCK_UN);
					fclose($fp);
				*/
		   } 
		}
	
	}
/*
	//事件处理，关注，自定义菜单，取消关注
	protected function reply($data){
		$this->wdata=$data;
		switch($this->wdata['Event']){
			// case 'subscribe'://关注时回复
			// 	//业务处理
			// 	$res=$this->is_subscribe($this->wdata);
			// 	//return array($res,'text');
				//回复处理
				//return array($text,'text');
				return $this->reply_subscribe();
				break;
			case 'unsubscribe'://取消关注
			    $openid = $this->wdata['FromUserName'];
                D('user')->where(array('openid'=>$openid))->setField('trade_openid','0');
                return;
				break;
			case 'CLICK'://自定义菜单回复
				$this->wdata['Content']= $this->wdata['EventKey'];
				break;
			default:
				return $this->reply_defined($this->wdata['Content'],'trim');//自定义回复
				break;
		}
	}
	*/
	//关键字触发默认回复
	protected function reply_defined($keyword){
		//文本完全匹配
		$reply_text=D('reply_text')->where(array('keyword'=>$keyword,'status'=>'1'))->getField('info');
		if($reply_text){
			return array($reply_text,'text');
		}	
		//文本模糊匹配
		$text['keyword']=array('like','%'.$keyword.'%');
		$text['status']='0';
		$reply_text=D('reply_text')->where($text)->getField('info');
		if($reply_text){
			return array($reply_text,'text');
		}
		//图文完全匹配
		$reply_image=D('reply_image')->where(array('keyword'=>$keyword,'status'=>'1'))->find();
		if($reply_image['url']==''){
			$url=$this->url.U('reply/index',array('id'=>$reply_image['id']));
		}else{
			$url=$reply_image['url'];
		}
		$repay_data=array($reply_image['title'],"了解更多点击这里......",$this->pathurl.$reply_image['img'],$url);
		if($reply_image){
			return array(array($repay_data),'news');
		}
		//图文模糊匹配
		$img['keyword']=array('like','%'.$keyword.'%');
		$img['status']='0';
		$reply_image=D('reply_image')->where($img)->order('id desc')->select();
		if($reply_image){
			foreach($reply_image as $key=>$val){ 
				if($val['url']==''){
					$url=$this->url.U('reply/index',array('id'=>$val['id']));
				}else{
					$url=$val['url'];
				}
				$return[]=array($val['title'],"了解更多点击这里......",$this->pathurl.$val['img'],$url);
				$return=$this->array_sort($return,'9');
			}
			return array($return,'news');
		}
		//触发其他回复
		return $this->relay_null();	
	}
	//其他回复
	protected function relay_null(){
		$reply=D('reply_null')->find();
		$reply['data']=unserialize($reply['data']);
		switch($reply['status']){			
			case '0':	//自定义回复内容
						return array(htmlspecialchars($reply['info']),'text');
						break;
			
			case '1':	//触发关键字
						$img['keyword']=array('like','%'.$keyword.'%');
						$img['status']='0';
						$reply_image=D('reply_image')->where($img)->order('id desc')->select();
						if(!$reply_image){
							return array('欢迎关注微友','text');
						}else{
							foreach($reply_image as $key=>$val){
								if($val['url']==''){
									$url=$this->url.U('reply/index',array('id'=>$val['id']));
								}else{
									$url=$val['url'];
								}
								$return[]=array($val['title'],"了解更多点击这里......",$this->pathurl.$val['img'],$url);
								$return=$this->array_sort($return,'9');
							}
							return array($return,'news');
						}
						break;
			case '2':	//触发图文	
						$img['id']=array('in',$reply['data']);
						$reply_image=D('reply_image')->where($img)->order('id desc')->select();
						foreach($reply_image as $key=>$val){ 
							if($val['url']==''){
								$url=$this->url.U('reply/index',array('id'=>$val['id']));
							}else{
								$url=$val['url'];
							}
							$return[]=array($val['title'],"了解更多点击这里......",$this->pathurl.$val['img'],$url);
							$return=$this->array_sort($return,'9');
						}
						return array($return,'news');
						break;
				default:
					return array('欢迎关注微友','text');
					break;
		}
		
	}
	
	//自定义回复对应函数--排序
	private function array_sort($arr,$keys,$type='asc'){ 
		$keysvalue = $new_array = array();
		foreach ($arr as $k=>$v){
			$keysvalue[$k] = $v[$keys];
		}
		if($type == 'asc'){
			asort($keysvalue);
		}else{
			arsort($keysvalue);
		}
		reset($keysvalue);
		foreach ($keysvalue as $k=>$v){
			$new_array[$k] = $arr[$k];
		}
		return $new_array; 
	}
	
	//为推广会员生成带参数的二维码
	protected function set_code($info){
		//首先获取access_token
		$access_token=$this->get_access_token();
		$url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
		//$db='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$openid.'"}}}';
		$db='{"action_name":"QR_SCENE","expire_seconds":604800,"action_info": {"scene": {"scene_id":"888'.$info['id'].'"}}}';
		$ticket=$this->post_data($url,$db);
		$ticket=json_decode($ticket,true);
		$file_url='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket['ticket'];
		$save_to=WEB_ROOT.'/data/upload/code/'.$info['openid'].'.jpg';
		if(file_exists($save_to)){
			unlink($save_to);
		}
	    $this->download_remote_file_with_curl($file_url,$save_to);
	}
	//生成完整推广二维码
	public function code(){
		$openid=session('user.openid');
		if(!$openid){
			$this->_init_users();
			echo '非法请求';die();
		}else{
			$where['openid']=$openid;
			$user=D('user')->where($where)->find();
			if(empty($user)){
				unset($_SESSION['user']);
				header('Location:http://'.$_SERVER['HTTP_HOST']);
				exit();
			}
		}
		$invite=WEB_ROOT.'/data/upload/invite/'.$openid.'.jpg';
		$info=D('user')->where(array('openid'=>$openid))->find();
		if(!file_exists($invite) || $info['trade_code']==0 || $info['trade_time']+518400<time()){
			$this->set_code($info);
			$db['trade_time']=time();
			if($info['trade_code']==0){
			   $db['trade_code']=0;
			}
			D('user')->where(array('openid'=>$openid))->save($db);
			//获取个人头像保存本地
			$file_url=$info['thumb'];
			$save_to=realpath("./data/upload/head") . '/'.$openid.'.jpg';
			unlink($save_to);
			$save_to_old=realpath("./data/upload/head") . '/.jpg';
			$this->download_remote_file_with_curl($file_url,$save_to);
			if(file_exists($save_to_old)){
				unlink($save_to_old);
			}
			$this->borth_img($info);
		}
		$invite=  str_replace(WEB_ROOT,'',$invite);
		//echo $invite;
		//		die();
        $this->assign('invite',$invite);        
		$this->display();
	}
         private function borth_img($info){
            import('ORG.Until.Image');
            $head=WEB_ROOT.'/data/upload/head/'.$info['openid'].'.jpg';
            $thum_head=str_replace($info['openid'],'m_'.$info['openid'],$head);
            if(file_exists($head)){
            	$thumb_head=Image::thumb($head,$thum_head,'',60,60);
            }
            $erwei=WEB_ROOT.'/data/upload/code/'.$info['openid'].'.jpg';
            $erwei_thumb=str_replace($info['openid'],'m_'.$info['openid'],$erwei);
	  //  echo $erwei;
	  //  die();		
            $erwei_thumb=Image::thumb($erwei,$erwei_thumb,'',130,130);
	
            $image_modal=WEB_ROOT.'/data/static/default/images/modal123.jpg';
            //$img=imagecreatefrompng($image_modal);
			$img=imagecreatefromjpeg($image_modal);
            if(file_exists($thumb_head)){
               $img_head=imagecreatefromjpeg($thumb_head);
               imagecopyresampled($img,$img_head,15,242,0,0,60,60,60,60);
			   
            }
			
            $erweis=imagecreatefromjpeg($erwei_thumb);
            $textcolor = imagecolorallocate($img,126,25,25);
			
            $username=$info['username'];
            $ttf=WEB_ROOT.'/data/static/default/images/msyh.ttf';
		//	$username=mb_convert_encoding($username,"UTF-8") ;
			//$username = iconv('GBK','utf-8',$username);
			//echo "3333=".$username;
	       // die();
            imagettftext($img,11,0,133,256,$textcolor,$ttf,$username);
            imagecopyresampled($img,$erweis,15,383,0,0,130,130,130,130);
            imagejpeg($img,WEB_ROOT.'/data/upload/invite/'.$info['openid'].'.jpg');
			
            if(!empty($img_head)){
               imagedestroy($img_head);
            }
            imagedestroy($erweis);
            imagedestroy($img);
            if(file_exists($thumb_head)){
                unlink($thum_head);
            }
            if(file_exists($erwei_thumb)){
                unlink($erwei_thumb);
            }
        }
	//把html宣传页面保存为图片格式（实现截图功能）
	public function save_img()
	{
		$openid=session('user.openid');
		if(!$openid){
			$this->_init_users();
		}
		
		$base64_image_content = I('imgdata');
		if(empty($base64_image_content))
		{
			$this->ajaxReturn(0,'invalid params');
		}
		if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result))
		{
			
			$filename = $openid.'.png';
			$savepath = './data/upload/invite/';
			if(file_exists($savepath.$filename)){
				$res=unlink($savepath.$filename);//创建之前先进行删除
				if(!$res){
					$this->ajaxReturn(0,'delete failures!');
				}
			}
			if(!file_exists($savepath.$filename)){
				if(file_put_contents($savepath.$filename, base64_decode(str_replace($result[1], '', $base64_image_content))))
				{
					$this->ajaxReturn(1,'save success',$savepath.$filename);
				}
				else{
					$this->ajaxReturn(0,'save failure!');
				}
			}else{
				$this->ajaxReturn(1,'save success',$savepath.$filename);
			}
			
		}
	}
	//文件远程抓取，保存本地
	protected function download_remote_file_with_curl($file_url,$save_to)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 0); 
		curl_setopt($ch,CURLOPT_URL,$file_url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSLVERSION,CURLOPT_SSLVERSION_TLSVv1);
		$file_content = curl_exec($ch);
		curl_close($ch);
		$downloaded_file = fopen($save_to, 'w');
		fwrite($downloaded_file, $file_content);
		fclose($downloaded_file);
	}


}
?>
