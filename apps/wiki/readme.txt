####说明
1. wiki需要上传图片， /var/tmp/wiki  需要写权限
2. wiki使用的是mongodb存储,迁移的时候请备份 wiki_settings   wiki_ziku   wiki_citiao  wiki_items  wiki_module_version  wiki_web_plugins 
    
    如果只是要导入系统数据，请执行以下步骤
    
    一、
    <?php
     //执行时请慎重，此操作会删除用户输入的全部数据
     $host = "192.168.86.3";  //请修改为对应服务器ip
     $port = "10009";  //请修改为对应服务器port
     $dbname = "wiki"; //请修改为对应服务器db

     $m = new Mongo("mongodb://$host:$port"); 
     $mdb = $m->$dbname;

     $user_create_item = $mdb->wiki_items->find(array('$or' => array(array("is_system" => "0"),array("is_system" => null))), array("_id"));
	 
     $user_create_item_id = array();
     foreach($user_create_item as $v){
        $user_create_item_id[] = $v['_id']->__toString();
     }

     $mdb->wiki_module_version->remove(array('$or' => array(array('item_id' => array('$in' => $user_create_item_id)), array('version' => array('$gt' => 1)), array("uid" => array('$gt' => "0")))));
     $mdb->wiki_citiao->remove(array('$or' => array(array("is_system" => "0"),array("is_system" => null))));
     $mdb->wiki_items->remove(array('$or' => array(array("is_system" => "0"),array("is_system" => null))));

    ?>
    二、
     执行脚本备份 

     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_settings -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_ziku -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_citiao -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_items -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_module_version -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_web_plugins -o wiki;
    
    三、
     把备份的目录 wiki  拷到要迁移的服务器上

     mongorestore -h 迁移的服务器 --port 27017 -d wiki_duankou  wiki/wiki_duankou   ###这样就把数据迁移完毕，注意不会覆盖原有数据


3. 清除全部数据  即删除数据库，mongodb会自动建立数据库
        mongo -h 192.168.12.252 --port 27017 
        use wiki_duankou; db.dropDatabase(); 

4. 建立索引脚本
   <?php
     header("content-type:text/html;charset=utf-8");
     $host = "192.168.86.3";  //请修改为对应服务器ip
     $port = "10009";  //请修改为对应服务器port
     $dbname = "wiki"; //请修改为对应服务器db

     $m = new Mongo("mongodb://$host:$port"); 
     $mdb = $m->$dbname;
     
     //先删除所有已有索引
     $allcollections = $mdb->listCollections();
     foreach($allcollections as $collection){ 
        $collection->deleteIndexes();
     }
     echo "删除全部已用索引成功";
     
     //针对每个集合建立索引
     $mdb->wiki_citiao->ensureIndex(array("citiao_title"=>1));
     $mdb->wiki_citiao->ensureIndex(array("create_time"=>1));

     $mdb->wiki_items->ensureIndex(array("citiao_id" => 1));
     $mdb->wiki_items->ensureIndex(array("web_p_id" => 1));
     $mdb->wiki_items->ensureIndex(array("web_s_ids" => 1));

     $mdb->wiki_module_version->ensureIndex(array("item_id" => 1, "version" => 1), array("unique" => true));

     $mdb->wiki_settings->ensureIndex(array("name" => 1));

     $mdb->wiki_web_info->ensureIndex(array("web_id" => 1, "item_id" =>1, "use_module_version"  => 1), array("unique" => true));

     $mdb->wiki_web_plugin_config->ensureIndex(array("imid" =>1, "enabled" => 1));

     $mdb->wiki_web_plugin_info->ensureIndex(array("web_id" => 1));

     $mdb->wiki_ziku->ensureIndex(array("char" => 1));
     echo "建立索引成功";
   ?>

5. 修改mongodb 读写,连接超时时间   
      修改My_controller 中的 mongo_cursor_timeout  
   