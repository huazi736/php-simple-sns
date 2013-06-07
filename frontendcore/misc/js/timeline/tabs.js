/**
 * Author: wushaojie
 * Date: 12-7-30
 * Time: 上午10:56
 */
/*================== tab 切换 ======================*/
var Tabs = {};
Tabs.record = {
    reload: {},
    tab: {},
    content: {}
};
Tabs.tab = function(tabs, contents, options) {
    var self = this,
        options = $.extend({}, options);
    this.id = options.id;
    this.$container = $("#" + this.id);

    this.$tabs = $(tabs);
    this.$contents = $(contents);
    this.$pointUp = this.$container.find(".pointUp");
    this.$footer = this.$container.find(".mediaFooter");

    this.selected = options.selected;
    this.selectedClass = options.selectedClass;

    this.requires = options.requires;
    this.isFooterHide = options.isFooterHide;
    this.retriverTab = this.$tabs.eq(this.selected);
    this.retriverContent = this.$contents.eq(this.selected).show();
    if(!Tabs.record.reload[this.id]) {
        this.reload();
        Tabs.record.reload[this.id] = true;
    }
    this.$tabs.each(function(i) {
        self.addTab($(this), self.$contents.eq(i), self.requires[i]);
    });
};
Tabs.tab.prototype= {
    reload: function() {
        Post.postBox.handleClick(this.id, this.$contents.eq(this.selected), this.requires[this.selected]);
    },
    addTab: function(tab, content, require) {
        var self = this;
        tab.bind("click", function(event) {
            event.preventDefault();
            var id = self.id;
            self.retriverTab.parent().removeClass(self.selectedClass);
            self.retriverContent.hide();
            self.retriverTab = tab;
            self.retriverContent = content;
            self.retriverTab.parent().addClass(self.selectedClass);
            self.retriverContent.show();
            if(!self.isFooterHide) {
                if(Tabs.record.tab[id])
                    Tabs.record.tab[id].parent().parent().show();
                if(Tabs.record.content[id])
                    Tabs.record.content[id].hide();
                Tabs.record.tab[id] = null;
                Tabs.record.content[id] = null;
                self.$pointUp.animate({"left": 70 * self.$tabs.index(tab) + 18});
                if(!require) {
                    self.$footer.hide();
                } else {
                    self.$footer.show();
                }
            } else {
                Tabs.record.tab[id] = tab;
                Tabs.record.content[id] = content;
                Tabs.record.tab[id].parent().parent().hide();
                self.$footer.show();
            }
            if(require) {
                Post.postBox.handleClick(id, content, require);
            }
        });
    }
};