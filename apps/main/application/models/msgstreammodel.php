<?php

/**
 * 
 * 信息流模型
 * @author zhoutianliang
 * 
 *
 */
class Msgstreammodel extends DK_Model {
	private $getTypeCountKey = 'info:%d:%s';
	private $getTopicKey     = 'topic:%d';//信息实体
	private $getTypeTopicKey = 'info:%d:%s:%d';//ZADD 信息ID集合
	private $Type            = '';//信息类型
	private $TypeMap         = '';//type对应的类型 表
	private $PageSize        = 10;//一页显示多少个
	private $Page            = 1;//当前页
	private $Count           = 0;//总数
	private $Uid             = 0;//用户id
	private $PageCount       = 0;//页数量
	private $startOffset     = 0;//信息起始位置
	private $endOffset       = 0;//信息结束位置
	private $_lastId         = 0;//上次最后的ID
	private $_score          = 0;//上次的时间  
	public function __construct() { 
		parent::__construct();
        $this->init_redis();
	}
	/**
	 * 获取对应的信息 
	 * @param $uid 用户UID
	 * @param $type 类型
	 * @param $page 当前面
	 */
	public function getMessage($uid,$type,$page,$lastid='',$score='') {
		$this->_Initialize($uid, $type, $page,$lastid,$score);//初始化
		return $this->calcOperate($score,$lastid);
	}
	
