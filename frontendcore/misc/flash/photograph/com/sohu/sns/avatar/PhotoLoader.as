package com.sohu.sns.avatar
{
    import com.sohu.sns.avatar.*;
    import flash.display.*;
    import flash.events.*;
    import flash.net.*;
	import flash.external.*;
    import flash.system.*;

    public class PhotoLoader extends Sprite implements IEditSource
    {
        private var path:String;
        private var loader:Loader;
        private var bitmapData:BitmapData;
        public static var COMPLETE:String = "complete";

        public function PhotoLoader(param1:String)
        {
            this.path = param1;
            return;
        }// end function

        public function getEditSource() : void
        {
            this.loader = new Loader();
            this.loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onLoadComp);
            this.loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onIOError);
            this.loader.load(new URLRequest(this.path), new LoaderContext(true));
            return;
        }// end function

        private function onIOError(event:IOErrorEvent) : void
        {
			ExternalInterface.call("avatarError", "加载的图片为未知类型，请上传正确的图片文件");
            return;
        }// end function

        public function get editData() : BitmapData
        {
            return this.bitmapData;
        }// end function

        private function onLoadComp(event:Event) : void
        {
            this.bitmapData = new BitmapData(event.target.content.width, event.target.content.height);
            this.bitmapData.draw(event.target.content, null, null, null, null, true);
            this.dispatchEvent(new Event(COMPLETE));
            return;
        }// end function

    }
}
