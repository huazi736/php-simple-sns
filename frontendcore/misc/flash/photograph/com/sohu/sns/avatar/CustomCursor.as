package com.sohu.sns.avatar
{
    import flash.display.*;
    import flash.events.*;
    import flash.ui.*;

    public class CustomCursor extends Sprite
    {
        private var cursor:Sprite;
        private var targetObj:Object;
        private var moveCursor:Sprite;
        private var resizeCursor:Sprite;
        private var Cursor_CMove:Class;
        private var yOffSet:Number;
        private var xOffSet:Number;
        private var Cursor_CResize:Class;
        private static var singleton:CustomCursor;

        public function CustomCursor()
        {
            Cursor_CMove = CustomCursor_Cursor_CMove;
            Cursor_CResize = CustomCursor_Cursor_CResize;
            cursor = new Sprite();
            this.mouseEnabled = false;
            this.mouseChildren = false;
            this.moveCursor = new Sprite();
            this.moveCursor.addChild(new Cursor_CMove());
            this.resizeCursor = new Sprite();
            this.resizeCursor.addChild(new Cursor_CResize());
            return;
        }// end function

        public function showNormal() : void
        {
            Mouse.show();
            if (this.contains(this.cursor))
            {
                removeChild(this.cursor);
            }
            this.removeListener();
            return;
        }// end function

        public function showMove() : void
        {
            this.targetObj = null;
            this.xOffSet = (-this.moveCursor.width) / 2;
            this.yOffSet = (-this.moveCursor.height) / 2;
            Mouse.hide();
            if (this.contains(this.resizeCursor))
            {
                this.removeChild(this.resizeCursor);
            }
            this.cursor = this.moveCursor;
            this.updateCursor(null);
            addChild(this.cursor);
            stage.addEventListener(MouseEvent.MOUSE_MOVE, updateCursor);
            this.updateCursor(null);
            return;
        }// end function

        public function showResize(param1:Object = null) : void
        {
            this.targetObj = null;
            this.xOffSet = (-this.resizeCursor.width) / 2;
            this.yOffSet = (-this.resizeCursor.height) / 2;
            Mouse.hide();
            if (this.contains(this.moveCursor))
            {
                this.removeChild(this.moveCursor);
            }
            this.cursor = this.resizeCursor;
            addChild(this.cursor);
            if (param1)
            {
                this.targetObj = param1;
                this.cursor.x = this.targetObj.x - this.xOffSet - 2;
                this.cursor.y = this.targetObj.y - this.yOffSet - 2;
                stage.addEventListener(MouseEvent.MOUSE_MOVE, updateCursor);
            }
            else
            {
                this.updateCursor(null);
                this.targetObj = null;
                this.removeListener();
            }
            this.updateCursor(null);
            return;
        }// end function

        private function removeListener() : void
        {
            try
            {
                stage.removeEventListener(MouseEvent.MOUSE_MOVE, updateCursor);
            }
            catch (error:Error)
            {
            }
            return;
        }// end function

        private function updateCursor(event:MouseEvent) : void
        {
            this.cursor.x = this.mouseX + this.xOffSet;
            this.cursor.y = this.mouseY + this.yOffSet;
            if (event)
            {
                event.updateAfterEvent();
            }
            return;
        }// end function

        public static function getInstance() : CustomCursor
        {
            if (singleton == null)
            {
                singleton = new CustomCursor;
            }
            return singleton;
        }// end function

    }
}
