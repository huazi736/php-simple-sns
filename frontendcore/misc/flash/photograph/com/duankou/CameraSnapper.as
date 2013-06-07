package com.duankou {
	import com.duankou.IEditSource;
    import flash.display.*;
    import flash.events.*;
    import flash.media.*;
	import flash.utils.Timer;
    import flash.system.*;
	import flash.ui.*;
	import flash.geom.ColorTransform;

    public class CameraSnapper extends Sprite implements IEditSource
    {
        private var os:String;
        private var camera:Camera;
		private var bitmap:Bitmap;
        private var bitmapData:BitmapData;
        private var video:Video;
		private var _timer:Timer;
		private var _videoIsWorked:Boolean = false;
		private var colorTransform:ColorTransform;
		private var filter:*;
		private var times:Number;
		
		public static var CAMERAOK:String = "cameraOk";
		public static var CAMUSED:String = "camused";
        public static var NOCAMERA:String = "nocamera";
		public static var CAMERAUNABLE:String = "cameraunable";
		public static var CAMUNUSED:String = "camunused";
        public static const MAC_CAM:String = "USB Video Class Video";

        public function CameraSnapper()
        {
            return;
        }// end function

        public function getEditSource() : void
        {
            if (this.camera)
            {
                this.bitmapData.draw(this.video, null, null, null, null, true);
            }
            return;
        }// end function

        private function connectCamera() : void
        {
            this.camera.setMode(640, 480, 21);
            this.camera.setQuality(0, 100);
			if(this.camera.muted){
				this.camera.addEventListener(StatusEvent.STATUS,statusHandler);
				this.dispatchEvent(new Event(CAMERAUNABLE));
				Security.showSettings(SecurityPanel.PRIVACY);
			}else{
				//addCamLoader("摄像头视频获取中...");
				_timer = new Timer(100,20);
				if(this.camera.activityLevel == -1){
					cameraActivityHandler(null);
				}else{
					this.camera.addEventListener(ActivityEvent.ACTIVITY,cameraActivityHandler);
				}
			}
            this.video.attachCamera(this.camera);
            return;
        }// end function
		
		private function statusHandler(evt:StatusEvent):void
		{
			if (evt.code == "Camera.Unmuted")
			{
				this.dispatchEvent(new Event(CAMUSED));
				_timer = new Timer(100,20);
				if(this.camera.activityLevel == -1){
					cameraActivityHandler(null);
				}else{
					this.camera.addEventListener(ActivityEvent.ACTIVITY,cameraActivityHandler);
				}
			}
		}

		//摄像头有活动时被触发  
		private function cameraActivityHandler(e:ActivityEvent):void
		{
			//trace("cameraActivityHandler被调用!");
			if (!_videoIsWorked)
			{
				if (_timer != null)
				{
					_timer.addEventListener(TimerEvent.TIMER, checkCamera);
					_timer.addEventListener(TimerEvent.TIMER_COMPLETE, checkCameraComplete);
					_timer.start();
				}
			}
		}
		
		//timer回调函数，用于检测摄像头设备是否正确
		function checkCamera(e:TimerEvent):void
		{
			if (this.camera.currentFPS > 0)
			{
				_timer.stop();
				_videoIsWorked = true;
				this.dispatchEvent(new Event(CAMERAOK));
			}
		}
		
		function checkCameraComplete(e:TimerEvent):void
		{
			this.dispatchEvent(new Event(CAMUNUSED));
			_timer.removeEventListener(TimerEvent.TIMER, checkCamera);
			_timer.removeEventListener(TimerEvent.TIMER_COMPLETE, checkCameraComplete);
			_timer = null;
			return;
		}

        public function init(w:Number,h:Number) : void
        {
            this.os = Capabilities.manufacturer;
            this.video = new Video(w,h);
			this.bitmapData = new BitmapData(this.video.width, this.video.height);
			this.bitmap = new Bitmap(this.bitmapData);
            addChild(this.video);
			addChild(this.bitmap);
            this.initCamera();
            return;
        }// end function

        public function get editData() : BitmapData
        {
            return this.bitmapData;
        }// end function
		
		public function showVideo():void{
			this.video.visible = true;
			this.bitmap.visible = false;
		}
		
		public function showBitmap():void{
			this.video.visible = false;
			this.bitmap.visible = true;
			
			colorTransform = this.bitmap.transform.colorTransform;
			filter = this.bitmap.filters;
			times = 0;
			
			this.addEventListener(Event.ENTER_FRAME,fire);
		}
		
		private function fire(e:Event):void{
			if(times == 0 && colorTransform.blueOffset < 255){
				colorTransform.blueOffset += 255*.4;
				colorTransform.redOffset += 255*.4;
				colorTransform.greenOffset += 255*.4;
			}
			
			if(colorTransform.blueOffset >=255){
				times = 1;
			}
			
			if(times == 1 && colorTransform.blueOffset > 0){
				colorTransform.blueOffset -= 255*.4;
				colorTransform.redOffset -= 255*.4;
				colorTransform.greenOffset -= 255*.4;
			}
			
			if(times == 1 && colorTransform.blueOffset <= 0){
				this.removeEventListener(Event.ENTER_FRAME,fire);
			}
			
			this.bitmap.transform.colorTransform = colorTransform;
			this.bitmap.filters = filter;
		}

        private function initCamera() : void
        {
            var _loc_1:String = null;
            var _loc_2:* = undefined;
            if (this.os == "Adobe Macintosh")
            {
                for (_loc_2 in Camera.names)
                {
                    
                    if (Camera.names[_loc_2] == MAC_CAM)
                    {
                        _loc_1 = _loc_2.toString();
                        break;
                    }
                }
                this.camera = Camera.getCamera(_loc_1);
            }
            else
            {
                this.camera = Camera.getCamera();
            }
            if (this.camera != null && Camera.names.length >= 1)
            {
                  this.connectCamera();
            }
            else
            {
                this.dispatchEvent(new Event(NOCAMERA));
            }
            return;
        }// end function

    }
	
}
