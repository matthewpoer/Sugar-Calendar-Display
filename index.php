<?php
/*
 * Find Monday.
 * We need to get today's date and find out when the past Monday was. If it's Sunday, use the next Monday.
 */
$Monday = new DateTime();
$dayOfWeek = date('w');
switch($dayOfWeek){
    case "0":
        $Monday->modify('+1 Day');
        break;
    case "1":
        $Monday->modify('+0 Day');
        break;
    case "2":
        $Monday->modify('-1 Day');
        break;
    case "3":
        $Monday->modify('-2 Day');
        break;
    case "4":
        $Monday->modify('-3 Day');
        break;
    case "5":
        $Monday->modify('-4 Day');
        break;
    case "6":
        $Monday->modify('-5 Day');
        break;
    default:
        die("What day is it? Cannot find Monday.");
        break;
}
$DynamicDay = clone $Monday;

/*
 * Setup the Array for this week, starting with Monday, going thru Saturday
 */
$week = array();
$first = TRUE;
for($i=0;$i<6;$i++){
    if(!$first) $DynamicDay->modify("+1 Day");
    $first = FALSE;
    $week[$DynamicDay->format('l m-d-Y')] = array();
}
/* **At this point, $DynamicDay holds a Saturday's date and $Monday is still Monday** */


/*
 * Basic REST control/setup stuff
 */
require_once("sugar_rest.php");
$sugar = new Sugar_REST();
$error = $sugar->get_error();
if($error !== FALSE) {
    die($error['name']);
}

/*
 * Grab the Users List from the SugarCRM Instance
 */
$where = " first_name is not null and last_name is not null and deleted = 0 ";
$options = array('where' => $where);
$sugar_users = $sugar->get("Users",array('id','first_name','last_name'),$options);
$users = array();
foreach($sugar_users as $sugar_user){
    $users[$sugar_user['id']] = $sugar_user['first_name'] . " " . $sugar_user['last_name'];
}

/*
 * Create User entries in each WeekDay
 */
foreach($week as $index => $array){
    foreach($users as $user_id => $user_name){
        $week[$index][$user_id] = array();
    }
}

/*
 * Grab the Calls and Meetings that have a start date after Monday 01:00 and before Saturday 11:59
 */
$fields = array('id','name','assigned_user_id','duration_hours','duration_minutes','date_start','date_end','status','location');
$where = " deleted = 0 and date_start > '{$Monday->format('Y-m-d')} 00:00:00' and date_end < '{$DynamicDay->format('Y-m-d')} 11:59:59' ";
$options = array('where'=>$where,'order_by'=>'date_start','limit'=>'100');
$calls = $sugar->get("Calls",$fields,$options);
$meetings = $sugar->get("Meetings",$fields,$options);

/*
 * merge Calls and Meetings into a single array.
 * We can still tell one from the other because Meetings will have the 'location' param, even if it's
 * empty, but Calls won't have one at all
 */
$activities = array();
foreach($calls as $call){
    $activities[] = $call;
}
foreach($meetings as $meeting){
    $activities[] = $meeting;
}

/*
 * Add a datetime object to each of the activities, converted from GMT into local timezone
 */
foreach($activities as $index => $activity){
    $DateTimeObject = DateTime::createFromFormat("Y-m-d H:i:s",$activity['date_start'],new DateTimeZone('GMT'));
    $DateTimeObject->setTimezone(new DateTimeZone('America/New_York'));
    $activities[$index]['DateTimeObject'] = $DateTimeObject;
}

/*
 * Re-order our activities based on date_start, earlier on top
 */
foreach($activities as $key=>$value){
    $date_start[$key] = $value['date_start'];
}
array_multisort($date_start,SORT_ASC,$activities);

/*
 * Sort into the $week array
 */
foreach($activities as $activity){
    $date_start = $activity['date_start']; // format e.g. 2012-07-09 14:00:00
    $dateObject = DateTime::createFromFormat("Y-m-d H:i:s",$date_start);
    $week[$dateObject->format('l m-d-Y')][$activity['assigned_user_id']][] = $activity;
}

