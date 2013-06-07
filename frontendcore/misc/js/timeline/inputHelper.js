/* inputHelper */
var inputHelper = function(inputObj, options) {
    var options = jQuery.extend({}, inputHelper.defaults, options),
        useMethodName = options.useMethodName;
    this.inputObj = inputObj;
    if(options.textTipObj) {
        this.unique = options.unique;
        this.textTipObj = options.textTipObj;
    }
    this.defaultValue = options.defaultValue || this.textTipObj.text();
    this[useMethodName](this.defaultValue);
};
inputHelper.unique = 0;
inputHelper.defaults = {
    defaultValue: undefined,
    textTipObj: null,
    useMethodName: 'setDefaultValue'
};
inputHelper.prototype = {
    createUnique: function(id) {
        this.textTipObj.attr("for", id);
        this.inputObj.attr("id", id);
    },
    setDefaultValue: function(value) {
        var inputObj = this.inputObj,
            oThis = this;
        if(value == "") return this;
        this.defaultValue = value;
        inputObj.val(oThis.defaultValue);
        inputObj.focus(function() {
            if($(this).val() == oThis.defaultValue) {
                $(this).val("");
            }
        }).blur(function() {
                if($(this).val() == "") {
                    $(this).val(oThis.defaultValue);
                }
                oThis.validateValue();
            });
        return this;
    },
    setDefaultValue1: function(value) {
        this.createUnique(this.inputObj.attr("id") + "_" + inputHelper.unique++);
        var inputObj = this.inputObj,
            tipTextObj = this.textTipObj,
            oThis = this;
        !value ? value = tipTextObj.text() : tipTextObj.text(value);
        inputObj.focus(function() {
            if(tipTextObj.text() == oThis.defaultValue && inputObj.val() == "") {
                tipTextObj.text("");
            }
        }).blur(function() {
                if(tipTextObj.text() == "" && inputObj.val() == "") {
                    tipTextObj.text(oThis.defaultValue);
                }
                oThis.validateValue()
            });
        return this;
    },
    getValue: function() {
        var value = this.inputObj.val();
        return (value != "") && (value != this.defaultValue) ? value : "";
    },
    validateValue: function() {
        var value = this.getValue();
        if(value != "" && /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig.test(value)) {
            return true;
        }
        return false;
    }
};