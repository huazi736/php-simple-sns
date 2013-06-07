<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 搜索 数据处理层
 * Enter description here ...
 * @author liuGC
 * @access public
 * @dateline 2012/03/24
 * @version 1.0
 * @description <author><access><dateline><version><description>
 *
 */

class SearchModel extends DK_Model
{
	private $service = null;
	
	private $keyword = null;
	
	private $current_user_id = null;
	
	private $subfix='...';
	
	private $charset = 'utf-8';

	private $records =array();
	
	private $parameters = array();
							
	private $configuration = array(
							'location_path' => array(//搜索引用路径
											  	  "main"     => "main/search/main",
												  'personal' => array(//个人应用路径	
																	  "people"=>"main/index/main",
																	  "web"=>"webmain/index/main",
																	  "status"=>"main/index/main",
																	  "photo"=>"album/index/photoInfo",
																	  "album"=>"album/index/photoLists",
																	  "video"=>"video/video/player_video",
																	  "blog"=>"blog/blog/main",
																	  "ask"=>"ask/ask/detail",
																	  "event"=>"event/event/detail"
																	  ),
												   'web'     => array(//网页应用路径
																	  "video"=>"wvideo/video/player_video",
													 				  "event"=>"wevent/event/detail",
																	  "photo"=>"walbum/photo/get",
																	  "album"=>"walbum/photo/index",
																	  "status"=>"webmain/index/main"
																	 )
												  ),										
							'fields'      => array(//统计字段type值
											      'people' => 1, 'website' => 2, 'status' => 3, 'photo' => 4, 'album' => 5,  'video' => 6,'blog' => 7,'answer' => 8,'event' => 9 
											      ),									 
							'time'        => array(//时间格式
											      'blog'=>'Y-m-d H:i','ask'=>'m-d H:i','photo'=>'m月d日 H:i','album'=>'Y-m-d H:i', "event"=>"m月d日 H:i"
										          ),								   
							'title'       => array(//标题截取长度
										          'photo'=>8, 'album'=>8,'event'=>14,'ask'=>30, 'blog'=>30, 'video'=>10
										          ),									    
							'body'        => array(//描述截取长度
										          'status'=>150,'blog'=>140, 'video'=>50, 'event'=>50
										          ),									    
							'other'       => array(//其他参数设置
											      'ask_option_length'=>12,
										          'statistics_max_count_value'=>500,
										          )     
						  );
	
	private $permiss = array(
							-1 => '仅自定义可见', 
							 1 => '公开', 
							 3 => '仅粉丝可见', 
							 4 => '仅好友可见', 
							 8 => '仅自己可见'
							 );	

	public function __construct()
	{
		$this->service = service("GlobalSearch");
	}
	
        /**
         *获取搜索左侧导航数据统计
         * 
         * @return array 
         */
	public function getLeftNavStatistics()
	{
		
		$count = array();
		
		$prefix = "";
		
		$subfix = "";
		
		$default = 0;
		
		if ($this->keyword == null)
		
			foreach ($this->configuration['fields'] as $key => $num)    $count[$key] = $default;
		else{
			$statistics = $this->service->getStatisticsByGroup($this->keyword);

                        if ($statistics === false)
                        {
                        	foreach ($this->configuration['fields'] as $key => $num)     $count[$key] = $default;                           
                        }else{
                            $left_records = get_object_vars($statistics->facet_counts->facet_fields->type);

                            foreach ($this->configuration['fields'] as $key => $num)
                            {
                                    $count[$key] = (isset($left_records[$num]) && ($left_records[$num] != 0)) ? 

                                                                    $prefix.$this->getCountFormat($left_records[$num], $this->configuration['other']['statistics_max_count_value']).$subfix :

                                                                    $default;
                            }
                        }
		}
		
		return $count;
	}
        /**
         *设置当前登录用户ID
         * 
         * @param type $user_id 
         */
	public function setCurrentUserID($user_id)
	{
		$this->current_user_id = $user_id;
	}
	
        /**
         *设置当前用户搜索的关键字
         * 
         * @param type $keyword 
         */
	public function setKeyword($keyword)
	{
		$this->keyword = $keyword;
	}
	
        /**
         *获取用户搜索的关键字
         * 
         * @return type 
         */
	public function getKeyword()
	{
		return $this->keyword;
	}
	
        /**
         *设置搜索的额外参数
         * 
         * @param array $params 
         */
	public function setParameters(array $params)
	{
		$this->parameters = $params;
	}
	
        /**
         *过去搜索返回数据
         * 
         * @param type $name
         * @return type 
         */
	public function getRecords($name = '')
	{
		if ($name == '') return $this->records;
		
		if (isset($this->records[$name]))
		{
			return $this->records[$name];
		}else 
		{
			return array('list'=>array(), 'is_end'=> 'true', 'count'=>0);
		}
	}
	
