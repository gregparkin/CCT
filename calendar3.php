<?php
/**
 * calendar3.php
 *
 * @package   PhpStorm
 * @file      calendar3.php
 * @author    gparkin
 * @date      10/30/16
 * @version   7.0
 *
 * @brief     About this module.
 */
?>
<section id="content" class="planner">
<h2>
<?php echo date("F Y") ?></h2>

<table class="month">
    <tr class="days">
        <td>Mon</td>
        <td>Tues</td>
        <td>Wed</td>
        <td>Thurs</td>
        <td>Fri</td>
        <td>Sat</td>
        <td>Sun</td>
    </tr>
    <?php

    $today = date("d"); // Current day
    $month = date("m"); // Current month
    $year = date("Y"); // Current year
    $days = cal_days_in_month(CAL_GREGORIAN,$month,$year); // Days in current month

    $lastmonth = date("t", mktime(0,0,0,$month-1,1,$year)); // Days in previous month

    $start = date("N", mktime(0,0,0,$month,1,$year)); // Starting day of current month
    $finish = date("N", mktime(0,0,0,$month,$days,$year)); // Finishing day of  current month
    $laststart = $start - 1; // Days of previous month in calander

    $counter = 1;
    $nextMonthCounter = 1;

    if($start > 5){	$rows = 6; }else {$rows = 5; }

    for($i = 1; $i <= $rows; $i++){
        echo '<tr class="week">';
        for($x = 1; $x <= 7; $x++){

            if(($counter - $start) < 0){
                $date = (($lastmonth - $laststart) + $counter);
                $class = 'class="blur"';
            }else if(($counter - $start) >= $days){
                $date = ($nextMonthCounter);
                $nextMonthCounter++;

                $class = 'class="blur"';

            }else {
                $date = ($counter - $start + 1);
                if($today == $counter - $start + 1){
                    $class = 'class="today"';
                }
            }


            echo '<td '.$class.'><a class="date">'. $date . '</a></td>';

            $counter++;
            $class = '';
        }
        echo '</tr>';
    }

    ?>
</table>
</section>