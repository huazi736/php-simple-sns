package com.sohu.sns.avatar
{
    import flash.display.*;
    import flash.events.*;
    import flash.geom.*;

    public class DragBox extends Sprite
    {
        private var gripper:Sprite;
        private var selfHeight:Number;
        private var initWidth:Number;
        private var isDragging:Boolean = false;
        private var right:Number;
        private var bottom:Number;
        private var isResizing:Boolean = false;
        private var bg:Sprite;
        private var offX:Number = 0;
        private var offY:Number = 0;
        private var initHeight:Number;
        private var selfWidth:Number;
        private var Grapper:Class;
        private var originalScale:Number;
        private var dragBoundary:Rectangle;
        public static var MOVE:String = "moving";
        public static var START_RESIZE:String = "startResize";
        public static var STOPMOVE:String = "stop";
        public static var RESIZE:String = "resizing";
        public static var START_MOVE:String = "startMove";

        public function DragBox(param1:Rectangle, param2:Number = 100, param3:Number = 100)
        {
            Grapper = DragBox_Grapper;
            bg = new Sprite();
            this.dragBoundary = param1;
            this.right = right;
            this.bottom = bottom;
            this.selfWidth = param2;
            this.selfHeight = param3;
            this.originalScale = param2 / param3;
            this.initWidth = param2;
            this.initHeight = param3;
            addChild(this.bg);
            this.addEventListener(Event.ADDED_TO_STAGE, onStage);
            return;
        }// end function

        private function onBgMouseOut(event:MouseEvent) : void
        {
            if (this.isDragging || this.isResizing)
            {
                return;
            }
            CustomCursor.getInstance().showNormal();
            return;
        }// end function

        public function initListeners() : void
        {
            this.bg.addEventListener(MouseEvent.MOUSE_OVER, onBgMouseOver);
            this.bg.addEventListener(MouseEvent.MOUSE_OUT, onBgMouseOut);
            this.addEventListener(MouseEvent.MOUSE_DOWN, onBgMouseDown);
            this.addEventListener(MouseEvent.MOUSE_UP, onBgMouseUp);
            this.gripper.addEventListener(MouseEvent.MOUSE_OVER, onRectMouseOver);
            this.gripper.addEventListener(MouseEvent.MOUSE_OUT, onRectMouseOut);
            this.gripper.addEventListener(MouseEvent.MOUSE_DOWN, onRectDown);
            this.gripper.addEventListener(MouseEvent.MOUSE_UP, onRectUp);
            return;
        }// end function

        public function resetBoundary(param1:Rectangle) : void
        {
            this.dragBoundary = param1;
            return;
        }// end function

        public function get miniLength() : Number
        {
            return this.initWidth > this.initHeight ? (initHeight) : (this.initWidth);
        }// end function

        private function onRectDown(event:MouseEvent) : void
        {
            this.isResizing = true;
            CustomCursor.getInstance().showResize(DisplayObject(event.target));
            this.dispatchEvent(new Event(START_RESIZE));
            this.offX = event.target.width - event.target.mouseX;
            this.offY = event.target.height - event.target.mouseY;
            event.stopPropagation();
            stage.addEventListener(MouseEvent.MOUSE_MOVE, resizing);
            stage.addEventListener(MouseEvent.MOUSE_UP, onRectUp);
            return;
        }// end function

        private function drawGripper() : void
        {
            this.gripper = new Grapper();
            return;
        }// end function

        public function get minHeight() : Number
        {
            return this.initHeight;
        }// end function

        public function resume(param1:Number, param2:Number) : void
        {
            this.resizeBox(param1, param2);
            return;
        }// end function

        public function get miniWidth() : Number
        {
            return this.initWidth;
        }// end function

        private function moveSelf() : void
        {
            return;
        }// end function

        private function onBgMouseDown(event:MouseEvent) : void
        {
            this.isDragging = true;
            this.dispatchEvent(new Event(START_MOVE));
            CustomCursor.getInstance().showMove();
            var _loc_2:* = new Rectangle(this.dragBoundary.x, this.dragBoundary.y, this.dragBoundary.width - this.width, this.dragBoundary.height - this.height);
            this.startDrag(false, _loc_2);
            stage.addEventListener(MouseEvent.MOUSE_UP, dragStop);
            stage.addEventListener(MouseEvent.MOUSE_MOVE, onMove);
            return;
        }// end function

        public function get box() : Sprite
        {
            return this.bg;
        }// end function

        public function resize(param1:Number) : void
        {
            if (this.initWidth > this.initHeight)
            {
                this.resizeBox(param1, param1 / this.originalScale);
            }
            else
            {
                this.resizeBox(param1 * this.originalScale, param1);
            }
            return;
        }// end function

        private function onMove(event:Event) : void
        {
            return;
        }// end function

        private function dragStop(event:MouseEvent) : void
        {
            if (!this.bg.hitTestPoint(event.stageX, event.stageY))
            {
                CustomCursor.getInstance().showNormal();
            }
            this.stopDrag();
            stage.removeEventListener(MouseEvent.MOUSE_UP, dragStop);
            stage.removeEventListener(MouseEvent.MOUSE_MOVE, onMove);
            this.dispatchEvent(new Event(STOPMOVE));
            this.isDragging = false;
            return;
        }// end function

        private function onRectUp(event:MouseEvent) : void
        {
            CustomCursor.getInstance().showNormal();
            stage.removeEventListener(MouseEvent.MOUSE_MOVE, resizing);
            stage.removeEventListener(MouseEvent.MOUSE_UP, onRectUp);
            this.dispatchEvent(new Event(STOPMOVE));
            this.isResizing = false;
            return;
        }// end function

        private function onStage(event:Event) : void
        {
            this.removeEventListener(Event.ADDED_TO_STAGE, onStage);
            this.drawGripper();
            this.drawBox();
            this.gripper.x = this.width - this.gripper.width - 1;
            this.gripper.y = this.height - this.gripper.height - 1;
            this.addChild(this.gripper);
			this.initListeners();
            stage.addChild(CustomCursor.getInstance());
            return;
        }// end function

        private function drawBox() : void
        {
            this.bg.graphics.clear();
            this.bg.graphics.beginFill(0, 0);
            this.bg.graphics.lineStyle(1, 16777215);
            this.bg.graphics.drawRect(0, 0, this.selfWidth, this.selfHeight);
            this.bg.graphics.endFill();
            return;
        }// end function

        public function get maxLength() : Number
        {
            return this.initWidth < this.initHeight ? (initHeight) : (this.initWidth);
        }// end function

        private function onRectMouseOut(event:MouseEvent) : void
        {
            if (this.isResizing || this.isDragging)
            {
                return;
            }
            CustomCursor.getInstance().showNormal();
            return;
        }// end function

        private function resizing(event:MouseEvent) : void
        {
            if (event.stageX > this.x && event.stageY > this.y)
            {
                this.resizeBox(this.mouseX, this.mouseY, this.offX, this.offY);
            }
            return;
        }// end function

        private function onBgMouseOver(event:MouseEvent) : void
        {
            if (this.isResizing)
            {
                return;
            }
            CustomCursor.getInstance().showMove();
            return;
        }// end function

        private function resizeBox(param1:Number, param2:Number, param3:Number = 0, param4:Number = 0) : void
        {
            var _loc_5:* = param3;
            var _loc_6:* = param4;
            var _loc_7:* = param1;
            var _loc_8:* = param2;
            var _loc_9:* = this.dragBoundary.width + this.dragBoundary.x;
            var _loc_10:* = this.dragBoundary.height + this.dragBoundary.y;
            this.selfWidth = _loc_7 + _loc_5;
            this.selfHeight = _loc_8 + _loc_6;
            if (this.selfWidth <= _loc_5)
            {
                this.selfWidth = _loc_5;
            }
            if (this.selfHeight <= _loc_6)
            {
                this.selfHeight = _loc_5;
            }
            if (this.selfWidth > _loc_9 - this.x)
            {
                this.selfWidth = _loc_9 - this.x;
            }
            if (this.selfHeight > _loc_10 - this.y)
            {
                this.selfHeight = _loc_10 - this.y;
            }
            if (this.selfWidth < this.selfHeight * this.originalScale)
            {
                this.selfHeight = this.selfWidth / this.originalScale;
            }
            else
            {
                this.selfWidth = this.selfHeight * this.originalScale;
            }
            this.drawBox();
            this.gripper.x = this.selfWidth - this.gripper.width;
            this.gripper.y = this.selfHeight - this.gripper.height;
            return;
        }// end function

        private function onBgMouseUp(event:MouseEvent) : void
        {
            return;
        }// end function

        private function onRectMouseOver(event:MouseEvent) : void
        {
            if (this.isDragging)
            {
                return;
            }
            CustomCursor.getInstance().showResize(DisplayObject(event.target));
            return;
        }// end function

    }
}
