####˵��
1. wiki��Ҫ�ϴ�ͼƬ�� /var/tmp/wiki  ��ҪдȨ��
2. wikiʹ�õ���mongodb�洢,Ǩ�Ƶ�ʱ���뱸�� wiki_settings   wiki_ziku   wiki_citiao  wiki_items  wiki_module_version  wiki_web_plugins 
    
    ���ֻ��Ҫ����ϵͳ���ݣ���ִ�����²���
    
    һ��
    <?php
     //ִ��ʱ�����أ��˲�����ɾ���û������ȫ������
     $host = "192.168.86.3";  //���޸�Ϊ��Ӧ������ip
     $port = "10009";  //���޸�Ϊ��Ӧ������port
     $dbname = "wiki"; //���޸�Ϊ��Ӧ������db

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
    ����
     ִ�нű����� 

     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_settings -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_ziku -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_citiao -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_items -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_module_version -o wiki;
     mongodump -h 192.168.12.252 --port 27017 -d wiki_duankou -c wiki_web_plugins -o wiki;
    
    ����
     �ѱ��ݵ�Ŀ¼ wiki  ����ҪǨ�Ƶķ�������

     mongorestore -h Ǩ�Ƶķ����� --port 27017 -d wiki_duankou  wiki/wiki_duankou   ###�����Ͱ�����Ǩ����ϣ�ע�ⲻ�Ḳ��ԭ������


3. ���ȫ������  ��ɾ�����ݿ⣬mongodb���Զ��������ݿ�
        mongo -h 192.168.12.252 --port 27017 
        use wiki_duankou; db.dropDatabase(); 

4. ���������ű�
   <?php
     header("content-type:text/html;charset=utf-8");
     $host = "192.168.86.3";  //���޸�Ϊ��Ӧ������ip
     $port = "10009";  //���޸�Ϊ��Ӧ������port
     $dbname = "wiki"; //���޸�Ϊ��Ӧ������db

     $m = new Mongo("mongodb://$host:$port"); 
     $mdb = $m->$dbname;
     
     //��ɾ��������������
     $allcollections = $mdb->listCollections();
     foreach($allcollections as $collection){ 
        $collection->deleteIndexes();
     }
     echo "ɾ��ȫ�����������ɹ�";
     
     //���ÿ�����Ͻ�������
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
     echo "���������ɹ�";
   ?>

5. �޸�mongodb ��д,���ӳ�ʱʱ��   
      �޸�My_controller �е� mongo_cursor_timeout  
   