        /**
         *获取顶部搜索输入框下拉列表数据
         * 
         * @return array 
         */
	public function getTopInputResult()
	{	
		$result = $access = $list_ids = array();
		
		$title_1 = $title_2 = true;
		
		$top_list = $this->getPeopleAndWebsite();

		$count = 0;

		foreach ($top_list as $key => $val)
		{
			$temp = array();
			
			if (is_object($val)) $val = get_object_vars($val);

			switch($val['type'])
			{
				case 1:
					if ($title_1)
					{
						array_push($result, array("label"=>"人名" ,"title"=>true, "category" => 1));
						$title_1 = false;  
					}
                                        $temp['id'] = $user_id = isset($val['user_id']) ? trim($val['user_id']) : '';
                                        $list_ids[] = $user_id;
                                        $user_id != "" && $access[$user_id]=  $this->string2Array($val, "base_access",array("base_access"=>array("type"=>1)));
					$temp["home"] = isset($val["home_addr"]) ? preg_replace("#\\s+#","/",trim($val["home_addr"])) : "";
                                        $temp['src'] = get_avatar($user_id);
					$temp['url'] = mk_url($this->configuration['location_path']['personal']['people'], isset($val['user_dkcode']) ? array('dkcode'=>$val['user_dkcode']) : array());
					$temp['label'] = $temp['value'] = isset($val['userinfo_user_name']) ? $val['userinfo_user_name'] : '';
					array_push($result, $temp);
					$count++;
					break;
				case 2:
					if ($title_2)
					{
						array_push($result, array("label"=>"网页" ,"title"=>true, "category" => 2));
						$title_2 = false;
					}
                                        $tags = isset($val["imname"]) ? trim($val["imname"]) : "";
                                        $tags != "" && $tags.=isset($val["iname"]) ? "/".trim($val["iname"]) : "";
                                        $tags != "" && $tags.=isset($val["ename"]) ? "/".trim($val["ename"]) : "";
                                        $temp["tags"] = $tags;
					$temp['id'] = isset($val['web_id']) ? $val['web_id'] : '';
					$temp['src'] = get_webavatar($temp['id']);
					$temp['url'] =  mk_url($this->configuration['location_path']['personal']['web'], array('web_id'=>$temp['id']));
					$temp['label'] = $temp['value'] = isset($val['name']) ? $val['name'] : '';
					array_push($result, $temp);
					$count ++;
					break;
			}							
		}

                $ship = service("Relation")->getMultiRelationStatus($this->current_user_id, $list_ids);

                foreach ($result as $key => $val)
                {
                    $relation = 1;
                    
                    if (isset ($val["category"]) && $val["category"] >= 2) break;
                    
                    if (isset ($val["category"]) && $val["category"] == 1) continue;
                    //本人或公开可看
                    if ($val["id"] == $this->current_user_id || $access[$val["id"]]["type"] == 1) continue;
                    
                    if (!isset($ship["u".$val["id"]]))  $relation = 1; 
                    //好友
                    else if ($ship["u".$val["id"]]  == 10)   $relation = 4;
                    //粉丝
		    else if($ship["u".$val["id"]]  >= 4)	$relation = 3;
                    //是否满足条件 好友
                    else $relation = 1;
                    
                    if ($access[$val["id"]]["type"] > $relation) $result[$key]["home"] = "";
                    //自定义
                    else if(isset($access[$val["id"]]["content"]) && !in_array($this->current_user_id, $access[$val["id"]]["content"])) $result[$key]["home"] = "";
                }
		if ($count > 0 )  array_push($result, $this->lastRow($count));

		return $result;
	}
	
