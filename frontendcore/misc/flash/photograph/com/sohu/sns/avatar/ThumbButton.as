package com.sohu.sns.avatar
{
    import flash.display.*;
    import flash.events.*;
    import flash.filters.*;
    import flash.text.*;

    public class ThumbButton extends Sprite
    {
        private var editTextField:TextField;
        private var titleText:String;
        private var buttonWidth:Number;
        private var bitMap:Bitmap;
        private var buttonBg:Sprite;
        private var buttonHeight:Number;
        private var bitMapBg:Sprite;
        public static var CLICKED:String = "clicked";

        public function ThumbButton(param1:Number, param2:Number, param3:String = "")
        {
            this.titleText = param3;
            this.buttonWidth = param1 + 8;
            this.buttonHeight = param2 + 8;
            this.draw();
            this.bitMap = new Bitmap();
            this.bitMap.x = 4;
            this.bitMap.y = 4;
            addChild(this.bitMap);
            return;
        }// end function

        private function onMouseOut(event:MouseEvent) : void
        {
            this.bitMap.filters = null;
            return;
        }// end function

        private function draw() : void
        {
            var _loc_2:TextFormat = null;
            var _loc_3:TextField = null;
            var _loc_4:TextFormat = null;
            this.buttonBg = new Sprite();
            this.buttonBg.graphics.clear();
            this.buttonBg.graphics.lineStyle(1, 16777215);
            this.buttonBg.graphics.beginFill(15658734);
            this.buttonBg.graphics.drawRect(0, 0, this.buttonWidth, this.buttonHeight);
            this.buttonBg.graphics.endFill();
            addChild(this.buttonBg);
            this.bitMapBg = new Sprite();
            this.bitMapBg.graphics.clear();
            this.bitMapBg.graphics.beginFill(12439515);
            this.bitMapBg.graphics.drawRect(0, 0, this.buttonWidth - 4, this.buttonHeight - 4);
            this.bitMapBg.graphics.endFill();
            addChild(this.bitMapBg);
            this.bitMapBg.x = 2;
            this.bitMapBg.y = 2;
            var _loc_1:* = new Sprite();
            _loc_1.graphics.beginFill(15658734);
            _loc_1.graphics.drawRect(0, 0, 11, 11);
            _loc_1.graphics.endFill();
            _loc_1.rotation = -45;
            _loc_1.x = -8;
            _loc_1.y = (this.buttonBg.height - _loc_1.height / 2.8) / 2;
            this.buttonBg.addChild(_loc_1);
            this.addEventListener(MouseEvent.CLICK, onChose);
            this.addEventListener(MouseEvent.MOUSE_OVER, onMouseOver);
            this.addEventListener(MouseEvent.MOUSE_OUT, onMouseOut);
            if (this.titleText.length > 1)
            {
                _loc_2 = new TextFormat("Tahoma", 12, 10066329);
                _loc_2.align = TextFieldAutoSize.LEFT;
                _loc_3 = new TextField();
                _loc_3.height = 20;
                _loc_3.mouseEnabled = false;
                _loc_3.text = this.titleText;
                _loc_3.width = _loc_3.textWidth + 6;
                _loc_3.setTextFormat(_loc_2);
                addChild(_loc_3);
                _loc_3.y = this.buttonHeight + 8;
                _loc_4 = new TextFormat("Tahoma", 12, 13382400);
                this.editTextField = new TextField();
                this.editTextField.height = 20;
                this.editTextField.mouseEnabled = false;
                this.editTextField.setTextFormat(_loc_4);
                addChild(this.editTextField);
                this.editTextField.x = _loc_3.width + 2;
                this.editTextField.y = this.buttonHeight + 8;
            }
            return;
        }// end function

        public function deActive() : void
        {
            this.buttonMode = true;
            this.mouseChildren = true;
            this.mouseEnabled = true;
            this.editTextField.visible = true;
            this.bitMapBg.visible = true;
            this.buttonBg.visible = false;
            this.buttonBg.filters = null;
            return;
        }// end function

        public function active() : void
        {
            this.buttonMode = false;
            this.mouseChildren = false;
            this.mouseEnabled = false;
            this.editTextField.visible = false;
            this.bitMapBg.visible = false;
            this.buttonBg.visible = true;
            this.buttonBg.filters = new Array(new GlowFilter(15504236, 0.6, 6, 6, 2, 6));
            return;
        }// end function

        public function drawData(param1:BitmapData) : void
        {
            this.bitMap = new Bitmap(param1, "never", true);
            this.bitMap.x = 4;
            this.bitMap.y = 4;
            addChild(this.bitMap);
            return;
        }// end function

        private function onMouseOver(event:MouseEvent) : void
        {
            this.bitMap.filters = new Array(new GlowFilter(15504236, 0.6, 6, 6, 2, 6));
            return;
        }// end function

        private function onChose(event:MouseEvent) : void
        {
            this.dispatchEvent(new Event(CLICKED));
            return;
        }// end function

    }
}
