package com.sohu.utils
{
    import flash.display.*;

    public class BasicGemo extends Sprite
    {

        public function BasicGemo()
        {
            return;
        }// end function

        public static function drawRectSprite(param1:uint = 50, param2:uint = 50, param3:uint = 16777215, param4:Number = 1) : Sprite
        {
            var _loc_5:Sprite = new Sprite();
            _loc_5.graphics.beginFill(param3, param4);
            _loc_5.graphics.drawRect(0, 0, param1, param2);
            _loc_5.graphics.endFill();
            return _loc_5;
        }// end function

    }
}
