<?php
/**
 * [ Duankou Inc ]
 * @filename : AxisModel.php
 * @author xwsoul
 * Created on 2012-08-04
 */

class SocialModel extends DkModel {

    public function __initialize() {
        $this->init_redis();
    }

		/**
		 * 返回社交关系数量
		 * @return array
		 */
		public function getNums($uid, $ym) {
			$uid = (int)$uid;
			$dateStart = strtotime($ym.'01 00:00:00');
			$dateEnd = strtotime(date('Ymt 23:59:59', $dateStart));
			//关注者
			$data[0] = (int)$this->redis->zCount(
				sprintf('following:%d', $uid), $dateStart, $dateEnd);
			//好友
			$data[1] = (int)$this->redis->zCount(
				sprintf('friend:%d', $uid), $dateStart, $dateEnd);
			return $data;
		}

}
