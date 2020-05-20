<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.21/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.21/datatables.min.js"></script>
<h2>Webhook management</h2>
<div class="add-webhook-wrapper">
    <div class="container-fluid" style="margin-bottom: 15px;">
        <input type="hidden" name="add_webhook_nonce" value="<?= wp_create_nonce("webhook_nonce") ?>">
        <input type="text" placeholder="Webhook url" id="webhook-url" value="">
        <button class="primary button" id="add-webhook-btn" onclick="add_webhook()">Add</button>
    </div>
    <div id="webhook-table-wrapper" class="container-fluid">
        <table 
            id="webhook-table" 
            class="table table-striped" 
            style="width: 100%"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>API key</th>
                    <th>Webhook URL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<style>
#webhook-table-wrapper #webhook-table tbody tr td input.form-control{
    background: transparent !important;
    width: calc(100% - 30px);
    float: left;
    border: 0 !important;
    outline: none !important;
}
#webhook-table-wrapper #webhook-table tbody tr td input.form-control:focus {
    outline: none !important;
    box-shadow: none !important;
    background: #c4c4c4 !important;
}
#webhook-table-wrapper #webhook-table tbody tr td label {
    opacity: 0;
    margin-top: 5px;
    margin-left: 5px;
}
#webhook-table-wrapper #webhook-table tbody tr td:hover label {
    opacity: 1;
}
</style>
<script>
    let   tbl   = null;
    const nonce = document.querySelector("input[name='add_webhook_nonce']").value.trim();
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
                    action     : "add_webhook",
                    nonce      : nonce,
                },
                    function (res) {
                        if (res.status == 200) {
                            jQuery("input#webhook-url").val('');
                            tbl && tbl.ajax.reload();
                            alert("Webhook url added");
                        } else {
                            alert(res.msg);
                        }
                    },
                    "json"
                );
            }
        }
    }

    function deleteWebhook(id) {
        if (confirm("Are you sure want to remove this webhook url")) {
            jQuery.get(ajaxurl + "?action=delete_webhook&nonce=" + nonce + "&id=" + id,
                function (res) {
                    if (res.status == 200) {
                        tbl && tbl.ajax.reload();
                        alert("Webhook url removed");
                    } else {
                        alert(res.msg);
                    }
                },
                "json"
            );
        }
    }
    function updateWebhook(e, id) {
        let webhook_url = e.value.trim();
        if (!!!webhook_url) {
            alert("Please enter webhook url!");
        } else {
            if(!checkValidUrl(webhook_url)){
                alert("Please enter a valid url");
            } else {
                jQuery.post(ajaxurl, {
                    webhook_url: webhook_url,
                    id         : id,
                    action     : "update_webhook",
                    nonce      : nonce,
                },
                    function (res) {
                        if (res.status == 200) {
                            alert("Webhook url updated");
                        } else {
                            alert(res.msg);
                        }
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
    jQuery(document).ready(function () {
        tbl = jQuery("#webhook-table").DataTable({
            ajax: ajaxurl + "?action=get_webhook&nonce=" + nonce,
            // serverSide: true,
            order: [],
            columns: [
                {data: 'id'},
                {data: 'api_key'},
                {data: 'webhook_url'},
            ],
            columnDefs: [ {
                targets: 3,
                render: function ( data, type, row, meta ) {
                    return `<button class="btn btn-danger" title="Remove ${row.webhook_url}" onclick="deleteWebhook(${row.id})">
                        <svg class="bi bi-trash" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5.5 5.5A.5.5 0 016 6v6a.5.5 0 01-1 0V6a.5.5 0 01.5-.5zm2.5 0a.5.5 0 01.5.5v6a.5.5 0 01-1 0V6a.5.5 0 01.5-.5zm3 .5a.5.5 0 00-1 0v6a.5.5 0 001 0V6z"/>
                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 01-1 1H13v9a2 2 0 01-2 2H5a2 2 0 01-2-2V4h-.5a1 1 0 01-1-1V2a1 1 0 011-1H6a1 1 0 011-1h2a1 1 0 011 1h3.5a1 1 0 011 1v1zM4.118 4L4 4.059V13a1 1 0 001 1h6a1 1 0 001-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    `;
                }
            }, {
                targets: 2,
                render: (data, type, row, meta ) => {
                    return `
                        <input id="webhook-${row.api_key}" value="${row.webhook_url}" onchange="updateWebhook(this, ${row.id})" class="form-control">
                        <label for="webhook-${row.api_key}">
                            <svg class="bi bi-pencil-square" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.502 1.94a.5.5 0 010 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 01.707 0l1.293 1.293zm-1.75 2.456l-2-2L4.939 9.21a.5.5 0 00-.121.196l-.805 2.414a.25.25 0 00.316.316l2.414-.805a.5.5 0 00.196-.12l6.813-6.814z"/>
                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 002.5 15h11a1.5 1.5 0 001.5-1.5v-6a.5.5 0 00-1 0v6a.5.5 0 01-.5.5h-11a.5.5 0 01-.5-.5v-11a.5.5 0 01.5-.5H9a.5.5 0 000-1H2.5A1.5 1.5 0 001 2.5v11z" clip-rule="evenodd"/>
                            </svg>
                        </label>
                    `;
                }
            }]
        });
    });
</script>