<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.21/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.21/datatables.min.js"></script>
<h2>Webhook management</h2>
<div class="add-webhook-wrapper">
    <input type="hidden" name="add_webhook_nonce" value="<?= wp_create_nonce("webhook_nonce") ?>">
    <input type="text" placeholder="Webhook url" id="webhook-url" value="https://google.com.vn">
    <button class="primary button" id="add-webhook-btn" onclick="add_webhook()">Add</button>
</div>
<script>
    function add_webhook() {
        let webhook_url = jQuery("#webhook-url").val().trim();
        if (!!!webhook_url) {
            alert("Please enter webhook url!");
        } else {
            if(!checkValidUrl(webhook_url)){
                alert("Please enter a valid url");
            } else {
                jQuery.post(ajaxurl, {
                    webhook_url: webhook_url,
                    action: "add_webhook",
                    nonce: jQuery("input[name='add_webhook_nonce']").val().trim(),
                },
                    function (data) {
                        console.log(data);
                    },
                    "json"
                );
            }
        }
    }

    function checkValidUrl(url) {
        let url_pattern = new RegExp("^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$");
        return !!url_pattern.test(url);
    }
    (() => {
        let nonce = jQuery("input[name='add_webhook_nonce']").val().trim();
        jQuery.get(ajaxurl + "?action=get_webhook&nonce=" + nonce,
            function (data) {
                console.log(data);
            },
            "json"
        );
    })();
</script>