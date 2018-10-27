<?php die("It's uncooked!"); ?>
$${include d2h_server, d2h_message}
<span data-d2h-message="#$${id}"></span>
<div class="selectivity-input"
    id="$${id}"
    name="$${name}"
    data-d2h-from-id="$${from-id}"
    $${url ? [[data-d2h="{url:'$${url}'}"]]}
></div>
<script>
$($${id}).selectivity({
    placeholder: $${description},
    allowClear: true,
    items: [
        $${repeat [[{id:${[keys]}, text:${0}} | , ]]}
    ]
});
</script>
