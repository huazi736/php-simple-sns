package com.sohu.sns.avatar
{
    import com.adobe.images.*;
    import com.adobe.serialization.json.*;
    import flash.display.*;
    import flash.events.*;
    import flash.net.*;
    import flash.utils.*;
	import flash.external.*;

    public class BinaryTransfer extends EventDispatcher
    {
        private var pId:String = "";
        private var postUrl:String;
        private var msgStr:String = "default";
        private var from:String;
        private var photoServer:String;
        private var type:String;
        public static var ERROR:String = "error";
        public static var COMPLETE:String = "complete";

        public function BinaryTransfer(param1:String, param2:String, param3:String = "", param4:String = "", param5:String = "", param6:String = "")
        {
            this.postUrl = param1;
            this.type = param2;
            this.photoServer = param3;
            this.from = param4;
            this.postUrl = this.postUrl + ("?type=" + this.type);
            this.postUrl = this.postUrl + ("&photoServer=" + this.photoServer);
            this.postUrl = this.postUrl + ("&from=" + this.from);
            this.postUrl = this.postUrl + ("&photoId=" + param5);
			this.postUrl = this.postUrl + ("&web_id=" + param6);
            return;
        }// end function

        public function get msg() : String
        {
            return this.msgStr;
        }// end function

        public function get pServer() : String
        {
            return this.photoServer;
        }// end function

        private function onIOError(event:IOErrorEvent) : void
        {
            this.msgStr = "io error";
            this.dispatchEvent(new Event(ERROR));
            return;
        }// end function

        private function onSecurityError(event:SecurityErrorEvent) : void
        {
            this.msgStr = "securityError:" + event.toString();
            this.dispatchEvent(new Event(ERROR));
            return;
        }// end function

        public function get photoId() : String
        {
            return this.pId;
        }// end function

        private function onComplete(event:Event) : void
        {
            var arr:Object;
            var event:* = event;
            var loader:URLLoader = URLLoader(event.target);
            loader.dataFormat = URLLoaderDataFormat.VARIABLES;
            try
            {
                arr = JSON.decode(loader.data);
                if (arr.status == 1)
                {
                    if (arr.data == null)
                    {
                        this.msgStr = "no response";
                        this.dispatchEvent(new Event(ERROR));
                    }
                    else
                    {
                        this.msgStr = arr.statusText;
                        this.photoServer = arr.data.urls[0];
                        if (arr.data.photoId)
                        {
                            this.pId = arr.data.photoId;
                        }
                        else
                        {
                            this.pId = "";
                        }
                        this.dispatchEvent(new Event(COMPLETE));
                    }
                }
                else
                {
                    this.msgStr = arr.statusText;
                    this.dispatchEvent(new Event(ERROR));
                }
            }
            catch (e:Error)
            {
                this.msgStr = "json format error";
                this.dispatchEvent(new Event(ERROR));
            }
            return;
        }// end function

        public function transferData(param1:BitmapData) : void
        {
            var _loc_2:* = new JPGEncoder(100);
            var _loc_3:* = _loc_2.encode(param1);
            var _loc_4:* = new URLRequest(this.postUrl);
            _loc_4.data = _loc_3;
            _loc_4.method = URLRequestMethod.POST;
            _loc_4.contentType = "application/octet-stream";
            var _loc_5:* = new URLLoader();
            _loc_5.load(_loc_4);
            _loc_5.addEventListener(Event.COMPLETE, onComplete);
            _loc_5.addEventListener(IOErrorEvent.IO_ERROR, onIOError);
            _loc_5.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onSecurityError);
            return;
        }// end function

    }
}
