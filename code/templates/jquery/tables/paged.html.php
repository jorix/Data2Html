<?php die("It's uncooked!"); ?>
<div class="row"
    id="$${id}"
    data-d2h="
        url:'$${url}',
        repeat:'table tbody tr',
        visual: $${visual},
        actions: {
            'edit': function(elem) {
                this.switchTo('form').load({elemKeys:elem});
            },
            'copy': function() {},
            'delete': function() {}
        },
        filter:['#$${id}_filter', {
            actions: {
                'readPage': function() {this.load();}
            }
        }],
        page: ['#$${id}_page', {
            actions: {
                'readPage': function() {this.load();},
                'nextPage': function() {this.load({add:true});}
            }
        }],
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
            <tbody><tr data-d2h-keys="${[keys]}">$${tbody}</tr></tbody>
        </table>
    </div>
</div>