        /**
         *获取人名搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setPeopleResult($start = 0, $limit =2)
	{		
		$default_relation = '';
		
		$rel_ids = $result = $permiss = array();
		
		$permiss_default = array("school_access"=>  array("type"=>1),"base_access"=>  array("type"=> 4),"company_access"=>array("type"=>4));

		$people = $this->service->getPeopleList($this->keyword, $start, $limit, $this->parameters);
                
		foreach ($people->response->docs as $key => $val)
		{
			$temp = array();
			
			if (is_object($val)) $val = get_object_vars($val);
		
			$temp['url'] = mk_url($this->configuration['location_path']['personal']['people'], isset($val['user_dkcode']) ? array('dkcode'=>$val['user_dkcode']) : array());
			
			$temp['img'] = get_avatar(isset($val['user_id']) ? $val['user_id'] : '');
			
			$temp['name'] = isset($val['userinfo_user_name']) ? $val['userinfo_user_name'] : '';
			
			$temp['uid'] = isset($val['user_id']) ? $val['user_id'] : '';
                        
                        $temp['home'] = isset($val['home_addr']) && trim($val["home_addr"]) != "" ? $val['home_addr'] : '';
			
			$temp['company'] = isset($val['company']) && count($val['company']) > 0 ? current($val['company']) : '';
			
			if (isset($this->parameters['middle_school']) && trim($this->parameters['middle_school']) != null && isset($this->parameters['college']) && trim($this->parameters['college']) != null)
			{
				$temp['school'] = $this->parameters['college'].' '.$this->parameters['middle_school'];
								
			}else if (isset($val['school_name']) && $school_len = count($val['school_name']) > 0){	
				
				if (isset($this->parameters['middle_school']) && $school = array_search($this->parameters['middle_school'], $val['school_name']))
				{
					unset($val['school_name'][$school]);

					$temp['school'] = $this->parameters['middle_school'] . " " . current($val['school_name']);

				}else{
					$temp['school'] = implode(" ", array_splice($val['school_name'], 0, 2));
				}
			}else 
				$temp['school'] = '';
			
			if (isset($val['now_addr']) && ($now_addr = trim($val['now_addr'])) != '') 
			{			
				$temp['address'] = $now_addr; 
			}else
			
				$temp['address'] = ''; 
			
			if ($temp['uid'] == $this->current_user_id)
			{
				$temp['self'] = true;
			}else{
				
				$temp['self'] = false;
				
				if (! in_array($temp['uid'], $rel_ids))	$rel_ids[] = $temp['uid'];
			}

			$permiss[$temp['uid']]['school'] = $this->string2Array($val, "school_access",$permiss_default);

			$permiss[$temp['uid']]['company'] = $this->string2Array($val, "company_access",$permiss_default);
			
			$permiss[$temp['uid']]['address'] = $this->string2Array($val, "base_access",$permiss_default);
			
			$result[$key] = $temp;
		}

		$this->records['people']['count'] = $people->response->numFound;

		$this->records['people']['type'] = 1;
		
		$this->records['people']['is_end'] =  $this->isLastPage($people->response->numFound, $start, $limit);

		$r_people = service('Relation');
		
		$relations =  $r_people->getMultiRelationStatus($this->current_user_id, $rel_ids) ;
                            
		$common_friends = $r_people->getMultiCommonFriends($this->current_user_id, $rel_ids);

		foreach ($result as $key => $val)
		{
                       if($val["self"] == true) continue;
                    
			$val['relation'] = isset($relations['u'.$val['uid']]) ? $relations['u'.$val['uid']] : $default_relation;
			
			if ($val['relation'] == 10) $relation = 4;
			
			else if($val['relation'] >= 4)	$relation = 3;
			
			else $relation = 1;
			
			$val['common_friend_count'] = isset($common_friends[$val['uid']]) ? count($common_friends[$val['uid']]) : 0;
                        
                        if ( ($school  = $this->notAccessible($permiss, $val["uid"], "school", $relation)) ) $val["school"] = "";
                        
                        if ( ($address = $this->notAccessible($permiss, $val["uid"], "address", $relation))) $val["home"] = $val["address"] = "";
                        
                        if ( ($company = $this->notAccessible($permiss, $val["uid"], "company", $relation))) $val["company"] = "";
			
			$result[$key] = $val;
		}

		$this->records['people']['list'] = $result;
	}
        
        protected function notAccessible($permiss, $user_id, $key, $relation)
        {
                $return = false;

                $permiss_level = $permiss[$user_id][$key]['type'];;

		if (isset($permiss[$user_id][$key]['content']) && ! in_array($this->current_user_id, $permiss[$user_id][$key]['content'])) $return = $this->permiss[$permiss_level];
				
		else if ($permiss_level > $relation)    $return = $this->permiss[$permiss_level];  
                
                return $return;
        }


        protected function string2Array($array, $key,$default)
        {
            if (isset($array[$key]) && ($string = $array[$key]) != "" && is_string($string))
            {
                $array = array();

                foreach (json_decode($string) as $key => $value)
                {
                    if ($key == "content" && is_object($value))    $array["content"] = get_object_vars ($value);

                    else    $array[$key] = $value;
                }
                
                return $array;
                
            }else return $default[$key];        
        }

        /**
         *获取网页搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	
	public function setWebpageResult($start=0 ,$limit=2)
	{
		$rel_ids = $result = array();
		
		$default_relation = array('type'=>'', 'relation'=>'', 'days'=>'');
                
                $separator = " ";
	
		$webpage = $this->service->getWebpageList($this->keyword, $start, $limit);
		
		foreach ($webpage->response->docs as $key => $val)
		{
			$temp = array();
			
			if (is_object($val)) $val = get_object_vars($val);
			
			$temp['f_uid'] = isset($val['user_id']) ? $val['user_id'] : '';
			
			$temp['web_id'] = isset($val['web_id']) ? $val['web_id'] : '';
			
			$temp['url'] = mk_url($this->configuration['location_path']['personal']['web'], array('web_id'=>$temp['web_id']));
			
			$temp['img'] = get_webavatar($temp['web_id']);
			
			$temp['fans_count'] = isset($val['fansCount']) ? number_format($val['fansCount']) : 0;
			
			$temp['full_name'] = $temp['name'] = isset($val['name']) ? $val['name'] : '';
                        
                                           $imname = isset ($val["imname"]) ? $val["imname"] : '';
                                           
                                           $iname = isset($val["iname"]) ? $val["iname"] : "";
                                           
                                           $ename = isset($val["ename"]) ? $val["ename"] : "";
                                           
                                           $temp["tags"] = $imname.$separator.$iname.$separator.$ename;

			if ($temp['f_uid'] == $this->current_user_id)
			{
				$temp['self'] =  true ;
			
			}else{

				$temp['self'] = false;
				
				if (! in_array($temp['web_id'], $rel_ids))	$rel_ids[] = $temp['web_id'];
			}
			
			$result[$key] = $temp;
		}
		
		$this->records['web']['count'] = $webpage->response->numFound;
		
		$this->records['web']['type'] = 2;
		
		$this->records['web']['is_end'] = $this->isLastPage($webpage->response->numFound, $start, $limit);
		
		$r_webpage = service('WebpageRelation');

		$relations = $r_webpage->checkUserFollowings($this->current_user_id, $rel_ids);
		
		foreach ($result as $key => $val)
		{
			$val['relation'] = isset($relations[$val['web_id']]) ? $relations[$val['web_id']] : $default_relation;
			
			$result[$key] = $val;
		}

		$this->records['web']['list'] = $result;
	}
	
        /**
         *获取状态搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setStatusResult($start=0, $limit=2)
	{	
		$user_ids=$result = array();

		$status = $this->service->getStatusList($this->keyword, $start, $limit);

		foreach ($status->response->docs as $key => $val)
		{
			$temp = array();
						
			if (is_object($val)) $val = get_object_vars($val);

                        $temp["share_type"] = $text_type = isset($val['status_type']) ? $val['status_type'] : 'info';
                        
                        $temp["user_dkcode"] =  isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
                                    
                        $temp["user_id"] = isset($val['user_id']) ? $val['user_id'] : "";
                        
                        $temp["comment_id"] = isset($val["unique_id"]) ? preg_replace("#[a-zA-Z_]+#", "", $val["unique_id"]) : "";
                        
                        $temp["web_id"] = isset($val['web_id']) ? $val['web_id'] : '';
                        
			if (isset($val['person_web_type']) && $val['person_web_type'] == 1)
			{
                                
                                $temp["page_type"] = strcasecmp($text_type, "info") ? "web_".$text_type : "web_topic";
				
				$temp['full_name'] = $temp['name']= isset($val['web_name']) ? $val['web_name'] : '';
				
				$temp['img'] = get_webavatar($temp["web_id"] );
				
				$temp['url'] = mk_url($this->configuration['location_path']['web']['status'], array('web_id'=>$temp["web_id"] ));
			}else{
                                $temp["page_type"] = strcasecmp($text_type, "info") ? $text_type : "topic";

				$temp['img']=get_avatar( $temp["user_id"]);
				
				$temp['full_name'] = $temp['name']= isset($val['user_name']) ? $val['user_name'] : '';
				
				$temp['url']=mk_url($this->configuration['location_path']['personal']['status'], array('dkcode'=>$temp["user_dkcode"] ));
			}

			$temp['time'] = isset($val['createTime']) ? friendlyDate($val['createTime']) : '';
			
			if (isset($val['content_show']))
			{
				$text = $this->htmlSubString($val['content_show'], $this->configuration['body']['status'], $this->charset);
				
				$temp['text'] = $text['is_cut'] ? $text['content'].$this->subfix : $text['content'];
			}else 
				$temp['text'] = '';		
			
			switch ($text_type)
			{
				case 'photo':
					if ( isset($val['picurl']) )
					{
						$picurl = get_object_vars(current(json_decode($val['picurl'])));
						
						$info['groupname'] = $picurl['groupname'];

						$info['file_name'] = $picurl['filename'];
						
						$info['photo_type'] = $picurl['type'];
                                                
                                                $temp["self_id"] = isset($picurl["pid"]) ? $picurl["pid"] : "";

						$temp['text_img'] = $this->getAlbumOrPhotoImg($info);
                                                
                                                $temp["text_big_img"] = $this->getAlbumOrPhotoImg($info,"b");
                                                
                                                break;
					}
                                        
				case 'video':
                                                $temp["self_id"] = isset($val["videoid"]) ? $val["videoid"] : "";
                                                
						$cover = isset($val['imgurl']) ? config_item('video_pic_domain').$val['imgurl'] : '';
                                                //获取小图
                                                $temp['text_img'] = $this->getVideoCoverPicture($cover, "_1");
                                                //获取原图
                                                $temp["text_big_img"] = "vid=".$temp["self_id"] ."&uid=".$temp["user_id"];
                                                
						break;
				default:
					$temp["text_big_img"] = $temp['text_img'] = $temp["self_id"] = ''; 	
			}
                        
			if (  ! in_array ( $temp["user_id"], $user_ids )) $user_ids[] = $temp["user_id"];
                        
			$result[$key] = $temp;
		}
 
		$this->records['status']['count'] = $status->response->numFound;
		
		$this->records['status']['is_end'] = $this->isLastPage($status->response->numFound, $start, $limit);
		
		$this->records['status']['type'] = 3;
		
		$this->records['status']['list'] = $result;
	}
	
        /**
         *判断搜索结果是否结束
         * 
         * @param type $count
         * @param type $start
         * @param type $limit
         * @return type 
         */
	protected function isLastPage($count , $start, $limit)
	{
		if ($count == 0) return true;
		
		return $count - $start > $limit ? false : true;
	}
	
