pimcore.registerNS("pimcore.plugin.StarfruitPostBundle");

pimcore.plugin.StarfruitPostBundle = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        // alert("StarfruitPostBundle ready!");
    }
});

var StarfruitPostBundlePlugin = new pimcore.plugin.StarfruitPostBundle();
