/**
 * Created with IntelliJ IDEA.
 * User: wushaojie
 * Date: 12-8-9
 * Time: 上午11:39
 * To change this template use File | Settings | File Templates.
 */
var D = {};
D.flash = {
    options: {
        width: 0,
        height: 0,
        properties: {
        },
        params: {
            allowscriptaccess: "always"
        },
        flashvars: {}
    },
    init: function(url, options) {
        var url = url,
            options = $.extend(true, this.options, options),
            inject = options.inject,
            properties = options.properties,
            params = options.params,
            flashvars = options.flashvars;
        properties.width = options.width;
        properties.height = options.height;
        params.flashvars = $.param(flashvars);

        if($.browser.msie) {
            properties.classid = "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000";
            params.movie = url;
        } else {
            properties.data = url;
        }
        var html = '<object type="application/x-shockwave-flash"';
        for(var property in properties) html += " " + property + '="' + properties[property] + '"';
        html += ">";
        for(var name in params) html += '<param name="' + name + '" value="' + params[name] + '" />';
        html += "</object>";
        inject.append(html);
    }
};