        /**
         *获取图片搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setPhotoResult($start=0, $limit=2)
	{		
		$result = array();

		$photo = $this->service->getPhotoList($this->keyword, $start, $limit);
                
		foreach ($photo->response->docs as $key => $val)
		{
			$temp = $query_string = $photo_info = array();
			
			if (is_object($val)) $val = get_object_vars($val);
                        
                        $temp["thumb"] = $this->getAlbumOrPhotoImg($val, 's');
			
			$temp["photo_id"] = isset($val['id']) ? $val['id'] : '';
         
                        $temp["user_id"] = isset($val['user_id']) ? $val['user_id'] : '';
			
                        $temp["web_id"] = isset($val['web_id']) ? $val['web_id'] : '';
                        
			if (isset($val['person_web_type']) && $val['person_web_type'] == 1)
			{	
				$temp['author_name'] = isset($val['web_name']) ? $val['web_name'] : '';
				
				$temp['author_img'] = get_webavatar($temp["web_id"]);
                                
                                $temp["photo_type"] = "web_photo";
				
				$temp['author_url'] = mk_url($this->configuration['location_path']['personal']['web'], array('web_id' => $temp["web_id"]));
			}else{
				
				$user_dkcode = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';					
				
                                $temp["photo_type"] = "photo";
                                
				$temp['author_name'] = isset($val['user_name']) ? $val['user_name'] : '';
				
				$temp['author_img'] = get_avatar($temp["user_id"]);
				
				$temp['author_url'] = mk_url($this->configuration['location_path']['personal']['people'], array('dkcode'=>$user_dkcode));
				
			}
			
			$temp['url'] = $this->getAlbumOrPhotoImg($val, "");
			
			$temp['img'] = $this->getAlbumOrPhotoImg($val, 's');
			
			$temp['time']=isset($val['createTime']) ? preg_replace("#([^\\d:]|^)0(\\d{1})#", "\\1\\2",date($this->configuration['time']['photo'], $val['createTime'])) : '';
			
			if (isset($val['name']))
			{
				$temp['full_name']= htmlspecialchars($val['name'], ENT_QUOTES);
			
				$temp['name'] = $this->cutString($val['name'], $this->configuration['title']['photo']);
				
			}else 
				$temp['name'] = $temp['fullname'] = '';
			
			$result[$key] = $temp;
		}

		$this->records['photo']['list'] = $result;
		
		$this->records['photo']['type'] = 4;
		
		$this->records['photo']['count'] = $photo->response->numFound;
		
		$this->records['photo']['is_end'] = $this->isLastPage($photo->response->numFound, $start, $limit);
	}
	
        /**
         *获取相册搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setAlbumResult($start=0, $limit=2)
	{	
		$result = array();
				
		$album = $this->service->getAlbumList($this->keyword, $start, $limit);
                
		foreach ($album->response->docs as $key => $val)
		{
			$temp = array();
			
			if (is_object($val)) $val = get_object_vars($val);
			
			$user_dkcode = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
			
			$album_id = isset($val['id']) ? $val['id'] : '';
							
			if (isset($val['person_web_type']) && $val['person_web_type'] == 1)
			{
				$path = $this->configuration['location_path']['web']['album']; 
				
				$web_id = isset($val['web_id']) ? $val['web_id'] : '';
				
				$query_string = array('web_id'=>$web_id,'albumid'=>$album_id, 'dkcode'=>$user_dkcode);
				
			}else{
				$path = $this->configuration['location_path']['personal']['album'];
				
				$query_string = array('dkcode'=> $user_dkcode, 'albumid'=> $album_id);
			}
			
			$temp['url']=mk_url($path, $query_string);
			
			$temp['count']= '共 '.(isset($val['photo_count']) ? number_format($val['photo_count']) : 0).' 张';
		
			$temp['img']=$this->getAlbumOrPhotoImg($val);

			$temp['time']=isset($val['createTime']) ? date($this->configuration['time']['album'], $val['createTime']) : '';
			
			if (isset($val['name']))
			{
				$temp['full_name']=htmlspecialchars($val['name'], ENT_QUOTES);
				
				$temp['name'] = $this->cutString($val['name'], $this->configuration['title']['album']);				
			}else 
				$temp['name'] = $temp['fullname'] = '';
			
			$result[$key] = $temp;
		}
		
		$this->records['album']['type'] = 5;
		
		$this->records['album']['list'] = $result;
		
		$this->records['album']['count'] = $album->response->numFound;
		
		$this->records['album']['is_end'] = $this->isLastPage($album->response->numFound, $start, $limit);
				
	}
	
        /**
         *获取相册或图片 资源地址
         * 
         * @param type $info
         * @param type $thumb
         * @return type 
         */
	protected function getAlbumOrPhotoImg($info, $thumb = 'f')
	{
                $group = $filename = "";
            
		$ext='gif';
		
		if (isset($info['file_name'])) $filename = $info['file_name'];
		
		if (isset($info['groupname'])) $group = $info['groupname'];
		
		if (isset($info['photo_type'])) $ext = $info['photo_type'];
		
                if (trim($group) == "" || trim($filename) == "") return MISC_ROOT."misc/img/default/album_default.png";
                    
		$filename .= $thumb != "" ? "_".$thumb : "";
		
		return "http://".config_item("fastdfs_domain")."/".$group."/".$filename.".".$ext;
	}
	
