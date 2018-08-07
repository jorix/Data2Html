<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>date</title>
</head>
<div class="container">
<?php
$idCount = 0;

echo 'PHP ' . phpversion() . '<br>';

test(new \DateTime());
echo '<br>';

test(new \DateTime('2014-01-30T02:59:59.1000Z'));
test(new \DateTime('2014-01-30T02:59:59+01:00'));
test(new \DateTime('2014-07-30T02:59:59+02:00'));
echo '<br>';

$zoneMad = new \DateTimeZone('Europe/Madrid');
test(new \DateTime('2014-01-30T02:59:59', $zoneMad));
test(new \DateTime('2014-07-30T02:59:59', $zoneMad));
echo '<br>';

$zone02 = new \DateTimeZone('+02:00');
test(new \DateTime('2014-01-30T02:59:59', $zone02));
test(new \DateTime('2014-07-30T02:59:59', $zone02));
echo '<br>';

$zoneUTC = new \DateTimeZone('UTC');
test(new \DateTime('2014-01-30T02:59:59', $zoneUTC));
test(new \DateTime('2014-07-30T02:59:59', $zoneUTC));
echo '<br>';

function test(\DateTime $date) {
    global $idCount;
    $id = 'i' . ++$idCount;
    $dText = date_format($date, 'c');
    $uText = date_format(
        $date->setTimezone(new DateTimeZone("UTC")),
        'c'
    );
    
    echo 
        $dText . ' | ' .
        $uText . ' | <span id="' . $id . '"></span><br>
    <script>
    var d = new Date("' . $dText . '");
    var u = new Date("' . $uText . '");
    
    
   // d.setTime( d.getTime() - d.getTimezoneOffset()*60000 );
    
    var n = d.toJSON() + 
        "[" + d.toLocaleString() + "#" + (-d.getTimezoneOffset()/60) + "]";
    n += " " + u.toJSON() + "[" + u.toLocaleString() + "#" + (-u.getTimezoneOffset()/60) + "]";
    document.getElementById("' . $id . '").innerHTML = n;
    </script>' ;    
}
?>
</div>
</body>
</html>
    