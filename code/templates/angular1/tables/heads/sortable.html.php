<?php die("It's uncooked!"); ?>
<th>$${title} <a
    title="ordre creixent"
    ng-click="sortBy('$${name}', false);"><span
    aria-label="ordre creixent" aria-hidden="true"
    ng-class="{d2h_sortActivated: predicate=='$${name}' && !reverse}"
    class="glyphicon glyphicon-sort-by-alphabet"></span></a><a
    title="ordre invers"
    ng-click="sortBy('$${name}', true);"><span
    aria-label="ordre invers" aria-hidden="true"
    ng-class="{d2h_sortActivated: predicate=='$${name}' && reverse}"
    aria-hidden="true"
    class="glyphicon glyphicon-sort-by-alphabet-alt"></span></a></th>
