package 
{
	import com.adobe.images.*;
	import com.hurlant.util.Base64;
	import com.duankou.CameraSnapper;
	
	import flash.display.*;
	import flash.external.*;
	import flash.media.*;
	import flash.net.*;
	import flash.events.*;
	import flash.text.*; 
	import flash.utils.*;
	import flash.geom.*;
	import flash.system.*;
	import flash.ui.*;

	dynamic public class photograph extends MovieClip
	{
		private var square:Sprite;
		private var label:TextField;
		private var loader:URLLoader;
		
		private var cameraSnapper:CameraSnapper;
		private var editData:BitmapData;
		private var ready_btn:ReadyBtn;
		private var reset_btn:ResetBtn;
		private var ready_mov:ReadyMov;
		private var _flashVars;
		private var _obj;

		public function photograph()
		{
			addFrameScript(0, frame1);
			return;
		}// end function

		public function saveServer(param1:String, param2:String, param3:String = ""):void
		{
			Security.allowDomain("*");
			var _loc_3:URLRequest = null;
			var encoder:JPGEncoder = new JPGEncoder(100);
            var bytes:ByteArray = encoder.encode(this.editData);
			var jpgString:String = Base64.encodeByteArray(bytes);
			var variables:URLVariables = new URLVariables();
			variables["flashUploadUid"] = param2;
			variables["web_id"] = param3;
			variables["type"] = 3;
			variables["img"] = jpgString;
			_loc_3 = new URLRequest(param1);
			_loc_3.method = URLRequestMethod.POST;
			_loc_3.data = variables;
			loader = new URLLoader();
			loader.addEventListener(Event.COMPLETE, completeHandler);
			loader.load(_loc_3);
		}// end function
		
		public function completeHandler(event:Event):void
		{
			var loader:URLLoader = URLLoader(event.target);
			ExternalInterface.call("photo", loader.data);
		}

		public function resetCamera(event:MouseEvent):void
		{
			reset_btn.visible = false;
			this.cameraSnapper.showVideo();
			ExternalInterface.call(""+ _obj +".disable("+ true +")");
			return;
		}// end function

		function frame1()
		{
			//右键菜单设置
			var my_menu:ContextMenu = new ContextMenu();
			my_menu.hideBuiltInItems()
			this.contextMenu = my_menu;
			
			_flashVars = this.loaderInfo.parameters;
			_obj = _flashVars["obj"];
			
			ExternalInterface.addCallback("save", saveServer);
			
			stage.scaleMode = StageScaleMode.NO_SCALE;
			stage.align = StageAlign.TOP_LEFT;
			
			label=addLabel("");
			
			this.cameraSnapper = new CameraSnapper();
			this.cameraSnapper.addEventListener(CameraSnapper.NOCAMERA, noCamera);
			this.cameraSnapper.addEventListener(CameraSnapper.CAMERAUNABLE, cameraUnable);
			this.cameraSnapper.addEventListener(CameraSnapper.CAMUNUSED, camUnused);
			this.cameraSnapper.addEventListener(CameraSnapper.CAMUSED, camused);
			this.cameraSnapper.addEventListener(CameraSnapper.CAMERAOK, cameraOk);
			this.cameraSnapper.init(stage.stageWidth,stage.stageHeight);
		}// end function
		
		private function noCamera(e:Event):void{
			label.text = "没有检测到摄像头";
		}
		
		private function cameraUnable(e:Event):void{
			label.text="您不允许使用摄像头...";
			label.x = stage.stageWidth*0.5 - label.width*0.5;
			label.y = (stage.stageHeight-50 - label.height)*0.5;
		}
		
		private function camUnused(e:Event):void{
			label.text="设备被占用...";
			label.x = stage.stageWidth*0.5 - label.width*0.5;
			label.y = (stage.stageHeight-50 - label.height)*0.5;
		}
		
		private function camused(e:Event):void{
			label.text="摄像头检测中...";
			label.x = stage.stageWidth*0.5 - label.width*0.5;
			label.y = (stage.stageHeight-50 - label.height)*0.5;
		}
		
		private function cameraOk(e:Event):void{
			addChild(this.cameraSnapper);
			this.cameraSnapper.showVideo();
				
			square = new Sprite();
			square.graphics.beginFill(000, 0.3);
			square.graphics.drawRect(0, stage.stageHeight-50, stage.stageWidth, 50);
			square.graphics.endFill();
			addChild(square);
				
			ready_mov = new ReadyMov();
			ready_btn = new ReadyBtn();
			reset_btn = new ResetBtn();
			ready_mov.x = stage.stageWidth*0.5 - 10;
			ready_mov.y = stage.stageHeight - 50 + ready_mov.height*.5;
			ready_btn.x = stage.stageWidth*0.5;
			ready_btn.y = stage.stageHeight - 25;
			reset_btn.x = stage.stageWidth - reset_btn.width - 10;
			reset_btn.y = reset_btn.height*0.5 + 10;

			addChild(ready_mov);
			addChild(ready_btn);
			addChild(reset_btn);
				
			ready_mov.visible = false;
			ready_btn.addEventListener(MouseEvent.CLICK, ready);
			reset_btn.addEventListener(MouseEvent.CLICK, resetCamera);
		}

		private function ready(event:MouseEvent):void
		{
			reset_btn.visible = false;
			ready_btn.visible = false;
			square.visible = false;
			ready_mov.visible = true;
			ready_mov.gotoAndPlay(1);
			var minuteTimer:Timer = new Timer(3000,1);
			minuteTimer.addEventListener(TimerEvent.TIMER_COMPLETE, captureImage);
			minuteTimer.start();
			ExternalInterface.call(""+ _obj +".disable("+ true +")");
			
			this.cameraSnapper.showVideo();
		}

		public function captureImage(event:TimerEvent):void
		{
			ready_mov.visible = false;
			ready_mov.stop();
			ready_btn.visible = true;
			reset_btn.visible = true;
			square.visible = true;
			ExternalInterface.call(""+ _obj +".disable("+ false +")");
			
			this.cameraSnapper.getEditSource();
			this.editData = this.cameraSnapper.editData;
			
			this.cameraSnapper.showBitmap();
		}// end function
		
		private function addLabel(text:String):TextField{
            var label:TextField=new TextField();
            label.autoSize=TextFieldAutoSize.LEFT;
            label.text=text;
            addChild(label);
            return label;
        } 

	}
}