        /**
         *获取视频搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setVideoResult($start = 0, $limit =2)
	{
		$result = array();
		
                $blank = "target=\"_blank\"";
                
		$video = $this->service->getVideoList($this->keyword, $start, $limit);
        
		foreach ($video->response->docs as $key => $val)
		{
			$temp = array();
			
			if (is_object($val)) $val = get_object_vars($val);

			$video_id = isset($val['id']) ? $val['id'] : '';
                        
                        $temp["view_times"] = isset ($val["totalCount"]) ? number_format($val["totalCount"]) : 0;
		
			if (isset($val['person_web_type']) && $val['person_web_type'] == 1)
			{
				$web_id = isset($val['web_id']) ? $val['web_id'] : '';
				
				$temp['author_url'] = mk_url($this->configuration['location_path']['personal']['web'], array('web_id'=>$web_id));
				
				$author_fullname = isset($val['web_name']) ? $val['web_name'] : '';
                                
				$temp['url'] = mk_url($this->configuration['location_path']['web']['video'], array('web_id'=> $web_id, 'vid'=>$video_id));
			}else{
				$user_dkcode = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
				
				$temp['author_url'] = mk_url($this->configuration['location_path']['personal']['people'], array('dkcode'=>$user_dkcode));
				
				$author_fullname = isset($val['user_name']) ? $val['user_name'] : '';
				
				$temp['url'] = mk_url($this->configuration['location_path']['personal']['video'],array('vid'=> $video_id));
			}
                        
                        if ($author_fullname != "") 
                        {
                            $temp["author_fullname"] = htmlspecialchars($author_fullname, ENT_QUOTES);
                            
                            $temp['author_name'] =  $this->cutString ($author_fullname, 5); 
                            
                        }else   $temp["author_fullname"] =  $temp['author_name'] = "";
                        
			$video_img = isset($val['video_pic']) ? $val['video_pic'] : ''; 
                         
                        if ($video_img != "") $temp["img"] = $this->getVideoCoverPicture($video_img, "_1");
                        
                        else $temp["img"] = "";
			
			$temp['time']=isset($val['createTime']) ? $this ->  format_time($val["createTime"]) : '';
                          			
			if (isset($val['title']))
			{
				$temp['full_name'] = htmlspecialchars($val['title'], ENT_QUOTES);
				
				$temp['name'] = $this->cutString($val['title'], $this->configuration['title']['video']);
			}else 
				$temp['name'] = $val['full_name'] = '';
			
			$result[$key] = $temp;
			
		}
         
		$this->records['video']['list'] = $result;
		
		$this->records['video']['type'] = 6;
		
		$this->records['video']['count'] = $video->response->numFound;
		
		$this->records['video']['is_end'] = $this->isLastPage($video->response->numFound, $start, $limit);
				
	}
        
        protected function getVideoCoverPicture($picture_query, $type="")
        {
            
                $last_position = strrpos($picture_query, ".");
                
                if (preg_match("#_1$#",substr($picture_query, 0, $last_position)))  $picture_query = substr ($picture_query, 0, $last_position - 2).substr ($picture_query, $last_position);            
            
                return get_video_img($picture_query, $type);
        }


        /**
         *获取博客搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setBlogResult($start = 0, $limit =2)
	{	
		$result = array();
		
		$blog = $this->service->getBlogList($this->keyword, $start, $limit);

		foreach ($blog->response->docs as $key => $val)
		{
			$temp = array();
			
			$query_string = array();
			
			if (is_object($val)) $val = get_object_vars($val);
			
			$query_string['id'] = isset($val['id']) ? $val['id'] : '';
			
			$query_string['dkcode'] = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
			
			$temp['url']=mk_url($this->configuration['location_path']['personal']['blog'], $query_string);
			
			$temp['time']=isset($val['createTime']) ? friendlyDate($val["createTime"]) : '';
			
			if (isset($val['summary']))
			{
				$val['summary'] = preg_replace("/\\{img\\_\\d{3}\\}/i", "", $val['summary']);
				
				$text = $this->htmlSubString($val['summary'], $this->configuration['body']['blog']);
				
				$temp['text'] = $text['is_cut'] ? $text['content'].$this->subfix : $text['content'];
				
			}else 
			    $temp['text'] = '';
			
			    
			if (isset($val['title']))
			{
				$temp['full_name'] = htmlspecialchars($val['title'], ENT_QUOTES);
				
				$temp['name'] = $this->cutString($val['title'], $this->configuration['title']['blog']);
				
			}else 
			    $temp['name'] = $temp['full_name'] = '';
                        
                                           $temp["author_img"] = get_avatar(isset ($val["user_id"]) ? $val["user_id"] : "");

			$action_dkcode = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
			
			$temp['home_page'] = mk_url($this->configuration['location_path']['personal']['people'], array('dkcode'=>$action_dkcode));
			
			$temp['author'] = isset($val['user_name']) ? $val['user_name'] : '';
		
			$result[$key] = $temp;

		}

		$this->records['blog']['list'] = $result;
		
		$this->records['blog']['type'] = 7;
		
		$this->records['blog']['count'] = $blog->response->numFound;
		
		$this->records['blog']['is_end'] = $this->isLastPage($blog->response->numFound, $start, $limit);
				
	}
	
        /**
         *获取问答搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setAskResult($start=0, $limit=2)
	{
		$result = array();
			
		$ask = $this->service->getQuestionAndAnswerList($this->keyword, $start, $limit);

		foreach ($ask->response->docs as $key => $val)
		{
			$temp = array();

			$query_string = array();
			
			if (is_object($val)) $val = get_object_vars($val);
			
			$query_string['from'] = 'notice';
			
			$query_string['poll_id'] = isset($val['id']) ? $val['id'] : '';
			
			$query_string['dkcode'] = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
			
			$temp['url']= mk_url($this->configuration['location_path']['personal']['ask'], $query_string);

			$temp['ask']=$this->getQAInfo($val);

			$temp['time']=isset($val['createTime']) ? date($this->configuration['time']['ask'], $val['createTime']) : '';
						
			if (isset($val['title']))
			{
				$temp['full_name'] = htmlspecialchars($val['title'], ENT_QUOTES);

				$temp['name'] = $this->cutString($val['title'], $this->configuration['title']['ask']);
					
			}else
			
				$temp['name'] = $temp['fullname'] = '';

				$temp['author_name'] = isset($val['user_name']) ? $val['user_name']: ' ';
				
				$user_id = isset($val['user_id']) ? $val['user_id'] : '';
				
				$temp['author_img'] = get_avatar($user_id);	
				
				$user_dkcode = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
				
				$temp['author_url'] = mk_url($this->configuration['location_path']['main'], array('dkcode'=>$user_dkcode));
			
			$result[$key] = $temp;
		}

		$this->records['ask']['list'] = $result;
		
		$this->records['ask']['count'] = $ask->response->numFound;
		
		$this->records['ask']['type'] = 8;
		
		$this->records['ask']['is_end'] = $this->isLastPage($ask->response->numFound, $start, $limit);
			
	}
	
        /**
         *解析问答选项
         * 
         * @param type $data
         * @return array 
         */
	protected function getQAInfo($data)
	{		
		$array = array();
		//单选还是多选
		$array['type'] = ((int)$data['multiple'] == 1) ? "radio" : "checkbox" ;
		//是否显示更多连接
		if (!isset($data['ask_option_list']))  
		{
			$array['more_link'] = false;
			
			$array['list']=array();
			
			return $array;
		}
		//计算投票总数
		if($array['type'] == 0)
		{
			$total_votes = isset($data['totalVotes']) ? $data['totalVotes'] : 0;
		}else{
			$total_votes = $this->getMaxValueByCheckbox($data['ask_option_list']);
		}
		//问答显示内容
		foreach ($data['ask_option_list'] as $key => $val)
		{
			$val = json_decode($val);
			if ($key > 3)
			{
				$array['more_link'] = true;
				
				unset($array['list'][3]);
				
				break;
			}else
				
				$array['more_link'] = false;
			
			$title = isset($val->option_message) ? $val->option_message : '';
			
			if ($title != null)
			{
				$title = $this->transferSpecialChar($title, false);
				
				$return = $this->cutString($title, $this->configuration['other']['ask_option_length']);
			}else{
				$return = '';
			}
			//计算百分比
			$percent = $total_votes == 0 ? 0 : $val->option_votes*100/$total_votes;
			
			$array['list'][$key] = array('name'=>$return, 
			
											 'full_name'=>htmlspecialchars($title, ENT_QUOTES), 
			
											 'percent'=>$percent);
		}
		
		return $array;		
	}
	
