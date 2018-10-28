<?php die("It's uncooked!"); ?>
$${include selectivity-wrapper, d2h_message}
<span data-d2h-message="#$${id}"></span>
<div class="form-control selectivity-input"
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
