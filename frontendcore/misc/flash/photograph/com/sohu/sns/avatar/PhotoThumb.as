package com.sohu.sns.avatar
{
    import flash.display.*;
    import flash.text.*;

    public class PhotoThumb extends Sprite
    {
        private var thumbTitle:TextField;
        private var bmp:Bitmap;
        private var bg:Sprite;
        private var tWidth:Number;
        private var tHeight:Number;

        public function PhotoThumb(param1:Number = 80, param2:Number = 80, param3:String = "")
        {
            bmp = new Bitmap();
            this.tWidth = param1;
            this.tHeight = param2;
            this.bg = new Sprite();
            addChild(this.bg);
            if (param3.length > 0)
            {
                this.thumbTitle = new TextField();
				this.thumbTitle.text = param3;
                this.thumbTitle.textColor = 8092539;
                this.thumbTitle.mouseEnabled = false;
                this.thumbTitle.autoSize = TextFieldAutoSize.CENTER;
                this.thumbTitle.x = 8;
                this.thumbTitle.y = 12;
                addChild(this.thumbTitle);
            }
            return;
        }// end function

        public function draw(param1:BitmapData) : void
        {
            this.bmp = new Bitmap(param1, "never", true);
            addChild(this.bmp);
            this.bmp.x = 30;
            this.bmp.y = 35;
            this.reDrawBg();
            return;
        }// end function

        public function display(param1:BitmapData) : void
        {
            this.bmp = new Bitmap(param1, "never", true);
            this.scaleBmp();
            addChild(this.bmp);
            this.bmp.x = 30;
            this.bmp.y = 35;
            this.reDrawBg();
            return;
        }// end function

        private function reDrawBg() : void
        {
            this.bg.graphics.clear();
            this.bg.graphics.lineStyle(1, 15199473);
            this.bg.graphics.beginFill(16777215);
            this.bg.graphics.drawRect(0, 0, this.tWidth + 60, this.tHeight + 60);
            this.bg.graphics.endFill();
            return;
        }// end function

        private function scaleBmp() : void
        {
            var _loc_5:Number = NaN;
            var _loc_6:Number = NaN;
            var _loc_7:Number = NaN;
            var _loc_1:* = this.tWidth;
            var _loc_2:* = this.tHeight;
            var _loc_3:* = this.bmp.width;
            var _loc_4:* = this.bmp.height;
            if (_loc_3 != _loc_1 || _loc_4 != _loc_2)
            {
                _loc_5 = _loc_1 / _loc_3;
                _loc_6 = _loc_2 / _loc_4;
                _loc_7 = _loc_5 < _loc_6 ? (_loc_5) : (_loc_6);
                this.bmp.smoothing = true;
                this.bmp.width = _loc_3 * _loc_7;
                this.bmp.height = _loc_4 * _loc_7;
            }
            this.bmp.x = (-(this.bmp.width - _loc_1)) / 2;
            return;
        }// end function

    }
}