        /**
         *计算问答选项百分比
         * 
         * @param type $data
         * @return type 
         */
	private function getMaxValueByCheckbox($data)
	{
		$total_votes = 0;
		
		foreach ($data as $val)
		{
			$val = json_decode($val);
			
			$option_votes = isset($val->option_votes) ? $val->option_votes : 0;
			
			$total_votes = $option_votes > $total_votes ? $option_votes : $total_votes;
		}
		return $total_votes;
	}
	
        /**
         *获取活动搜索结果
         * 
         * @param type $start
         * @param type $limit 
         */
	public function setEventResult($start = 0, $limit=2)
	{	
		$result = array();
	
		$event = $this->service->getEventList($this->keyword, $start, $limit);

		foreach ($event->response->docs as $key => $val)
		{
			$temp = array();
			
			if (is_object($val)) $val = get_object_vars($val);
			
			$id = isset($val['id']) ? $val['id'] : '';
			
			if (isset($val['person_web_type']) && $val['person_web_type'] == 1)
			{
				$temp['url'] = mk_url($this->configuration['location_path']['web']['event'], array('id' => $id,'web_id'=>(isset($val['web_id'])) ? $val['web_id'] : ''));
				
				$temp['originator'] = isset($val['web_name']) ? $val['web_name'] : '';
					
				$web_id = isset($val['web_id']) ? $val['web_id'] : '';				
					
				$temp['originator_url'] = mk_url($this->configuration['location_path']['personal']['web'], array('web_id'=>$web_id));
			}else{ 			
				
				$temp['url']= mk_url($this->configuration['location_path']['personal']['event'], array('id'=>$id));
					
				$temp['originator'] = isset($val['user_name']) ? $val['user_name'] : '';
					
				$user_dkcode = isset($val['user_dkcode']) ? $val['user_dkcode'] : '';
					
				$temp['originator_url'] = mk_url($this->configuration['location_path']['personal']['people'], array('dkcode'=>$user_dkcode));
			}
                                           
                                           $detail = (isset($val["detail"]) && $val["detail"] != "") ? $val["detail"] : "未填写...";
                                           
                                           $temp["detail"] = $this->cutString($detail, $this->configuration["body"]["event"]);
                                           
                                           $starttime = isset($val["starttime"]) ? strtotime($val["starttime"]) : "";

                                           $endtime = isset($val['endtime']) ? strtotime($val["endtime"]) : "";
                                         
                                           $current_time = time();                                   

                                           if ($starttime > $current_time)     $temp["status"] = "即将开始";
                                          
                                           else if ($endtime != "" && $endtime < $current_time)   $temp["status"] = "已经结束";
                                           
                                           else $temp["status"] =  "正在进行" ;
                        
                                           $temp["join_num"] = isset ($val["joinNum"]) ? $val["joinNum"] : 0;
                                           
			$temp['img']=$this->getEventImg($val);

			$temp['start_time'] = $this->  formatEventTime($starttime);

			$temp['end_time'] = $this->  formatEventTime($endtime);
			
			if (isset($val['name']))
			{
				$temp['full_name']=htmlspecialchars($val['name'],ENT_QUOTES);
				
				$temp['name'] = $this->cutString($val['name'], $this->configuration['title']['event']);

			}else
			
				$temp['name']=$temp['full_name'] = '';
				
			$result[$key] = $temp;
		}

		$this->records['event']['type'] = 9;
		
		$this->records['event']['list'] = $result;
		
		$this->records['event']['count'] = $event->response->numFound;
		
		$this->records['event']['is_end'] = $this->isLastPage($event->response->numFound, $start, $limit);
				
	}
	
