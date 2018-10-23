<?php die("It's uncooked!"); ?>
$${include selectivity, d2h_server, d2h_messages}
<span data-d2h-message="#$${id}"></span>
<div class="selectivity-input"
    id="$${id}"
    name="$${name}"
    data-d2h-from-id="$${from-id}"
></div>
<script>
$("#$${id}").selectivity({
//    $${url ? [[data-d2h="{url:'$${url}'}"]]}
    placeholder: "$${description}",
    allowClear: true,
    items: [
        $${repeat [[{id:${[keys]}, text:"${0}"} | , ]]}
    ]
});
</script>
