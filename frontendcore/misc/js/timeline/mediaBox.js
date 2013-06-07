/**
 * Author: wushaojie
 * Date: 12-6-20
 * Time: 上午10:51
 * .uiFocusMediaStatus .uiFocusMediaPhoto .uiFocusMediaVideo
 */
(function($) {
    var mediaBox = {
        initialize:function() {
            var self = this;
            this.$mediaBox = $(".mediaBox");
            this.$mediaNav = this.$mediaBox.find(".mediaNav a");
            this.$mediaUlItemNav = this.$mediaBox.find(".mediaItemNav");
            this.$mediaItemNav = this.$mediaUlItemNav.find("a");
            this.$mediaWrap = this.$mediaBox.find(".mediaWrap");
            this.$mediaFooter = this.$mediaBox.find(".mediaFooter");
            this.$pointUp = $(".pointUp");

            this.uiFocusClass = ['uiFocusMediaStatus', 'uiFocusMediaPhoto', 'uiFocusMediaVideo'];
            this.uiFocusChildClass = ['uiFocusChildPhoto', 'uiFocusChildCamera', 'uiFocusChildVideo', 'uiFocusChildUploadVideo'];

            this.retrievalNav = $(this.$mediaNav[0]).parent();
            this.retrievalClass = this.uiFocusClass[0];
            this.retrievalChildClass = null;

            this.$mediaNav.each(function(i) {
                $(this).bind("click", function(e) {
                    e.preventDefault();
                    if (self.retrievalChildClass) {
                        self.$mediaWrap.removeClass(self.retrievalChildClass);
                    }

                    /* mediaNav */
                    var mediaNavParent = $(this).parent();
                    self.retrievalNav.removeClass("mediaCurrent");
                    mediaNavParent.addClass("mediaCurrent");
                    $(this).parent().addClass("mediaCurrent");
                    self.retrievalNav = mediaNavParent;

                    /* mediaWrap */
                    self.$mediaWrap.removeClass(self.retrievalClass);
                    self.$mediaWrap.addClass(self.uiFocusClass[i]);
                    self.retrievalClass = self.uiFocusClass[i];

                    self.$pointUp.animate({"left":(18 + i * 70)});

                    self.mediaNavIndex = i;
                })
            });
            this.$mediaItemNav.each(function(i) {
                $(this).bind("click", function(e) {
                    e.preventDefault();
                    if (self.retrievalChildClass) {
                        self.$mediaWrap.removeClass(self.retrievalChildClass);
                    }
                    self.$mediaWrap.addClass(self.uiFocusChildClass[i]);
                    self.retrievalChildClass = self.uiFocusChildClass[i];
                })
            })
        }

    };

    mediaBox.Tab = {

    };

    mediaBox.initialize();
})(jQuery);