	/**
	 * 初始化所需参数 
	 * @param $uid 用户UID
	 * @param $type 信息类型
	 * @param $page 当前页数
	 */
	private function _Initialize ($uid,$type,$page=1,$lastid=0,$score=0) {
		$this->Uid     = $uid;
		$this->Type    = $type;
		$this->Page    = $page;
		$this->_lastId = $lastid;
		$this->_score  = $score;
		$this->TypeMap();//初始化typemap
	}
	/**
	 * 获取相对应年月总数
	 * 
	 */
	private function getTypeCount() {
		$allyearmonth = array();
		if (empty($allyearmonth)) {
			$allyearmonth = $this->redis->hgetall(sprintf($this->getTypeCountKey,$this->Uid,$this->Type)) ? : array();
			krsort($allyearmonth, SORT_NUMERIC); //按键名年月排序 
		}
		return $allyearmonth;
	}
	/**
	 * 对应的类型 进行转换
	 */
	private function TypeMap() {
        $this->TypeMap = substr($this->Type,0,4); //取Type的前面四位字符
	}
	/**
	 * 所有基本计算操作
	 * 算出起始位置 结束位置
	 * 起如位置等于 当前页数*显示数量-显示数量
	 * 结束位置等于 当前页数*显示数量
	 * 过滤传过来的页数大于总数页的
	 * 过滤起始位置大于总数的
	 */
	private function calcOperate($score=0,$lastid=0) {
		$yearTotal = $this->getTypeCount();
		if(empty($yearTotal)) return $this->returnData(array('data'=>null,'msg'=>'没有数据','status'=>1),1);
		$year      = array_keys($yearTotal);//取得所有年月;
		//logging($yearTotal);
		if($score==0) {
			$score = '+inf';
			$transitionTime = $year[0];
		}else {
			$transitionTime = date("Ym",(int)$score); //如果不是为零  就转成  201208这个格式
		}
		//先取出 pagesize 条  从大到小
		//logging(sprintf($this->getTagidTopicKey,$this->Uid,$this->tagId,$transitionTime));
		$result = $this->redis->zRevRangeByScore(sprintf($this->getTypeTopicKey,$this->Uid,$this->TypeMap,$transitionTime),
				$score,'-inf',array('limit'=>array(/* ($this->Page * $this->PageSize) - $this->PageSize */0,$this->PageSize)));//取出全部的
		//取出第一条
		if($lastid) { //查找这个lastid是否存在 当前读出来的topicid中
			if(($key = array_search($lastid, $result)) !== false) {
				$result = array_slice($result, $key+1);
			}
		}
		//logging($result);
		$one    = $this->redis->zRangeByScore(sprintf($this->getTypeTopicKey,$this->Uid,$this->TypeMap,$transitionTime),
				'-inf','+inf',array('limit'=>array(0,1)));
		//logging($one);
		//if(count($result) > $this->PageSize) {
		//	$fetchResult = array_slice($result,0,$this->PageSize,true);//取出前面十个出来
		//}
		/*
		 * 判断取出的最后一个是不是等于全部结果的最后一个 如果不等于说明还有下一页
		* 如果相等有几种情况  正好取到这个月的月尾
		*/
		$isend = null;
		if(isset($one[0]) && $one[0]==$result[(count($result)-1)]) {
			//取得这个值在数组的第几位。
			if(($key=array_search($transitionTime, $year))!==false) {
				//取得这个数组之后的数据
				$newYear = array_slice($year, $key+1);
				//如果之后没有数据说明到头了。
				if(empty($newYear)) {
					$isend = true; //已经结束
				}else {
					$isend = false;//没有结束
					//把下一个月的score取出来;
					$endScoreAndId = $this->redis->zRevRangeByScore(sprintf($this->getTypeTopicKey,$this->Uid,$this->TypeMap,$newYear[0]),
							'+inf','-inf',array('withscores'=>true,'limit'=>array(0,1)));
					$getKeyId      = array_keys($endScoreAndId);
					$getScore      = array_values($endScoreAndId);
					$endScore      = $getScore[0];
					//$endId         = $getKeyId[0];
				}
			}else {
				$isend = true;//已经结束
			}
		}else {
			$isend = false;
		}
		$data = $this->getTopicFeedInfo($result); //根据ID取出所有数据
		$data = array_filter($data);//过滤掉空的内容
		$lastarray = end($data);
		
		return $this->returnData(array('data' => array_values($data),
				'isend'=>$isend,
				'status' => 1,
				'param'=>array('score'=>isset($endScore) ? $endScore : $lastarray['dateline'],'lastid'=>isset($endId) ? $endId : $lastarray['tid'])
		),1);
	}
	/**
	 *获取信息实体信息
	 *转换要转换的数据
	 *@param $topicId 信息ID
	 */
	private function getTopicFeedInfo($topicId) {
		if(empty($topicId)) return $this->returnData(array('data'=>null,'msg'=>'没有数据','status'=>0),1);

		if($this->_lastId) { //查找这个lastid是否存在 当前读出来的topicid中
			if(($key = array_search($this->_lastId, $topicId)) !== false) {
				$topicId = array_slice($topicId, $key+1);
			}
		}
		$data = $this->getTopic($topicId);
        $data = array_filter($data);//过滤为空的数组
		if($this->_score) { //判断是不是有score传过来 如果有就过滤
			$score = $this->_score; 
			$data = array_filter($data,function($var) use ($score) {
				return $var['dateline'] <= $score;//把$score大于的取出
			});
		}
		$arrayLength = count($data);
		for($i=0;$i<$arrayLength;$i++) {
			
			if(empty($data[$i])) {
				unset($data[$i]);
			}
			if(isset($data[$i]['dateline'])) {
				$ctime = friendlyDate($data[$i]['dateline']);
				$data[$i]['friendly_time'] = $ctime;
			}
			if(isset($data[$i]['uid'])) { //获取信息流 的头像地址
				if($data[$i]['uid']>0){
					$get_headimage = get_avatar($data[$i]['uid']);
					$data[$i]['headpic'] = $get_headimage;
				}
			}
			if(isset($data[$i]['type']) && $data[$i]['type']=='uinfo'){
				//过滤不要的时间线要的信息不要的信息
				unset($data[$i]);
			}
			if(isset($data[$i]['type'])) {
				if(in_array($data[$i]['type'],$this->infoType())) {
					$data[$i] = $this->filterType($data[$i]['type'], $data[$i]);
				}
			}

		}

        return $data;
	}
	/**
	 * 
	 * 处理返回结果
	 * @param $data 要返回的数据
	 */
	private function returnResult($data) {
		$lastarray = end($data);
		if(($this->Count - ($this->Page * $this->PageSize)) <= 0 ) {
			$isend = true;
		} else {
			$isend = false;  //判断是否还有下一页;
		}
		return $this->returnData(array('data' => array_values($data),
				'isend'=>$isend,
				'status' => 1,
				'param'=>array('score'=>isset($lastarray['dateline']) ? $lastarray['dateline'] : 0 ,'lastid'=>isset($lastarray['tid']) ? $lastarray['tid'] : 0 )
		),1);
	}
	/**
	 * 取得时间段是不是有新的数据产生，如果有返回总数
	 * @param $uid 用户ID
	 * @param $type 信息类型
	 * @param $ctime 信息时间段
	 */
	public function getTimeTopicCount($uid,$type,$ctime=0) {
		$this->_Initialize($uid, $type);
		if(!$ctime) $ctime = time();
		$getKey  = sprintf($this->getTypeTopicKey,$this->Uid,$this->TypeMap,date("Ym"));
		if(!$this->checkRedisKeyExists($getKey)) {//检 测这个KEY存在不
			return $this->returnData(array('data'=>null,'msg'=>'参数传递错误','status'=>0),1);
		}
		$infoId = $this->redis->zrangebyscore($getKey,$ctime,'+inf');
		//把这次读取的时间返回去ctime
		return $this->returnData(array('data'=>count($infoId),'status'=>1,'param'=>array('ctime'=>time())), 1);
	}
	/**
	 *取得时间段新产生的数据
	 * @param $uid 用户ID
	 * @param $type 信息类型
	 * @param $ctime 信息时间段
	 */
	public function getTimeTopicInfo($uid,$type,$ltime=0,$lastid=0) {
		$this->_Initialize($uid, $type);
		if(!$ltime) $ltime = time();
		$getKey  = sprintf($this->getTypeTopicKey,$this->Uid,$this->TypeMap,date("Ym"));
		if(!$this->checkRedisKeyExists($getKey)) {//检 测这个KEY存在不
			return $this->returnData(array('data'=>null,'msg'=>'参数传递错误','status'=>0),1);
		}
		$infoId = $this->redis->zrangebyscore($getKey,$ltime,'+inf');
		if($lastid && $infoId) { //过滤这个ID之前的
		    if(($key = array_search($lastid, $infoId)) !== false) {
		    	$infoId = array_slice($infoId, $key + 1);
		    }
		}
		if($infoId) {
			$result = $this->getTopicFeedInfo($infoId);
			//把这次读取的时间返回去ltime
			return $this->returnData(array('data'=>$result,'status'=>1,'param'=>array('ltime'=>SYS_TIME)), 1);
		}else {
			return $this->returnData(array('data'=>0,'msg'=>'没有数据','status'=>1,'param'=>array('ltime'=>SYS_TIME)), 1);
		}
	}
	/**
	 * 检测redis键存在不如果存在返回true 否 false
	 * @param $key 键值 
	 * 
	 */
	private function checkRedisKeyExists($key) {
		return $this->redis->exists($key) ? true : false;
	}
	/**
	 *转换信息 
	 * @param $type 要过滤的类型
	 * @param $topic 要过滤的信息
	 */
	private function filterType($type,$topic) {
/* 		switch ($type) {
			case 'album':
				return $this->switchAlbum($topic);
			break;
			case 'event':
			    return $this->switchEvent($topic);
			break;
			case 'ask':
				return $this->switchAsk($topic);
			break;
			case 'forward':
			    return $this->switchForward($topic);
			break;
			case 'social':
				return $this->switchSocial($topic);
			break;
			case 'change':
			    return $this->switchChange($topic);
		    break;
		    case 'answer':
		        return $this->switchAnswer($topic);
		    break;
		    case 'join':
		        return $this->switchJoin($topic);
		    break;
		    default:
		    	return $topic;
		} *///将switch 用选择函数代替

		$costom = 'switch'.ucfirst($type);
		if(method_exists($this,$costom)) {
			return $this->$costom($topic);
		}
		return $topic;
	}
	/**
	 * 
	 * 对 关注 加好友 关注网页 处理
	 * @param $topic 处理的数据
	 */
	private function switchSocial($topic) {
		if(isset($topic['friends'])) {
			$friends = json_decode($topic['friends'],true);
			$topic['friends_num'] = count($friends);
			if(!empty($friends)) {
				$friends = $this->arraySort($friends, 'time');
				$friends = array_slice($friends,0,6,true);
				$tmparr = array();
				foreach ($friends as $key=>$one) {
					$one['headpic'] = get_avatar($one['id']);
					array_push($tmparr, $one);
				}
	
				$topic['friends'] = $tmparr;
			}else {
				$topic['friends'] = array();
			}
		}
		if(isset($topic['follows'])) {
			$follows = json_decode($topic['follows'],true);
			$topic['follows_num'] = count($follows);
			if(!empty($follows)) {
				$follows = $this->arraySort($follows, 'time');
	
				$follows = array_slice($follows,0,6,true);
				$tmparr = array();
				foreach ($follows as $key=>$one) {
					$one['headpic'] = get_avatar($one['id']);
					array_push($tmparr, $one);
				}
				$topic['follows'] = $tmparr;
			}else {
				$topic['follows'] = array();
			}
		}
		if(isset($topic['webs'])) {
/* 			$followwebs = json_decode($topic['webs'],true);
			$topic['webs_num'] = count($followwebs);
			if($followwebs) {
				$followwebs = $this->arraySort($followwebs, 'time');
				$followwebs = array_slice($followwebs,0,6,true);
				$tmparr = array();
				foreach ($followwebs as $key=>$one) {
					$one['headpic'] = get_webavatar($one['owner'],'m',$key);
					$one['web_url']  =  mk_url("webmain/index/main",array('web_id'=>$one['id']));
					unset($one['owner']);
					array_push($tmparr, $one);
				}
				$topic['webs'] = $tmparr;
			}else {
				$topic['webs'] = array();
			} */
			$topic['webs'] = array();
		}
	    return array();//过滤掉 不显示 
		//return $topic;
	}
	/**
	 * 对问答处理
	 * @param $topic 处理的数据
	 */
	private function switchAnswer($topic) {
		if($topic['questions']) {
			$question = json_decode($topic['questions'],true);
			$topic['question_num'] = count($question);
			$question = $this->arraySort($question, 'time');
	
			$tmparr = array();
			$question = array_slice($question,0,3,true);
			foreach($question as $key=>$one) {
	
				$title              = strip_tags($one['title']);
				$one['title_full']  = $title;
				$one['title']       = mb_strlen($title) > 28 ? mb_substr($title,0,28) . '...': $title;
				$one['answers']     = array_slice(array_map(
					function($val){return mb_strlen(strip_tags($val)) > 28 ? mb_substr(strip_tags($val), 0,28) : strip_tags($val);},
					json_decode($one['answers'],true)
				),0,3,true);
	
				array_push($tmparr, $one);
			}
			$topic['questions'] = $tmparr;
		}
		$topic['start_time'] = date('m月d日',$topic['dateline']);
		$topic['end_time']   = date('m月d日',$topic['ctime']);
		return $topic;
	}
	/**
	 * 	对活动内容处理
	 *  @param $topic 处理的数据
	 */
	private function switchJoin($topic) {
		if($topic['events']) {
			$events = json_decode($topic['events'],true);
			$topic['events_num'] = count($events);
			$events = $this->arraySort($events, 'time');
	
			$tmparr = array();
			$events = array_slice($events,0,3,true);
			foreach($events as $key=>$one) {
				$one['join_time'] = date('Y年m月d日',$one['time']);
				array_push($tmparr, $one);
			}
			$topic['events'] = $tmparr;
		}
		return $topic;
	}
	//对头像更改内容处理
	private function switchChange($topic) {
		if($topic['filename'] && $topic['groupname'] && $topic['imgtype']) {
			$topic['imgurl'] = $topic['groupname'] .'/'.$topic['filename'].'.'.$topic['imgtype'];
			unset($topic['filename']);
			unset($topic['groupname']);
			unset($topic['imgtype']);
		}
		return $topic;
	}
	//相册
	private function switchAlbum($topic) {
		if(isset($topic['picurl'])) {
			$topic['picurl'] = json_decode($topic['picurl'],true);
		}
		return $topic;
	}
	/**
	 *
	 * @param array $topic
	 * @return array
	 */
	private function  switchPhoto($topic) {
		$topic['picurl'] = json_decode($topic['picurl'], true);
		return $topic;
	}
	//活动
	private function switchEvent($topic) {
		if(isset($topic['starttime']) && !empty($topic['starttime'])) {
		    $topic['starttime'] = date('Y-n-j H:i',$topic['starttime']);
		}
		return $topic;
	}
	//转发
	private function switchForward ($topic) {
		$get_fid = $topic['fid'];
		$get_forward = $this->redis->hgetall(sprintf($this->getTopicKey,$get_fid));
		if(!empty($get_forward)) {
			$topic['forward'] = $get_forward;
		}
		if(isset($topic['forward']['type']) && $topic['forward']['type']=='album') {
			$topic['forward']['picurl'] = json_decode($topic['forward']['picurl'],true);
		}
		if(isset($topic['forward']['type']) && $topic['forward']['type']=='ask') {
			if(isset($topic['forward']['answerlist'])&& !empty($topic['forward']['answerlist'])) {
				$topic['forward']['answerlist'] = json_decode($topic['forward']['answerlist'],true);
			}
	
		}
		if(isset($topic['forward']['type']) && $topic['forward']['type']=='photo') {
			$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
		}
		return $topic;
	}
	//问答
	private function switchAsk ($topic) {
		$topic['ask'] = service('Ask')->timelineAskData($topic['fid'],$this->Uid);
		return $topic;
	}
	//信息类型
	private function infoType() {
		return array('join','ask','event','change','forward','answer','social','album','photo'
				     );
	}

	/**
	 * 获取信息实体
	 * @param $key array string 如果key是数组就进行事务操作，如果不是就普通操作
	 */
	private function getTopic($key) {
		if(is_array($key)) {
			$multi = $this->redis->PIPELINE(); //返回一个redis事务对象
		    $len  = count($key);
		    for($i=0;$i<$len;$i++) {
		    	$multi->hgetall(sprintf($this->getTopicKey,$key[$i]));
		    }
		    $data = array();
		    $data = $multi->exec();
		    return $data; 
		}else {
			return $this->redis->hgetall(sprintf($this->getTopicKey,$key));
		}
	}
	/**
	 * 针对二维数组按键排序 默认降序
	 */
	private function arraySort($arr,$key,$order = SORT_DESC ) {
		if(empty($arr)) return false;
		$tmp = array();
		foreach($arr as $k=>$value) {
			//if(isset($value[$key])) {
			$arr[$k]['id'] = $k;
			$tmp[$k] = (int) isset($value[$key]) ? $value[$key] : 0;
			//}
		}
		array_multisort($tmp,$order,$arr);
		return $arr;
	}
	private function returnData($data,$status) {
		return array($data,'status'=>$status);
	}
}