            /**
             *活动时间格式化
             * 
             * @param type $time
             * @return type 
             */
        
            private function formatEventTime($timestamp)
            {   
                if ($timestamp == "") return "";

                $time_string = date($this ->configuration["time"]["event"], $timestamp);

                $time_string = preg_replace("#([^\\d\\s:]|^)0([1-9])#" ,  "$1$2" , $time_string);

                return $time_string;
            }
	
            /**
             *获取活动封面
             * 
             * @param type $info
             * @return type 
             */
	protected function getEventImg($info)
	{
		if (isset($info['fdfs_group']) && isset($info['fdfs_filename']))
		
			if (!empty($info['fdfs_group']) && !empty($info['fdfs_filename']))

				return "http://".config_item("fastdfs_domain")."/".$info['fdfs_group']."/".$info['fdfs_filename'];
		
		return MISC_ROOT.'img/default/event.jpg';		
	}
	
        /**
         *字符还原
         * 
         * @param type $html
         * @param type $single
         * @return type 
         */
	private function transferSpecialChar($html, $single=true)
	{
		$html = str_replace('&gt;', '>', $html);
		$html = str_replace('&lt;', '<', $html);
		$html = str_replace('&quot;', '"', $html);
		$html = str_replace('&amp;', '&', $html);
		if ($single)
			$html = str_replace("&#039;", "'", $html);
		return $html;	
	}
	
        /**
         *截取字符窜
         * 
         * @param string $str
         * @param type $cut_len
         * @return type 
         */
	protected function cutString($str, $cut_len)
	{
		if (mb_strlen($str, $this->charset) > $cut_len)
		{
			$str = mb_substr($str, 0, $cut_len, $this->charset).$this->subfix;
		}
		return htmlspecialchars($str, ENT_QUOTES);
	}
	
        /**
         * 获取人名与网页 前8条记录
         *
         * @return type 
         */
	protected function getPeopleAndWebsite()
	{
		$people_webpage = $this->service->getPeopleAndWebsite($this->keyword);

		$people_length = count($people_webpage['people']);
		$website_length = count($people_webpage['website']);

		if ($people_length >=4 && $website_length >= 4)
		{
			return array_merge(array_slice($people_webpage['people'], 0, 4) , array_slice($people_webpage['website'], 0, 4));
		}
		
		if ($people_length >= 4 && $website_length < 4)
		{
			return array_merge(array_slice($people_webpage['people'], 0, 8 - $website_length) , $people_webpage['website']);
		}
		
		if ($people_length < 4 && $website_length >= 4)
		{	
			return array_merge($people_webpage['people'] , array_slice($people_webpage['website'], 0, 8 - $people_length));
		}
		
		return array_merge($people_webpage['people'], $people_webpage['website']);
	}

