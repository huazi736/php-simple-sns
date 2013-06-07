package com.sohu.sns.avatar
{
    import flash.display.*;
    import flash.events.*;
    import flash.text.*;

    public class InfoSprite extends Sprite
    {
        private var loadingBg:Sprite;
        private var InfoMc:Class;
        private var loadingContainer:Sprite;
        private var LoadingMc:Class;
        private var loadingMc:Sprite;
        private var infoText:TextField;
        private var infoMc:Sprite;
        private var textColor:uint = 3355443;
        private static var singleton:InfoSprite;

        public function InfoSprite()
        {
            LoadingMc = InfoSprite_LoadingMc;
            InfoMc = InfoSprite_InfoMc;
            this.draw();
            return;
        }// end function

        private function redrawBg() : void
        {
            this.loadingBg.graphics.clear();
            this.loadingBg.graphics.lineStyle(1, 15125593);
            this.loadingBg.graphics.beginFill(16776154, 1);
            this.loadingBg.graphics.drawRect(0, 0, 25 + this.loadingMc.width + this.infoText.width, 32);
            this.loadingBg.graphics.endFill();
            return;
        }// end function

        private function draw() : void
        {
            this.loadingBg = new Sprite();
            this.loadingBg.x = 0;
            this.loadingBg.y = 0;
            this.loadingContainer = new Sprite();
            this.loadingBg.addChild(this.loadingContainer);
            this.loadingContainer.x = 10;
            this.loadingContainer.y = 7;
            this.drawText();
            this.loadingMc = new LoadingMc();
            this.loadingContainer.addChild(this.loadingMc);
            this.infoMc = new InfoMc();
            this.loadingContainer.addChild(this.infoMc);
            this.loadingContainer.addChild(this.infoText);
            this.infoText.x = this.loadingMc.width + 4;
            this.infoText.y = -1;
            this.redrawBg();
            this.addEventListener(Event.ADDED_TO_STAGE, listenResize);
            return;
        }// end function

        public function hide() : void
        {
            if (this.contains(this.loadingBg))
            {
                removeChild(this.loadingBg);
            }
            return;
        }// end function

        private function drawText() : void
        {
            this.infoText = new TextField();
            this.infoText.backgroundColor = 16383196;
            this.infoText.selectable = false;
            this.infoText.textColor = this.textColor;
            this.infoText.autoSize = TextFieldAutoSize.LEFT;
            this.infoText.text = "";
            return;
        }// end function

        private function listenResize(event:Event) : void
        {
            stage.addEventListener(Event.RESIZE, adjust);
            return;
        }// end function

        public function show(param1:String = "", param2:Boolean = true, param3:uint = 3355443) : void
        {
            if (!this.contains(this.loadingBg))
            {
                addChild(this.loadingBg);
                this.adjust(null);
            }
            if (param2)
            {
                this.loadingMc.visible = true;
                this.infoMc.visible = false;
            }
            else
            {
                this.loadingMc.visible = false;
                this.infoMc.visible = true;
            }
            this.infoText.text = param1 + " ";
            this.infoText.textColor = param3;
            this.redrawBg();
            this.adjust(null);
            return;
        }// end function

        public function adjust(event:Event) : void
        {
            this.loadingBg.x = (-(this.loadingBg.width - stage.stageWidth)) / 2;
            this.loadingBg.y = (-(this.loadingBg.height - stage.stageHeight)) / 2;
            if (this.parent)
            {
                this.parent.setChildIndex(this, (this.parent.numChildren - 1));
            }
            return;
        }// end function

        public static function getInstance() : InfoSprite
        {
            if (singleton == null)
            {
                singleton = new InfoSprite;
            }
            return singleton;
        }// end function

    }
}