/*
 * Display some HTML to get us going
 */
?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Weekly Calendar</title>
    <style type='text/css'>
        body {
            font-family:Calibri,sans-serif;
            font-size:.75em;
            font-weight:normal;
            z-index: 0;
        }
        table,tr,td,th,thead,tbody {
            border:1px solid black;
            margin:0;
            padding:0;
            width:1000px;
        }
        thead,tbody,tr {
            border-width: 0;
        }
        th,td {
            width:77px;;
            height:60px;
            vertical-align: top;
        }
        tr {
            height:60px;
        }
        th {
            font-family:Cambria,serif;
            font-family:bold;
            font-family:1em;
        }
        table {
            border-collapse:collapse;
            border-spacing:0;
        }
        div.event_wrap {
            top:0;
            margin:0;
            margin-top:-1px;
            margin-left:-1px;
            padding:0;
            height:21px;
            width:77px;
            background-color: #add8e6;
            overflow:hidden;
            border:1px solid #8b008b;
            z-index:0;
            position:relative;
        }
        div.event_wrap:hover {
            background-color: #00008b;
            overflow: visible;
            font-size:1.1em;
            color:white;
            height:auto;
            z-index: 1000;
            font-weight:bold;
            margin:-10px;
            width:200%;
        }
        div.event_wrap span.event_name {
            width:500px;
            display:block;
        }
        div.event_wrap:hover span.event_name {
            width:100%;
        }
        div.event_wrap span.event_datetime, div.event_wrap span.event_delete {
            display:none;
        }
        div.event_wrap:hover span.event_datetime, div.event_wrap:hover span.event_delete {
            display:block;
        }
        div.event_wrap span.event_delete a {
            color:white;
        }
        div.meeting {
            background-color:#8b008b;
            border-color:#add8e6;
            color:white;
        }
        div.call {

        }
    </style>
</head>
<body>
<table>
    <thead>
    <tr>
        <th>Time</th>
<?php
$count = count($users);
foreach($week as $day=>$data){
    echo "      <th colspan='{$count}'>{$day}</th>";
}
?>
    </tr>
    <tr>
        <td>&nbsp;</td>
<?php
for($i=0;$i<count($week);$i++){
    foreach($users as $username){
        echo "      <th>$username</th>";
    }
}
?>
    </tr>
    </thead>
    <tbody>
<?php
/*
 * Cycle through all dates/times on every day
 */
for($i=8;$i<18;$i++){
    for($o="00";$o<46;$o=$o+15){
        echo "  <tr>";
        echo "      <td>{$i}:{$o}</td>";
        foreach($week as $day => $users_and_events){
            foreach($users as $user_id => $user_name){
                $relevant_events = array();

                foreach($users_and_events as $users_and_events_userid => $users_and_events_events){
                    foreach($users_and_events_events as $users_and_events_event){
                        if($users_and_events_event['DateTimeObject']->format('H') == $i && $users_and_events_event['DateTimeObject']->format('i') == $o && $user_id == $users_and_events_event['assigned_user_id']){
                            $relevant_events[] = $users_and_events_event;
                        }
                    }
                }

                if(empty($relevant_events)){
                    echo "      <td>&nbsp;</td>";
                } else {
                    echo "      <td>";
                    foreach($relevant_events as $relevant_event){
                        $class = (isset($relevant_event['location'])) ? 'meeting' : 'call';
                        echo "<div class='event_wrap {$class}'>";
                        echo "  <span class='event_name'>{$relevant_event['name']}</span>";
                        echo "  <span class='event_datetime'>({$relevant_event['date_start']})</span>";
                        echo "  <span class='event_delete'><a href=''>(remove)</a></span>";
                        echo "</div>";
                    }
                    echo "</td>";
                }
            }
        }
        echo "  </tr>";
    }
}
/*
 * End our table and HTML document
 */
?>
    </tbody>
</table>
</body>
</html>