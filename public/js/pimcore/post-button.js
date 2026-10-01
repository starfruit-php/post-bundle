var csrfToken = pimcore.settings.csrfToken;

var myHeaders = {
    'X-Pimcore-CSRF-Token': csrfToken
};

document.addEventListener(
    pimcore.events.postOpenObject,
    (e) => {

        var object = e.detail.object;
        var id = object.id;

        canInit();

        function canInit() {

            fetch(
                '/admin/stf-post/default/check-to-init/' + id,
                {
                    method: 'POST',
                    headers: myHeaders
                }
            )
                .then(response => response.json())
                .then(data => {

                    if (data.canInit) {

                        init(
                            data.maxFileSizeMb || 5,
                            data.allowedExtensions || [
                                'doc',
                                'docx',
                                'tmp'
                            ]
                        );
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        }

        function init(
            maxFileSizeMb,
            allowedExtensions
        ) {

            var buttonText = "Lấy dữ liệu";

            e.detail.object.toolbar.add({

                xtype: "button",

                text: buttonText,

                iconCls: "pimcore_icon_import",

                scale: "medium",

                handler: function () {

                    var popupWidth = 720;

                    var viewSize =
                        Ext.getBody().getViewSize();

                    var popupHeight =
                        Math.min(
                            500,
                            viewSize.height - 100
                        );

                    /**
                     * File input
                     */
                    var fileField =
                        new Ext.form.field.File({

                            fieldLabel: "",

                            name: "file",

                            width: "100%",

                            buttonText:
                                "Chọn file Word",

                            emptyText:
                                "Chưa chọn file...",

                            allowBlank: false,

                            buttonConfig: {
                                iconCls:
                                    "pimcore_icon_import",
                                scale:
                                    "medium"
                            },

                            listeners: {

                                change:
                                    function (field) {

                                        var input =
                                            field
                                                .fileInputEl
                                                .dom;

                                        var file =
                                            input.files[0];

                                        if (!file) {
                                            return;
                                        }

                                        var fileName =
                                            file.name;

                                        var lowerName =
                                            fileName
                                                .toLowerCase();

                                        var extension =
                                            lowerName
                                                .split(".")
                                                .pop();

                                        /**
                                         * Check extension
                                         */
                                        if (
                                            allowedExtensions
                                                .indexOf(
                                                    extension
                                                ) === -1
                                        ) {

                                            pimcore.helpers
                                                .showNotification(
                                                    "Lỗi",
                                                    "Chỉ hỗ trợ file .doc, .docx hoặc .tmp",
                                                    "error"
                                                );

                                            input.value =
                                                "";

                                            field.setRawValue(
                                                ""
                                            );

                                            return;
                                        }

                                        /**
                                         * Check size
                                         */
                                        var maxBytes =
                                            maxFileSizeMb
                                            * 1024
                                            * 1024;

                                        if (
                                            file.size
                                            > maxBytes
                                        ) {

                                            pimcore.helpers
                                                .showNotification(
                                                    "Lỗi",
                                                    "Dung lượng file không được vượt quá "
                                                    + maxFileSizeMb
                                                    + "MB",
                                                    "error"
                                                );

                                            input.value =
                                                "";

                                            field.setRawValue(
                                                ""
                                            );

                                            return;
                                        }

                                        /**
                                         * Show filename
                                         */
                                        field.setRawValue(
                                            fileName
                                        );
                                    }
                            }
                        });

                    /**
                     * Popup
                     */
                    var importWindow =
                        new Ext.Window({

                            title:
                                "Nhập dữ liệu từ file Word",

                            width:
                                popupWidth,

                            height:
                                popupHeight,

                            modal: true,

                            resizable: false,

                            closable: true,

                            layout: "fit",

                            bodyStyle: {
                                background:
                                    "#f5f6f8"
                            },

                            items: [
                                {
                                    xtype: "form",

                                    bodyPadding: 20,

                                    border: false,

                                    autoScroll: true,

                                    bodyStyle: {
                                        background:
                                            "#f5f6f8"
                                    },

                                    defaults: {
                                        labelAlign:
                                            "top"
                                    },

                                    items: [

                                        {
                                            xtype:
                                                "component",

                                            html:
                                                "<div style='margin-bottom:15px;'>"
                                                +

                                                "<div style='font-size:16px;font-weight:600;color:#1f2937;margin-bottom:5px;'>"
                                                +
                                                "📄 Chọn file Word"
                                                +
                                                "</div>"
                                                +

                                                "</div>"
                                        },

                                        {
                                            xtype:
                                                "container",

                                            width:
                                                "100%",

                                            style: {
                                                background:
                                                    "#ffffff",
                                                border:
                                                    "1px solid #d9dee7",
                                                borderRadius:
                                                    "6px",
                                                padding:
                                                    "15px"
                                            },

                                            items: [

                                                {
                                                    xtype:
                                                        "component",

                                                    html:
                                                        "<div style='font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;'>"
                                                        +
                                                        "File Word "
                                                        +
                                                        "<span style='color:#ef4444;'>*</span>"
                                                        +
                                                        "</div>"
                                                },

                                                fileField
                                            ]
                                        },

                                        {
                                            xtype:
                                                "component",

                                            margin:
                                                "12 0 0 0",

                                            html:
                                                "<div style='background:#eef6ff;border:1px solid #cfe3ff;border-radius:6px;padding:10px 12px;'>"
                                                +

                                                "<div style='font-size:13px;color:#1e40af;font-weight:600;margin-bottom:3px;'>"
                                                +
                                                "📎 Định dạng hỗ trợ"
                                                +
                                                "</div>"
                                                +

                                                "<div style='font-size:12px;color:#4b5563;'>"
                                                +
                                                "Hỗ trợ file <b>.doc</b>, <b>.docx</b> và <b>.tmp</b>."
                                                +
                                                "<br>"
                                                +
                                                "Dung lượng tối đa: <b>"
                                                +
                                                maxFileSizeMb
                                                +
                                                "MB</b>."
                                                +
                                                "</div>"
                                                +

                                                "</div>"
                                        },

                                        {
                                            xtype:
                                                "component",

                                            margin:
                                                "10 0 0 0",

                                            html:
                                                "<div style='background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:10px 12px;'>"
                                                +

                                                "<div style='font-size:13px;color:#c2410c;font-weight:600;margin-bottom:3px;'>"
                                                +
                                                "⚠️ Lưu ý"
                                                +
                                                "</div>"
                                                +

                                                "<div style='font-size:12px;color:#6b7280;'>"
                                                +
                                                "Hệ thống chỉ tạo dữ liệu <b>nháp</b>. Admin cần kiểm tra và lưu lại sau khi nhập."
                                                +
                                                "</div>"
                                                +

                                                "</div>"
                                        }
                                    ]
                                }
                            ],

                            buttons: [

                                {
                                    text:
                                        "Lấy dữ liệu",

                                    iconCls:
                                        "pimcore_icon_import",

                                    scale:
                                        "medium",

                                    cls:
                                        "pimcore_button_primary",

                                    handler:
                                        function () {

                                            var window =
                                                this.up(
                                                    "window"
                                                );

                                            var input =
                                                fileField
                                                    .fileInputEl
                                                    .dom;

                                            var file =
                                                input
                                                    .files[0];

                                            /**
                                             * Required
                                             */
                                            if (!file) {

                                                pimcore.helpers
                                                    .showNotification(
                                                        "Lỗi",
                                                        "Vui lòng chọn file Word",
                                                        "error"
                                                    );

                                                return;
                                            }

                                            /**
                                             * Extension
                                             */
                                            var lowerName =
                                                file.name
                                                    .toLowerCase();

                                            var extension =
                                                lowerName
                                                    .split(".")
                                                    .pop();

                                            if (
                                                allowedExtensions
                                                    .indexOf(
                                                        extension
                                                    ) === -1
                                            ) {

                                                pimcore.helpers
                                                    .showNotification(
                                                        "Lỗi",
                                                        "Chỉ hỗ trợ file .doc, .docx hoặc .tmp",
                                                        "error"
                                                    );

                                                return;
                                            }

                                            /**
                                             * Size
                                             */
                                            var maxBytes =
                                                maxFileSizeMb
                                                * 1024
                                                * 1024;

                                            if (
                                                file.size
                                                > maxBytes
                                            ) {

                                                pimcore.helpers
                                                    .showNotification(
                                                        "Lỗi",
                                                        "Dung lượng file không được vượt quá "
                                                        +
                                                        maxFileSizeMb
                                                        +
                                                        "MB",
                                                        "error"
                                                    );

                                                return;
                                            }

                                            /**
                                             * Close popup
                                             */
                                            window.close();

                                            pimcore.helpers
                                                .loadingShow();

                                            /**
                                             * FormData
                                             */
                                            var formData =
                                                new FormData();

                                            formData.append(
                                                "id",
                                                id
                                            );

                                            formData.append(
                                                "file",
                                                file
                                            );

                                            /**
                                             * Request
                                             */
                                            fetch(
                                                "/admin/stf-post/default/start-action",
                                                {
                                                    method:
                                                        "POST",

                                                    headers: {
                                                        "X-Pimcore-CSRF-Token":
                                                            csrfToken
                                                    },

                                                    body:
                                                        formData
                                                }
                                            )
                                                .then(
                                                    async response => {

                                                        var result;

                                                        try {
                                                            result =
                                                                await response.json();
                                                        } catch (
                                                        error
                                                        ) {

                                                            throw new Error(
                                                                "Server trả về dữ liệu không hợp lệ"
                                                            );
                                                        }

                                                        if (
                                                            !response.ok
                                                            ||
                                                            !result.success
                                                        ) {

                                                            throw new Error(
                                                                result.message
                                                                ||
                                                                "Không thể lấy dữ liệu từ file Word"
                                                            );
                                                        }

                                                        return result;
                                                    }
                                                )

                                                .then(
                                                    result => {

                                                        pimcore.helpers
                                                            .loadingHide();

                                                        pimcore.helpers
                                                            .showNotification(
                                                                "Thành công",
                                                                "Đã lấy dữ liệu từ file Word",
                                                                "success"
                                                            );

                                                        object.reload(
                                                            []
                                                        );
                                                    }
                                                )

                                                .catch(
                                                    error => {

                                                        pimcore.helpers
                                                            .loadingHide();

                                                        pimcore.helpers
                                                            .showNotification(
                                                                "Lỗi",
                                                                error.message
                                                                ||
                                                                "Không thể lấy dữ liệu từ file Word",
                                                                "error"
                                                            );

                                                        console.error(
                                                            error
                                                        );
                                                    }
                                                );
                                        }
                                },

                                {
                                    text:
                                        "Đóng",

                                    handler:
                                        function () {
                                            this.up(
                                                "window"
                                            ).close();
                                        }
                                }
                            ]
                        });

                    importWindow.show();
                }
            });
        }
    }
);