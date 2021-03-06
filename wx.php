﻿<?php
require_once(__DIR__ . "/BaiduMapClient.php");
require_once(__DIR__ . "/BaiduTranslateClient.php");
require_once(__DIR__ . "/FacePPClient.php");
/**
  * wechat php test
  */

//define your token
define("TOKEN", "hackfisher");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];
		
        //valid signature , option
        if($this->checkSignature()){
			echo $echoStr;
        } else {
			exit;
		}
    }

    public function responseMsg()
    {

		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
				$msgType = $postObj->MsgType;
				if ($msgType=="text") {
					$keyword = trim($postObj->Content);
					$time = time();
					$textTpl = "<xml>
								<ToUserName><![CDATA[%s]]></ToUserName>
								<FromUserName><![CDATA[%s]]></FromUserName>
								<CreateTime>%s</CreateTime>
								<MsgType><![CDATA[%s]]></MsgType>
								<Content><![CDATA[%s]]></Content>
								<FuncFlag>0</FuncFlag>
								</xml>";             
					if(!empty( $keyword ))
					{
						$api_key = "SEwcXuDQE7ZcGM0Fxz2B02zb";
						// initialize client object
						$api = new BaiduTranslateClient($api_key);
						$result = $api->translate($keyword, "auto", "auto");
						$contentStr = "Translation Failed!";
						if (!empty($result->trans_result)) {
							if (count($result->trans_result) > 0) {
								$contentStr = $result->trans_result[0]->dst;
							}
						}
						$msgType = "text";
						//$contentStr = "Hi, I'm Mars Robot! 有何贵干? 你可以发送位置，试试看。";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else{
						echo "Input something...";
					}
				} else if ($msgType=="location"){
					$location_x = $postObj->Location_X;
					$location_y = $postObj->Location_Y;
					$scale = $postObj->Scale;
					$label = $postObj->Label;
					$time = time();
					$textTpl = "<xml>
								<ToUserName><![CDATA[%s]]></ToUserName>
								<FromUserName><![CDATA[%s]]></FromUserName>
								<CreateTime>%s</CreateTime>
								<MsgType><![CDATA[%s]]></MsgType>
								<Content><![CDATA[%s]]></Content>
								<FuncFlag>0</FuncFlag>
								</xml>";
					$api_key = "0e4fde2b4acbc043abdb68df511359ae";
					// initialize client object
					$api = new BaiduMapClient($api_key);
					$result = $api->geocoder_location($location_x.",".$location_y);
					if (!empty($result)) {
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $label . $result);
						echo $resultStr;
					}
				} else if ($msgType=="image"){
					$url = $postObj->PicUrl;
					$time = time();
					$api_key = "e336859e1099669c6662a3ca76c590b8";
					$api_secret = "HcMo_lQFQ4RZ1qrijxjjnzoHeNjxMc18";
					// initialize client object
					$api = new FacePPClient($api_key, $api_secret);			
					$person_name = md5(time().rand());
					//$file_name = $this->getFileByWget($url, $person_name . ".jpg");
					$ret = $this->downloadImage($url, "/home/clasix/sites/weixin/images/" . $person_name);
					if (!$ret) {
						$textTpl = "<xml>
								<ToUserName><![CDATA[%s]]></ToUserName>
								<FromUserName><![CDATA[%s]]></FromUserName>
								<CreateTime>%s</CreateTime>
								<MsgType><![CDATA[%s]]></MsgType>
								<Content><![CDATA[%s]]></Content>
								<FuncFlag>0</FuncFlag>
								</xml>";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, "File Download Failed!");
						echo $resultStr;
					} else {
						//
						// do search by url_img
						$group = "sample_group";
						$face_ids = array();
						detect($api, $person_name, $face_ids);
						$result = recognize($api, $person_name, $group);
						if (!empty($result)) {
							$textTpl = "<xml>
									 <ToUserName><![CDATA[%s]]></ToUserName>
									 <FromUserName><![CDATA[%s]]></FromUserName>
									 <CreateTime>%s</CreateTime>
									 <MsgType><![CDATA[%s]]></MsgType>
									 <Content><![CDATA[]]></Content>
									 <ArticleCount>1</ArticleCount>
									 <Articles>
									 <item>
									 <Title><![CDATA[Similar Pic]]></Title>
									 <Description><![CDATA[nothing]]></Description>
									 <PicUrl><![CDATA[%s]]></PicUrl>
									 <Url><![CDATA[%s]]></Url>
									 </item>
									 </Articles>
									 <FuncFlag>0</FuncFlag>
									 </xml>";
							$msgType = "news";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, getWeiXinUrl($result), getWeiXinUrl($result));
							echo $resultStr;
						} else {
							$textTpl = "<xml>
									<ToUserName><![CDATA[%s]]></ToUserName>
									<FromUserName><![CDATA[%s]]></FromUserName>
									<CreateTime>%s</CreateTime>
									<MsgType><![CDATA[%s]]></MsgType>
									<Content><![CDATA[%s]]></Content>
									<FuncFlag>0</FuncFlag>
									</xml>";
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, "No Similar Pictures");
							echo $resultStr;
						}
						// do train job
						$api->group_add_person($person_name, $group);
						train($api, $group);
					}
				} else {
					echo "";
					exit;
				}
        }else {
        	echo "";
        	exit;
        }
    }
		

	private function debug($text) {
		$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
		$fp = fopen($DOCUMENT_ROOT."/debug.txt",'ab');
		$tab = "--------------";
		fwrite($fp, $text, strlen($text));
		fwrite($fp, $tab, strlen($tab));
		fclose($fp);
	}
	
	/**
	 * 用shell获取远程文件
	 *
	 * $file_url    string  文件地址
	 * $dir         string  文件存放地址 默认是/tmp/image/
	 */
	function getFileByWget($url, $person, $dir='/home/clasix/sites/weixin/images') {
		if(empty($url)) {
			return null;
		}
		$file_name = $dir.$person;
		$commod = "wget -q {$url} -O {$file_name}";
		exec($commod);
		return $file_name;
	}
	
	/**
     * 下载远程图片
     * @param string $url 图片的绝对url
     * @param string $filepath 文件的完整路径（包括目录，不包括后缀名,例如/www/images/test） ，此函数会自动根据图片url和http头信息确定图片的后缀名
     * @return mixed 下载成功返回一个描述图片信息的数组，下载失败则返回false
     */
    function downloadImage($url, $filepath) {
		$ch = curl_init($url);
        $fp = fopen($filepath.'.jpg', 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		return true;
    }
	
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	

		$this->debug($signature);
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

/* 
 *	create new person, detect faces from person's image_url
 */
function detect(&$api, $person_name, &$face_ids) 
{
	// obtain photo_url to train
	$url = getTrainingUrl($person_name);
	
	// detect faces in this photo
	$result = $api->face_detect($url);
	// skip errors
	if (empty($result->face))
		return false;
	// skip photo with multiple faces (we are not sure which face to train)
	if (count($result->face) > 1)
		return false;
	
	// obtain the face_id
	$face_id = $result->face[0]->face_id;
	$face_ids[] = $face_id;
	// create a new person for this face
	$api->person_create($person_name);
	// add face into new person
	$api->person_add_face($face_id, $person_name);
}

/*
 *	train recognization model for group
 */
function train(&$api, $group_name)
{
	// train model
	$session = $api->recognition_train($group_name);
	if (empty($session->session_id))
	{
		// something went wrong, skip
		return false;
	}
	$session_id = $session->session_id;
	// wait until training process done
	while ($session=$api->info_get_session($session_id)) 
	{
		sleep(1);

		if (!empty($session->status)) {
			if ($session->status != "INQUEUE")
				break;
		}
	}
	// done
	return true;
}

/*
 *	recognize a person in group
 */
function recognize(&$api, $person_name, $group_name)
{
	// obtain photo_url to recognize
	$url = getPhotoUrl($person_name);
	
	// recoginzation
	$result = $api->recognition_recognize($url, $group_name);
	
	// skip errors
	if (empty($result->face))
		return "";
	// skip photo with multiple faces
	if (count($result->face) > 1)
		return "";
	$face = $result->face[0];
	// skip if no person returned
	if (count($face->candidate) < 1)
		return "";
		
	// print result
	foreach ($face->candidate as $candidate) 
		return $candidate->person_name;
	
	return "";
}

/*
 *	generate a new group with group_name, add all people into group
 */
function create_group(&$api, $group_name, $person_names) 
{
	$api->group_create($group_name);
	// add new person into the group
	foreach ($person_names as $person_name)
		$api->group_add_person($person_name, $group_name);
}

/*
 *	return the train data(image_url) of $person_name
 */
function getTrainingUrl($person_name)
{
	// TODO: here is just the fake url
	// "http://face-plus-plus.com/static/img/demo/".$person_name.".jpg";
	return "http://nf.hackfisher.info/wximages/".$person_name.".jpg";
}

/*
 *	return the photo_url of $person_name to recognize for
 */
function getPhotoUrl($person_name)
{
	// TODO: here is just the fake url
	return "http://nf.hackfisher.info/wximages/".$person_name.".jpg";
}

function getWeiXinUrl($person_name)
{
	// TODO: here is just the fake url
	return "http://weixin.hackfisher.info/images/".$person_name.".jpg";
}
?>