        /**
         *截取博客摘要内容
         * 
         * @param type $content
         * @param type $maxLen
         * @param type $charset
         * @return type 
         */
           private function htmlSubString($content,$maxLen=140, $charset = 'utf-8')
           {

                        $content = str_replace(array("\r","\n"),"",$content);
                        $curLen = mb_strlen(str_replace(" ","",$this->transferSpecialChar(strip_tags($content))),$charset);
                        if($curLen <= $maxLen)
                        {
                                $content =preg_replace("#<\\s*table.*?>#is", "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\">", $content);
                        return array('content'=>$content, 'is_cut'=>false);
                        }else{
                        $cut_content = $this->cutHtmlTextStr($content, $maxLen, $charset);
                                $cut_length = mb_strlen($cut_content, $charset);
                                $cut_content = preg_replace("#<\\s*table.*?>#is", "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\">", $cut_content);
                                $lost_tags = $this->findLostTags(mb_substr($content, $cut_length, mb_strlen($content, $charset),$charset), $charset);
                                return array('content'=>$cut_content.$lost_tags, 'is_cut'=>true);
                        }
           }
   
           /**
            *截取HTMLText相关内容,HTML标签不算
            * 
            * @param type $content
            * @param type $length
            * @param type $charset
            * @return type 
            */
           private function cutHtmlTextStr($content, $length = 140, $charset='utf-8')
           {
                        $str = '';
                        $record = true;
                        $placeholder = 0;
                        $transfered = array("&nbsp;","&lt;","&gt;","&#039;","&amp;");
                        for($i=0; $i<mb_strlen($content); $i++)
                        {
                                $char = mb_substr($content, $i, 1);
                                if ($char == "&")
                                        foreach ($transfered as $html)
                                        {
                                                $html_len =mb_strlen($html, $charset);
                                                $cut_flag = mb_substr($content, $i, $html_len, $charset);
                                                if ($cut_flag == $html)
                                                {
                                                        $i= $i+$html_len-1;
                                                        $char = $cut_flag;
                                                        break;
                                                }
                                        }
                                $str.=$char;
                                if ($char == "<")
                                {	
                                        $record = false;
                                }
                                if ($char == ">")
                                {
                                        $record = true;
                                }

                                if ($record && $char != ' ') 
                                {
                                        $plain = str_replace(array(" ","\r","\n"),"",strip_tags($str));
                                        $plain = str_replace($transfered, $placeholder, $plain);
                                        if (mb_strlen($plain, 'utf-8') == $length) break;
                                }
                        }
                        return $str;
          }
        /**
         *反补HTML标签
         * 
         * @param type $content
         * @param type $charset
         * @return string 
         */
          private function findLostTags($content, $charset='utf-8')
          {
	    $pos = '';
	    $prefix = '';
	    $tag_str = '';
	    $table = array('</tr>','</th>','</tbody>');
		$pattern = "#<\\s*([a-z]+).*?><\\s*/\\s*\\1>#i";
		$content = str_replace('<br>', '', $content);
		preg_match_all("#<.*?>#is", $content, $tags);
		if (count($tags[0]) > 0)
		{
			$tag_str = implode("", $tags[0]);
			foreach($table as $_t)
			{
				if (($pos = strpos($tag_str, $_t)) > 0) 
				{
					$pos += mb_strlen($_t, $charset);
					$prefix = mb_substr($tag_str, 0, $pos, $charset);
					break;
				}
			}
			if ($pos != '') $tag_str = mb_substr($tag_str, $pos, mb_strlen($content, $charset), $charset);
			$surplus = preg_replace($pattern, "", $tag_str);
			$final_tag = $prefix.preg_replace("#<\\s*[a-z]+.*?>#is", "", $surplus);
			return $final_tag;
		}else{
	   			return '';
		}
	}
	
            /**
             *格式化左侧导航统计数
             * 
             * @param type $count
             * @param type $max
             * @return type 
             */
	protected function getCountFormat($count, $max = 500)
	{
		return $count > $max ? $max.'+' : $count;
	}

            /**
             *input框查看更多
             * 
             * @param type $num
             * @return type 
             */
	public function lastRow($num=0)
	{		
		$label = "<b style='height:23px'>查看更多<u></u></b>";	

		$url = mk_url($this->configuration['location_path']['main'], array('type'=>'people','term'=>urlencode($this->keyword), 'init'=>time()));
		
		return array('id' => 0,'label' => $label,'src' => '','url' => $url,'value' => '');
	}
            /**
             *视频时间格式化
             * 
             * @param type $timestamp
             * @return type 
             */
              private function format_time($time = 0)
              {
                  $timestamp = is_numeric($time) ? intval($time) : strtotime ( $time);
                  
                  $sub_time = (time() - $timestamp);

                  //秒前
                  if (($sub_time / 60)< 1) 
                  {
                      $num = $sub_time;
                      $subfix = "秒前";
                  //分钟前
                  }else if (($num = $sub_time / 60) < 60) $subfix  = "分钟前";
                  //小时前
                  else if ( ($num = $sub_time / (60*60)) < 24) $subfix = "小时前";
                  //天前
                  else if (($num = $sub_time/ (60*60*24)) < 7*4) $subfix = "天前";
                  //月前
                  else if (($num = $sub_time / (60*60*24*7*4)) < 12) $subfix = "月前";
                  //年前
                  else {
                      $num = $sub_time / (60*60*24*7*4*12); 
                      $subfix = "年前";
                  }

                  return intval($num).$subfix;
              }
}
?>
