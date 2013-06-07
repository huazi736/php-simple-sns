package com.sohu.sns.avatar
{
    import com.sohu.utils.*;
    import flash.display.*;
    import flash.events.*;
    import flash.geom.*;
    import flash.media.*;
    import flash.text.*;
	import flash.external.*;

    public class EditWindow extends Sprite
    {
        private var maskBlock:Sprite;
        private var orignScale:Number;
        private var orignBoxHeight:Number;
        private var cameroVideo:Video;
        private var wWidth:Number;
        private var snapHeight:Number;
        private var snapWidth:Number;
        private var smallThumbData:BitmapData;
		private var bigThumbData:BitmapData;
        private var thumbText:TextField;
        private var camera:Camera;
		private var thumbWin:PhotoThumb;
        private var orignBoxWidth:Number;
        private var loadMode:Boolean = true;
        private var photoBitMapData:BitmapData;
        private var photoSp:Sprite;
        private var scalN:Number;
        private var pPath:String;
        private var pWidth:Number;
        private var pHeight:Number;
        private var dragRect:Rectangle;
        private var range:Number;
        private var photoMask:Sprite;
        private var alphaBg:Sprite;
        private var scaleP:Number = 1;
        private var boxH:Number;
        private var photoLoader:Loader;
        private var boxX:Number;
        private var boxY:Number;
        private var photoContainer:Sprite;
        private var maskContainer:Sprite;
        private var dragBox:DragBox;
        private var boxW:Number;
        private var wHeight:Number;
        public static var SNAP:String = "snap";

        public function EditWindow(param1:BitmapData = null, param2:Number = 300, param3:Number = 300, param4:Number = 165, param5:Number = 200)
        {
            thumbWin = new PhotoThumb(80, 80);
			thumbText = new TextField();
            this.photoBitMapData = param1;
            this.wWidth = param2;
            this.wHeight = param3;
            this.orignBoxWidth = param4;
            this.orignBoxHeight = param5;
            this.addEventListener(Event.ADDED_TO_STAGE, onStage);
            return;
        }// end function

        private function onResize(event:Event) : void
        {
            this.adjustMask();
            this.movePhoto();
            return;
        }// end function

        private function snapp(event:Event) : void
        {
            var _loc_2d:Number = this.dragBox.x - this.photoSp.x;
            var _loc_3g:Number = this.dragBox.y - this.photoSp.y;
            var _loc_4:Rectangle = new Rectangle(_loc_2d, _loc_3g, this.dragBox.box.width, this.dragBox.box.height);
            var _loc_5:Boolean = true;
            var _loc_6:Matrix = new Matrix();
			var _loc_6z:Matrix = new Matrix();
            var _loc_7b:Number = 48 / this.dragBox.box.width;
            var _loc_8b:Number = 48 / this.dragBox.box.height;
			var _loc_7z:Number = 125 / this.dragBox.box.width;
            var _loc_8z:Number = 125 / this.dragBox.box.height;
            _loc_6.scale(_loc_7b, _loc_8b);
			_loc_6z.scale(_loc_7z, _loc_8z);
            if (_loc_7b >= 0.98 && _loc_7b <= 1)
            {
                _loc_6 = null;
                _loc_5 = false;
            }
            else
            {
                _loc_5 = true;
            }
            var _loc_9:BitmapData = new BitmapData(this.dragBox.box.width, dragBox.box.height);
            var _loc_10:BitmapData = new BitmapData(this.photoSp.width, this.photoSp.height);
            var _loc_11:Matrix = new Matrix();
            _loc_11.scale(this.scaleP, this.scaleP);
            _loc_10.draw(this.photoSp, _loc_11, null, null, null, _loc_5);
            _loc_9.copyPixels(_loc_10, _loc_4, new Point(0, 0));
            this.smallThumbData = new BitmapData(48, 48);
            this.smallThumbData.draw(_loc_9, _loc_6, null, null, null, _loc_5);
			this.bigThumbData = new BitmapData(125, 125);
            this.bigThumbData.draw(_loc_9, _loc_6z, null, null, null, _loc_5);
            _loc_9.dispose();
            _loc_10.dispose();
            this.dispatchEvent(new Event(SNAP));
            return;
        }// end function

        private function init() : void
        {
            this.photoContainer = new Sprite();
            this.photoMask = new Sprite();
            this.photoSp = new Sprite();
            this.photoContainer = BasicGemo.drawRectSprite(this.wWidth, this.wHeight, 16777215, 0);
            this.photoMask = BasicGemo.drawRectSprite(this.wWidth, this.wHeight);
            this.maskContainer = new Sprite();
            this.alphaBg = BasicGemo.drawRectSprite(this.wWidth, this.wHeight, 0, 0.6);
            this.maskBlock = BasicGemo.drawRectSprite(125, 125, 0);
            this.maskContainer.addChild(this.alphaBg);
            this.maskContainer.addChild(this.maskBlock);
            this.photoContainer.addChild(this.photoSp);
            addChild(this.photoContainer);
            addChild(this.photoMask);
            this.photoContainer.mask = this.photoMask;
            this.maskContainer.blendMode = BlendMode.LAYER;
            this.maskBlock.blendMode = BlendMode.ERASE;
            addChild(this.maskContainer);
            this.centerObj(this.photoSp);
            this.centerObj(this.photoMask);
            this.centerObj(this.maskBlock);
            return;
        }// end function

        public function edit(param1:BitmapData, param2:Number = 165, param3:Number = 200) : void
        {
            var _loc_8a:Number = NaN;
            var _loc_4a:Number = param1.width;
            var _loc_5a:Number = param1.height;
            this.photoBitMapData = param1;
            this.pWidth = this.photoBitMapData.width;
            this.pHeight = this.photoBitMapData.height;
            var _loc_6a:int = this.photoSp.numChildren;
            while (_loc_6a > 0)
            {
                
                this.photoSp.removeChildAt(0);
                _loc_6a = _loc_6a - 1;
            }
            var _loc_7a:Bitmap = new Bitmap(this.photoBitMapData, "never", true);
            _loc_7a.smoothing = true;
            this.photoSp.addChild(_loc_7a);
            this.centerObj(this.photoSp);
            this.centerObj(this.photoMask);
            this.centerObj(this.maskBlock);
            this.adjustBoundary(this.photoSp.width, this.photoSp.height);
            addChild(this.dragBox);
            if (this.dragBox.width > _loc_4a || this.dragBox.height > _loc_5a)
            {
                _loc_8a = _loc_4a > _loc_5a ? (_loc_5a) : (_loc_4a);
                this.dragBox.resize(_loc_8a);
                this.orignBoxWidth = this.dragBox.width;
                this.orignBoxHeight = this.dragBox.height;
            }
            this.centerObj(this.dragBox);
            this.adjustMask();
            this.dragBox.addEventListener(DragBox.START_RESIZE, onResizeStart);
            this.dragBox.addEventListener(DragBox.START_MOVE, onMoveStart);
            this.dragBox.addEventListener(DragBox.STOPMOVE, onMoveStop);
            this.snapp(null);
            return;
        }// end function

        public function get scaleRange() : Number
        {
            getRange();
        }// end function
		
		private function getRange():Number{
			var _loc_1:Number = this.pWidth < this.pHeight? (this.pWidth):(this.pHeight);
            var _loc_2:Number = _loc_1/1
            this.range = _loc_2 - 1;
            return this.range;
		}

        public function forceScale(par:Number) : void
        {
			this.range = getRange();
            this.scalN = par;
            var _loc_2a:Number = 1/(this.range + 1);
            this.scaleP = _loc_2a + (1 - _loc_2a) * par;
            if (this.scaleP > 1)
            {
                this.scaleP = 1;
            }
            var _loc_3a:Number = this.scaleP;
            this.photoSp.scaleY = this.scaleP;
            this.photoSp.scaleX = _loc_3a;
            if (this.photoSp.width <= this.wWidth)
            {
                this.photoSp.x = -Math.ceil((this.photoSp.width - this.wWidth)/2);
            }
            if (this.photoSp.height <= this.wHeight)
            {
                this.photoSp.y = -Math.ceil((this.photoSp.height - this.wHeight)/2);
            }
            this.adjustBoundary(this.photoSp.width,this.photoSp.height);
            this.centerObj(this.dragBox);
            this.dragBox.resume(this.orignBoxWidth,this.orignBoxHeight);
            this.adjustMask();
            this.movePhoto();
            return;
        }// end function

        private function doMouseMove(event:MouseEvent) : void
        {
            this.adjustMask();
            this.movePhoto();
            return;
        }// end function

        private function adjustMask() : void
        {
            this.maskBlock.x = this.dragBox.x;
            this.maskBlock.y = this.dragBox.y;
            this.maskBlock.width = this.dragBox.box.width;
            this.maskBlock.height = this.dragBox.box.height;
            return;
        }// end function

        public function get smallThumbBmpData() : BitmapData
        {
            return this.smallThumbData;
        }// end function
		
		public function get bigThumbBmpData() : BitmapData
        {
            return this.bigThumbData;
        }// end function

        public function setSnapp() : void
        {
            this.snapp(null);
            return;
        }// end function

        public function setPhotoScale(param1:Number) : void
        {
			this.range = getRange();
            this.scalN = param1;
            var _loc_2b:Number = 1/(this.range + 1);
            this.scaleP = _loc_2b + (1 - _loc_2b) * param1;
            var _loc_5b:Number = this.scaleP;
            this.photoSp.scaleY = this.scaleP;
            this.photoSp.scaleX = _loc_5b;
            this.movePhoto();
            if (this.photoSp.width <= this.wWidth)
            {
                this.photoSp.x = -Math.ceil((this.photoSp.width - this.wWidth) / 2);
            }
            if (this.photoSp.height <= this.wHeight)
            {
                this.photoSp.y = -Math.ceil((this.photoSp.height - this.wHeight) / 2);
            }
			
            this.adjustBoundary(this.photoSp.width, this.photoSp.height);
			this.dragBox.x = this.dragBox.x > this.photoSp.x ? (this.dragBox.x) : (this.photoSp.x);
            this.dragBox.y = this.dragBox.y > this.photoSp.y ? (this.dragBox.y) : (this.photoSp.y);
            var _loc_3d:Number = this.dragBox.width > this.dragBox.height ? (this.dragBox.width) : (this.dragBox.height);
            var _loc_4b:Number = this.photoSp.width > this.photoSp.height ? (this.photoSp.height) : (this.photoSp.width);
            if (this.dragBox.x + this.dragBox.width > this.photoSp.x + this.photoSp.width)
            {
                if (_loc_3d > _loc_4b)
                {
                    this.dragBox.resize(_loc_4b);
                }
                this.dragBox.x = this.photoSp.x + this.photoSp.width - this.dragBox.width;
            }
            if (this.dragBox.y + this.dragBox.height > this.photoSp.y + this.photoSp.height)
            {
                if (_loc_3d > _loc_4b)
                {
                    this.dragBox.resize(_loc_4b);
                }
                this.dragBox.y = this.photoSp.y + this.photoSp.height - this.dragBox.height;
            }
            this.adjustMask();
            return;
        }// end function

        private function onResizeStart(event:Event) : void
        {
            stage.addEventListener(MouseEvent.MOUSE_MOVE, doMouseMove);
            return;
        }// end function

        public function get scalePercent() : Number
        {
            return this.scalN;
        }// end function

        public function get fullBmpData() : BitmapData
        {
            return this.photoBitMapData;
        }// end function

        private function adjustPostion(param1:Object) : void
        {
            param1.x = -Math.ceil((param1.width - (this.dragBox.x + this.dragBox.width / 2)) / 2);
            param1.y = -Math.ceil((param1.height - (this.dragBox.y + this.dragBox.height / 2)) / 2);
            return;
        }// end function

        private function onStage(event:Event) : void
        {
            this.removeEventListener(Event.ADDED_TO_STAGE, onStage);
            this.init();
            return;
        }// end function

        private function adjustBoundary(param1:Number, param2:Number) : void
        {
            var _loc_3e:Number = 0;
            var _loc_4c:Number = 0;
            var _loc_5c:Number = this.wWidth;
            var _loc_6b:Number = this.wHeight;
            if (param1 >= this.wWidth)
            {
                _loc_3e = 0;
                _loc_5c = this.wWidth;
            }
            else
            {
                _loc_3e = this.photoSp.x;
                _loc_5c = this.photoSp.width;
            }
            if (param2 >= this.wHeight)
            {
                _loc_4c = 0;
                _loc_6b = this.wHeight;
            }
            else
            {
                _loc_4c = this.photoSp.y;
                _loc_6b = this.photoSp.height;
            }
            this.dragRect = new Rectangle(_loc_3e, _loc_4c, _loc_5c, _loc_6b);
            if (!this.dragBox)
            {
                this.dragBox = new DragBox(this.dragRect, this.orignBoxWidth, this.orignBoxHeight);
            }
            else
            {
                this.dragBox.resetBoundary(this.dragRect);
            }
            return;
        }// end function

        private function movePhoto() : void
        {
            this.boxX = this.dragBox.x;
            this.boxY = this.dragBox.y;
            this.boxW = this.dragBox.box.width;
            this.boxH = this.dragBox.box.height;
            var _loc_1a:Number = (boxX - this.dragRect.x) / (this.dragRect.width - boxW);
            var _loc_2c:Number = (boxY - this.dragRect.y) / (this.dragRect.height - boxH);
            _loc_1a = _loc_1a < 0 ? (0) : (_loc_1a);
            _loc_1a = _loc_1a > 1 ? (1) : (_loc_1a);
            _loc_2c = _loc_2c < 0 ? (0) : (_loc_2c);
            _loc_2c = _loc_2c > 1 ? (1) : (_loc_2c);
            var _loc_3f:Number = this.photoSp.width - boxW;
            var _loc_4d:Number = this.photoSp.height - boxH;
            this.photoSp.x = boxX - _loc_3f * _loc_1a;
            this.photoSp.y = boxY - _loc_4d * _loc_2c;
            return;
        }// end function

        private function centerObj(param1:Object) : void
        {
            param1.x = -Math.ceil((param1.width - this.wWidth) / 2);
            param1.y = -Math.ceil((param1.height - this.wHeight) / 2);
            return;
        }// end function

        private function onBoxMove(event:Event) : void
        {
            this.adjustMask();
            this.movePhoto();
            return;
        }// end function

        public function reset(param1:Number = 1) : void
        {
            this.dragBox.resume(this.orignBoxWidth, this.orignBoxHeight);
            this.centerObj(this.dragBox);
            this.setPhotoScale(param1);
            this.adjustMask();
            this.movePhoto();
            this.snapp(null);
            return;
        }// end function

        private function onMoveStart(event:Event) : void
        {
            stage.addEventListener(MouseEvent.MOUSE_MOVE, doMouseMove);
            return;
        }// end function

        private function onMoveStop(event:Event) : void
        {
            stage.removeEventListener(MouseEvent.MOUSE_MOVE, doMouseMove);
            this.adjustMask();
            this.movePhoto();
            this.snapp(null);
            return;
        }// end function

    }
}
