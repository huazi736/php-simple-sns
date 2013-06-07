/**
 * 发表框表情
 * Date: 12-6-25
 * Time: 上午11:25
 * @example: $(button).face(element);
 * @一个功能多个发表框需要动态指定元素使用的是:$.faceInsert.insert(element);
 */
(function($) {
    $.fn.face = function(insertElement) {
        var isLoaded = false,
            insertElement = insertElement,//要插入到的表情元素
            faceOverlay = $('<div class="face-overlay"></div>'),
            facePreview = $('<div class="face-preview"><div class="faceInner"><div class="faceImg"><img src=""></div><div class="faceName"></div></div></div>');
        $(this).bind("click", function(event) {
            var offset = $(event.target).offset();
            if(!isLoaded) {
                $.ajax({
                    url: mk_url("main/info/createFace", {}),
                    method: "GET",
                    dataType: 'json',
                    success: function(data) {
                        faceOverlay.html(data.data).append(facePreview);
                        $(document.body).append(faceOverlay).bind("click", function() {
                            faceOverlay.hide();
                        });
                        faceOverlay.bind({
                            click: function(event) {
                                var target = $(event.target);
                                if(target.hasClass("face-overlay-close")) {
                                    faceOverlay.hide();
                                }
                                if(target.parent().hasClass("face-bd-box")) {
                                    var dynamicInsert = $.faceInsert.getInsert();
                                    insertElement = dynamicInsert ? dynamicInsert : insertElement;
                                    if(insertElement) {
                                        insertElement.val(insertElement.val() + "[" + target.attr("title") + "]");
                                        insertElement.focus();
                                        insertElement.blur();
                                        facePreview.hide();
                                        faceOverlay.hide();
                                    }
                                }
                                return false;
                            },
                            mouseover: function(event) {
                                var target = $(event.target);
                                if(target.parent().hasClass("face-bd-box")) {
                                    var faceImgWhich = target.attr("alt"),
                                        faceName = target.attr("title"),
                                        facePreviewOffset = target.offset(),
                                        facePreviewLeft = facePreviewOffset.left;
                                    facePreview.show();
                                    if(facePreviewLeft > 583 && !facePreview.hasClass("previewLeft")) {
                                        facePreview.removeClass("previewRight").addClass("previewLeft")
                                    } else if(facePreviewLeft <= 583 && !facePreview.hasClass("previewRight")) {
                                        facePreview.removeClass("previewLeft").addClass("previewRight");
                                    }
                                    facePreview.find(".faceImg img").attr("src", CONFIG['misc_path'] + "/img/system/face/face_type_01/" + faceImgWhich + ".gif" );
                                    facePreview.find(".faceName").text(faceName);
                                }
                            },
                            mouseout: function(event) {
                                var target = $(event.target);
                                if(target.parent().hasClass("face-bd-box")) {
                                    facePreview.hide();
                                }
                            }
                        });
                        isLoaded = true;
                    }
                });
            }
            faceOverlay.css({
                position: 'absolute',
                left: offset.left,
                top: offset.top + 29,
                zIndex: 10
            });
            faceOverlay.show();
            return false;
        });
    };
    $.faceInsert = {
        insert: function(insertElement) {
            this.insertElement = insertElement;
        },
        getInsert: function() {
            return this.insertElement
        }
    };
})(jQuery);