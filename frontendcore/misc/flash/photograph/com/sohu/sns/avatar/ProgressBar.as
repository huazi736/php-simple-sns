package com.sohu.sns.avatar
{
    import flash.display.*;
    import flash.events.*;
	import flash.external.*;

    public class ProgressBar extends Sprite
    {
        private var _slideButton:Sprite;
        private var progrs:Number;
        private var _progressMid:Sprite;
        private var leftIcon:Sprite;
        private var btnOffSet:Number;
        private var _maskSprite:Sprite;
        private var _progressBg:Sprite;
        private var barWidth:Number;
        private var rightIcon:Sprite;
        private var _rate:Number;
        public static var SEEK_BEGIN:String = "seekBegin";
        public static var SEEKING:String = "seeking";
        public static var BAR_SEEK:String = "barSeek";
        public static var SEEK_END:String = "seekEnd";

        public function ProgressBar(param1:Sprite, param2:Sprite, param3:Sprite, param4:Number = 4, param5:Sprite = null, param6:Sprite = null)
        {
            _slideButton = new Sprite();
            _maskSprite = new Sprite();
            _progressMid = new Sprite();
            _progressBg = new Sprite();
            leftIcon = new Sprite();
            rightIcon = new Sprite();
            this._slideButton = param1;
            this._progressMid = param2;
            this._progressBg = param3;
            this.leftIcon = param5;
            this.rightIcon = param6;
            this.drawMask();
            this.drawIcon();
            addChild(this._maskSprite);
            addChild(this._progressBg);
            addChild(this._progressMid);
            addChild(this._slideButton);
            this._maskSprite.y = -3;
            this.btnOffSet = this._slideButton.width / 2;
            this.barWidth = this._progressMid.width;
            this._progressMid.mask = this._maskSprite;
            this._slideButton.y = -param4;
            this._slideButton.alpha = 0.9;
            this._progressMid.buttonMode = true;
			this.initListeners();
            this.refresh();
            this.setProgres(1);
            this.setRate(0);
            return;
        }// end function

        public function get rate() : Number
        {
            return this._rate;
        }// end function

        public function enable() : void
        {
            this._slideButton.visible = true;
            this._progressMid.visible = true;
            this.mouseChildren = true;
            return;
        }// end function

        public function setProgres(param1:Number) : void
        {
            this.progrs = param1;
            this._maskSprite.width = this.barWidth * this.progrs;
            return;
        }// end function

        public function initListeners() : void
        {
            this._slideButton.addEventListener(MouseEvent.MOUSE_DOWN, dragSlide);
            this._progressMid.addEventListener(MouseEvent.MOUSE_DOWN, barSeek);
            this.leftIcon.addEventListener(MouseEvent.CLICK, reduceRate);
            this.rightIcon.addEventListener(MouseEvent.CLICK, addRate);
            return;
        }// end function

        private function drawMask() : void
        {
            this._maskSprite.graphics.beginFill(0);
            this._maskSprite.graphics.drawRect(0, 0, 1, this._progressBg.height + 6);
            this._maskSprite.graphics.endFill();
            return;
        }// end function

        private function drawIcon() : void
        {
            if (this.leftIcon)
            {
                this.leftIcon.x = -this.leftIcon.width - 12;
                this.rightIcon.x = this._progressBg.width + 12;
                this.leftIcon.useHandCursor = true;
                this.rightIcon.useHandCursor = true;
                this.leftIcon.buttonMode = true;
                this.rightIcon.buttonMode = true;
                addChild(this.leftIcon);
                addChild(this.rightIcon);
            }
            return;
        }// end function

        private function addRate(event:MouseEvent) : void
        {
            this._rate = this._rate + 0.05;
            this.setRate(this._rate);
            this.dispatchEvent(new Event(BAR_SEEK));
            return;
        }// end function

        private function dragSlide(event:MouseEvent) : void
        {
            stage.addEventListener(MouseEvent.MOUSE_MOVE, dragMove);
            stage.addEventListener(MouseEvent.MOUSE_UP, dragOver);
            return;
        }// end function

        private function dragMove(event:MouseEvent) : void
        {
			if(mouseX < 0 || mouseX > stage.stageWidth || mouseY < 0 || mouseY > stage.stageHeight ){
            	dragOver(null)
			}else{
				this._slideButton.x = mouseX - this.btnOffSet;
            	if (this._slideButton.x < -this.btnOffSet)
            	{
                	this._slideButton.x = -this.btnOffSet;
            	}
            	if (this._slideButton.x > this._maskSprite.width - this.btnOffSet)
            	{
                	this._slideButton.x = this._maskSprite.width - this.btnOffSet;
            	}
            	this._rate = (this._slideButton.x + this.btnOffSet) / this.barWidth;
            	this.dispatchEvent(new Event(SEEKING));
				this.dispatchEvent(new Event(SEEK_END));
				event.updateAfterEvent();
            	return;
			}
        }// end function

        public function disable() : void
        {
            this._slideButton.visible = false;
            this._progressMid.visible = false;
            this.mouseChildren = false;
            return;
        }// end function

        public function setRate(param1:Number) : void
        {
            if (param1 > 1)
            {
                param1 = 1;
            }
            else if (param1 < 0)
            {
                param1 = 0;
            }
            this._rate = param1;
            var _loc_2:* = this.barWidth * this._rate - this.btnOffSet;
            var _loc_3:* = Math.abs(this._slideButton.x - _loc_2);
            this._slideButton.x = _loc_2;
            return;
        }// end function

        private function barSeek(event:MouseEvent) : void
        {
            this._slideButton.x = mouseX - this.btnOffSet;
            this._rate = mouseX / this.barWidth;
            this.dispatchEvent(new Event(BAR_SEEK));
			this.dispatchEvent(new Event(SEEK_END));
            event.updateAfterEvent();
            return;
        }// end function

        private function reduceRate(event:MouseEvent) : void
        {
            this._rate = this._rate - 0.05;
            this.setRate(this._rate);
            this.dispatchEvent(new Event(BAR_SEEK));
			this.dispatchEvent(new Event(SEEK_END));
            return;
        }// end function

        public function resize(param1:Number) : void
        {
            this._progressBg.width = param1;
            this._progressMid.width = param1;
            this.barWidth = this._progressMid.width;
            this.setProgres(this.progrs);
            this.setRate(this._rate);
            return;
        }// end function

        private function dragOver(event:MouseEvent) : void
        {
            stage.removeEventListener(MouseEvent.MOUSE_MOVE, dragMove);
            return;
        }// end function

        public function refresh() : void
        {
            this._maskSprite.width = 0;
            this._slideButton.x = -this.btnOffSet;
            return;
        }// end function

    }
}
