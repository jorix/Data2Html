<?php die("It's uncooked!"); ?>
<div class="row"
    id="$${id}"
    data-d2h="
        url:'$${url}',
        repeat:  'table tbody tr',
        filter:'#$${id}_filter',
        page:  '#$${id}_page',
        sort:  '#$${id}_sort'"
>
    $${filter}
    <div class="col-md-12">
        <h3>$${title}</h3>
        <table class="table table-striped table-bordered">
            <thead><tr>$${thead}</tr></thead>
            <tfoot><tr>
                <td colspan="$${colCount}">
                    $${page}
                </td>
            </tr></tfoot>
            <tbody><tr>$${tbody}</tr></tbody>
        </table>
    </div>
</div>
