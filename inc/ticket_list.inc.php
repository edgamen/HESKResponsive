<?php
/**
 *
 * This file is part of HESK - PHP Help Desk Software.
 *
 * (c) Copyright Klemen Stirn. All rights reserved.
 * https://www.hesk.com
 *
 * For the full copyright and license agreement information visit
 * https://www.hesk.com/eula.php
 *
 */

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

// List of staff and check their permissions
$admins = array();
$can_assign_to = array();
$res2 = hesk_dbQuery("SELECT `id`,`name`,`isadmin`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ORDER BY `name` ASC");
while ($row = hesk_dbFetchAssoc($res2))
{
    $admins[$row['id']] = $row['name'];

    if ($row['isadmin'] || strpos($row['heskprivileges'], 'can_view_tickets') !== false)
    {
        $can_assign_to[$row['id']] = $row['name'];
    }
}

/* List of categories */
if ( ! isset($hesk_settings['categories']))
{
    $hesk_settings['categories'] = array();
    $res2 = hesk_dbQuery('SELECT `id`, `name` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` WHERE ' . hesk_myCategories('id') . ' ORDER BY `cat_order` ASC');
    while ($row=hesk_dbFetchAssoc($res2))
    {
        $hesk_settings['categories'][$row['id']] = $row['name'];
    }
}

/* Current MySQL time */
$mysql_time = hesk_dbTime();

/* Get number of tickets and page number */
$result = hesk_dbQuery($sql_count);
$total  = hesk_dbResult($result);

if ($total > 0)
{

	/* This query string will be used to browse pages */
	if ($href == 'show_tickets.php')
	{
		#$query  = 'status='.$status;

        $query = '';
        $query .= 's' . implode('=1&amp;s',array_keys($status)) . '=1';
        $query .= '&amp;p' . implode('=1&amp;p',array_keys($priority)) . '=1';

		$query .= '&amp;category='.$category;
		$query .= '&amp;sort='.$sort;
		$query .= '&amp;asc='.$asc;
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[1];
		$query .= '&amp;s_my='.$s_my[1];
		$query .= '&amp;s_ot='.$s_ot[1];
		$query .= '&amp;s_un='.$s_un[1];

		$query .= '&amp;cot='.$cot;
		$query .= '&amp;g='.$group;

		$query .= '&amp;page=';
	}
	else
	{
		$query  = 'q='.$q;
	    $query .= '&amp;what='.$what;
		$query .= '&amp;category='.$category;
		$query .= '&amp;dt='.urlencode($date_input);
		$query .= '&amp;sort='.$sort;
		$query .= '&amp;asc='.$asc;
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[2];
		$query .= '&amp;s_my='.$s_my[2];
		$query .= '&amp;s_ot='.$s_ot[2];
		$query .= '&amp;s_un='.$s_un[2];
		$query .= '&amp;page=';
	}

	$pages = ceil($total/$maxresults) or $pages = 1;
	if ($page > $pages)
	{
		$page = $pages;
	}
	$limit_down = ($page * $maxresults) - $maxresults;

	$prev_page = ($page - 1 <= 0) ? 0 : $page - 1;
	$next_page = ($page + 1 > $pages) ? 0 : $page + 1;

	if ($pages > 1)
	{
		echo '<p align="center">'.sprintf($hesklang['tickets_on_pages'],$total,$pages).' '.$hesklang['jump_page'].' <select name="myHpage" id="myHpage">';
		for ($i=1;$i<=$pages;$i++)
		{
        	$tmp = ($page == $i) ? ' selected="selected"' : '';
			echo '<option value="'.$i.'"'.$tmp.'>'.$i.'</option>';
		}
		echo'</select> <input type="button" value="'.$hesklang['go'].'" onclick="javascript:window.location=\''.$href.'?'.$query.'\'+document.getElementById(\'myHpage\').value" class="orangebutton" onmouseover="hesk_btn(this,\'orangebuttonover\');" onmouseout="hesk_btn(this,\'orangebutton\');" /><br />';

		/* List pages */
		if ($pages > 7)
		{
			if ($page > 2)
			{
				echo '<a href="'.$href.'?'.$query.'1"><b>&laquo;</b></a> &nbsp; ';
			}

			if ($prev_page)
			{
				echo '<a href="'.$href.'?'.$query.$prev_page.'"><b>&lsaquo;</b></a> &nbsp; ';
			}
		}

		for ($i=1; $i<=$pages; $i++)
		{
			if ($i <= ($page+5) && $i >= ($page-5))
			{
				if ($i == $page)
				{
					echo ' <b>'.$i.'</b> ';
				}
				else
				{
					echo ' <a href="'.$href.'?'.$query.$i.'">'.$i.'</a> ';
				}
			}
		}

		if ($pages > 7)
		{
			if ($next_page)
			{
				echo ' &nbsp; <a href="'.$href.'?'.$query.$next_page.'"><b>&rsaquo;</b></a> ';
			}

			if ($page < ($pages - 1))
			{
				echo ' &nbsp; <a href="'.$href.'?'.$query.$pages.'"><b>&raquo;</b></a>';
			}
		}

		echo '</p>';

	} // end PAGES > 1
	else
	{
		echo '<p align="center">'.sprintf($hesklang['tickets_on_pages'],$total,$pages).' </p>';
	}

	/* We have the full SQL query now, get tickets */
	$sql .= " LIMIT ".hesk_dbEscape($limit_down)." , ".hesk_dbEscape($maxresults)." ";
	$result = hesk_dbQuery($sql);

    /* Uncomment for debugging */
    # echo "SQL: $sql\n<br>";

	/* This query string will be used to order and reverse display */
	if ($href == 'show_tickets.php')
	{
		#$query  = 'status='.$status;

        $query = '';
        $query .= 's' . implode('=1&amp;s',array_keys($status)) . '=1';
        $query .= '&amp;p' . implode('=1&amp;p',array_keys($priority)) . '=1';

		$query .= '&amp;category='.$category;
		#$query .= '&amp;asc='.(isset($is_default) ? 1 : $asc_rev);
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[1];
		$query .= '&amp;s_my='.$s_my[1];
		$query .= '&amp;s_ot='.$s_ot[1];
		$query .= '&amp;s_un='.$s_un[1];
		$query .= '&amp;page=1';
		#$query .= '&amp;sort=';

		$query .= '&amp;cot='.$cot;
		$query .= '&amp;g='.$group;

	}
	else
	{
		$query  = 'q='.$q;
	    $query .= '&amp;what='.$what;
		$query .= '&amp;category='.$category;
		$query .= '&amp;dt='.urlencode($date_input);
		#$query .= '&amp;asc='.$asc;
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[2];
		$query .= '&amp;s_my='.$s_my[2];
		$query .= '&amp;s_ot='.$s_ot[2];
		$query .= '&amp;s_un='.$s_un[2];
		$query .= '&amp;page=1';
		#$query .= '&amp;sort=';
	}

    $query .= '&amp;asc=';

	/* Print the table with tickets */
	$random=rand(10000,99999);
	?>

	<form name="form1" action="delete_tickets.php" method="post" onsubmit="return hesk_confirmExecute('<?php echo hesk_makeJsString($hesklang['confirm_execute']); ?>')">

    <?php
    if (empty($group))
    {
		hesk_print_list_head();
    }

	$i = 0;
	$checkall = '<input type="checkbox" name="checkall" value="2" onclick="hesk_changeAll()" />';

    $group_tmp = '';
	$is_table = 0;
	$space = 0;

	while ($ticket=hesk_dbFetchAssoc($result))
	{
		// Are we grouping tickets?
		if ($group)
        {
			require(HESK_PATH . 'inc/print_group.inc.php');
        }

		// Determine line color
		if ($i) {$color="admin_gray"; $i=0;}
		else {$color="admin_white"; $i=1;}

		// Set owner (needed for row title)
		$owner = '';
        $first_line = '(' . $hesklang['unas'] . ')'." \n\n";
		if ($ticket['owner'] == $_SESSION['id'])
		{
			$owner = '<span class="assignedyou" title="'.$hesklang['tasy2'].'">*</span> ';
            $first_line = $hesklang['tasy2'] . " \n\n";
		}
		elseif ($ticket['owner'])
		{
        	if (!isset($admins[$ticket['owner']]))
            {
            	$admins[$ticket['owner']] = $hesklang['e_udel'];
            }
			$owner = '<span class="assignedother" title="'.$hesklang['taso3'] . ' ' . $admins[$ticket['owner']] .'">*</span> ';
            $first_line = $hesklang['taso3'] . ' ' . $admins[$ticket['owner']] . " \n\n";
		}

		// Prepare ticket priority
		switch ($ticket['priority'])
		{
			case 0:
				$ticket['priority']='<img src="../img/flag_critical.png" width="16" height="16" alt="'.$hesklang['priority'].': '.$hesklang['critical'].'" title="'.$hesklang['priority'].': '.$hesklang['critical'].'" border="0" />';
                $color = 'admin_critical';
				break;
			case 1:
				$ticket['priority']='<img src="../img/flag_high.png" width="16" height="16" alt="'.$hesklang['priority'].': '.$hesklang['high'].'" title="'.$hesklang['priority'].': '.$hesklang['high'].'" border="0" />';
				break;
			case 2:
				$ticket['priority']='<img src="../img/flag_medium.png" width="16" height="16" alt="'.$hesklang['priority'].': '.$hesklang['medium'].'" title="'.$hesklang['priority'].': '.$hesklang['medium'].'" border="0" />';
				break;
			default:
				$ticket['priority']='<img src="../img/flag_low.png" width="16" height="16" alt="'.$hesklang['priority'].': '.$hesklang['low'].'" title="'.$hesklang['priority'].': '.$hesklang['low'].'" border="0" />';
		}		

		// Set message (needed for row title)
		$ticket['message'] = $first_line . hesk_mb_substr(strip_tags($ticket['message']),0,200).'...';

		// Start ticket row
		echo '
		<tr title="'.$ticket['message'].'">
		<td class="'.$color.'" style="text-align:left; white-space:nowrap;"><input type="checkbox" name="id[]" value="'.$ticket['id'].'" />&nbsp;</td>
		';

		// Print sequential ID and link it to the ticket page
		if ( hesk_show_column('id') )
		{
			echo '<td class="'.$color.'" style="text-align:left; white-space:nowrap;"><a href="admin_ticket.php?track='.$ticket['trackid'].'&amp;Refresh='.$random.'">'.$ticket['id'].'</a></td>';
		}

		// Print tracking ID and link it to the ticket page
		if ( hesk_show_column('trackid') )
		{
			echo '<td class="'.$color.'" style="text-align:left; white-space:nowrap;"><a href="admin_ticket.php?track='.$ticket['trackid'].'&amp;Refresh='.$random.'">'.$ticket['trackid'].'</a></td>';
		}

		// Print date submitted
		if ( hesk_show_column('dt') )
		{
			switch ($hesk_settings['submittedformat'])
			{
	        	case 1:
					$ticket['dt'] = hesk_formatDate($ticket['dt']);
					break;
				case 2:
					$ticket['dt'] = hesk_time_lastchange($ticket['dt']);
					break;
				default:
					$ticket['dt'] = hesk_time_since( strtotime($ticket['dt']) );
			}
			echo '<td class="'.$color.'">'.$ticket['dt'].'</td>';
		}

		// Print last modified
		if ( hesk_show_column('lastchange') )
		{
			switch ($hesk_settings['updatedformat'])
			{
	        	case 1:
					$ticket['lastchange'] = hesk_formatDate($ticket['lastchange']);
					break;
				case 2:
					$ticket['lastchange'] = hesk_time_lastchange($ticket['lastchange']);
					break;
				default:
					$ticket['lastchange'] = hesk_time_since( strtotime($ticket['lastchange']) );
			}
			echo '<td class="'.$color.'">'.$ticket['lastchange'].'</td>';
		}

		// Print ticket category
		if ( hesk_show_column('category') )
		{
			$ticket['category'] = isset($hesk_settings['categories'][$ticket['category']]) ? $hesk_settings['categories'][$ticket['category']] : $hesklang['catd'];
			echo '<td class="'.$color.'">'.$ticket['category'].'</td>';
		}

		// Print customer name
		if ( hesk_show_column('name') )
		{
			echo '<td class="'.$color.'">'.$ticket['name'].'</td>';
		}

		// Print customer email
		if ( hesk_show_column('email') )
		{
			echo '<td class="'.$color.'"><a href="mailto:'.$ticket['email'].'">'.$hesklang['clickemail'].'</a></td>';
		}

		// Print subject and link to the ticket page
		if ( hesk_show_column('subject') )
		{
			echo '<td class="'.$color.'">'.($ticket['archive'] ? '<img src="../img/tag.png" width="16" height="16" alt="'.$hesklang['archived'].'" title="'.$hesklang['archived'].'"  border="0" style="vertical-align:text-bottom" /> ' : '').$owner.'<a href="admin_ticket.php?track='.$ticket['trackid'].'&amp;Refresh='.$random.'">'.$ticket['subject'].'</a></td>';
		}

		// Print ticket status
		if ( hesk_show_column('status') )
		{
            echo '<td class="'.$color.'">' . hesk_get_admin_ticket_status($ticket['status']) . '&nbsp;</td>';
		}

		// Print ticket owner
		if ( hesk_show_column('owner') )
		{
			if ($ticket['owner'])
			{
				$ticket['owner'] = isset($admins[$ticket['owner']]) ? $admins[$ticket['owner']] : $hesklang['unas'];
			}
			else
			{
				$ticket['owner'] = $hesklang['unas'];
			}
			echo '<td class="'.$color.'">'.$ticket['owner'].'</td>';
		}

		// Print number of all replies
		if ( hesk_show_column('replies') )
		{
			echo '<td class="'.$color.'">'.$ticket['replies'].'</td>';
		}

		// Print number of staff replies
		if ( hesk_show_column('staffreplies') )
		{
			echo '<td class="'.$color.'">'.$ticket['staffreplies'].'</td>';
		}

		// Print last replier
		if ( hesk_show_column('lastreplier') )
		{
			if ($ticket['lastreplier'])
			{
				$ticket['repliername'] = isset($admins[$ticket['replierid']]) ? $admins[$ticket['replierid']] : $hesklang['staff'];
			}
			else
			{
				$ticket['repliername'] = $ticket['name'];
			}
			echo '<td class="'.$color.'">'.$ticket['repliername'].'</td>';
		}

		// Print time worked
		if ( hesk_show_column('time_worked') )
		{
			echo '<td class="'.$color.'">'.$ticket['time_worked'].'</td>';
		}

		// Print custom fields
		foreach ($hesk_settings['custom_fields'] as $key => $value)
		{
			if ($value['use'] && hesk_show_column($key) )
            {
				echo '<td class="'.$color.'">'.($value['type'] == 'date' ? hesk_custom_date_display_format($ticket[$key], $value['value']['date_format']) : $ticket[$key]).'</td>';
            }
		}

		// End ticket row
		echo '
		<td class="'.$color.'" style="text-align:center; white-space:nowrap;">'.$ticket['priority'].'&nbsp;</td>
		</tr>
		';

	} // End while
	?>
	</table>
	</div>

    &nbsp;<br />

    <table border="0" width="100%">
    <tr>
    <td width="50%" style="text-align:left;vertical-align:top">
	    <?php
	    if (hesk_checkPermission('can_add_archive',0))
	    {
		    ?>
			<img src="../img/tag.png" width="16" height="16" alt="<?php echo $hesklang['archived']; ?>" title="<?php echo $hesklang['archived']; ?>"  border="0"  style="vertical-align:text-bottom" /> <?php echo $hesklang['archived2']; ?><br />
		    <?php
	    }
	    ?>

	    <span class="assignedyou">*</span> <?php echo $hesklang['tasy2']; ?><br />

	    <?php
	    if (hesk_checkPermission('can_view_ass_others',0) || hesk_checkPermission('can_view_ass_by',0))
	    {
		    ?>
			<span class="assignedother">*</span> <?php echo $hesklang['taso2']; ?><br />
		    <?php
	    }
	    ?>
        &nbsp;
    </td>
    <td width="50%" style="text-align:right;vertical-align:top">
        <?php echo $hesklang['with_selected']; ?>
		<select name="a">
		<option value="low" selected="selected"><?php echo $hesklang['set_pri_to'].' '.$hesklang['low']; ?></option>
		<option value="medium"><?php echo $hesklang['set_pri_to'].' '.$hesklang['medium']; ?></option>
		<option value="high"><?php echo $hesklang['set_pri_to'].' '.$hesklang['high']; ?></option>
		<option value="critical"><?php echo $hesklang['set_pri_to'].' '.$hesklang['critical']; ?></option>
		<?php
        if ( hesk_checkPermission('can_resolve', 0) )
        {
            ?>
            <option value="close"><?php echo $hesklang['close_selected']; ?></option>
            <?php
        }

		if ( hesk_checkPermission('can_add_archive', 0) )
		{
			?>
			<option value="tag"><?php echo $hesklang['add_archive_quick']; ?></option>
			<option value="untag"><?php echo $hesklang['remove_archive_quick']; ?></option>
			<?php
		}

        ?>
        <option value="print"><?php echo $hesklang['print_selected']; ?></option>
        <?php

		if ( ! defined('HESK_DEMO') )
		{

			if ( hesk_checkPermission('can_merge_tickets', 0) )
			{
				?>
				<option value="merge"><?php echo $hesklang['mer_selected']; ?></option>
				<?php
			}
			if ( hesk_checkPermission('can_export', 0) )
			{
				?>
				<option value="export"><?php echo $hesklang['export_selected']; ?></option>
				<?php
			}
			if ( hesk_checkPermission('can_privacy', 0) )
			{
				?>
				<option value="anonymize"><?php echo $hesklang['anon_selected']; ?></option>
				<?php
			}
			if ( hesk_checkPermission('can_del_tickets', 0) )
			{
				?>
				<option value="delete"><?php echo $hesklang['del_selected']; ?></option>
				<?php
			}

		} // End demo
		?>
		</select>
		<input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
		<input type="submit" value="<?php echo $hesklang['execute']; ?>" class="orangebutton"  onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />

        <?php
        if (hesk_checkPermission('can_assign_others',0))
        {
            ?>
            <br />&nbsp;<br />

            <?php echo $hesklang['assign_selected']; ?>
            <select name="owner">
            <option value="" selected="selected"><?php echo $hesklang['select']; ?></option>
            <option value="-1"> &gt; <?php echo $hesklang['unas']; ?> &lt; </option>
            <?php
            foreach ($can_assign_to as $k=>$v)
            {
                echo '<option value="'.$k.'">'.$v.'</option>';
            }
            ?>
            </select>
            <input type="submit" name="assign" value="<?php echo $hesklang['assi']; ?>" class="orangebutton"  onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
            <?php
        }
        ?>

    </td>
    </tr>
    </table>

	</form>
	<?php

} // END ticket list if total > 0
else
{
    if (isset($is_search) || $href == 'find_tickets.php')
    {
        hesk_show_notice($hesklang['no_tickets_crit']);
    }
    else
    {
        echo '<p>&nbsp;<br />&nbsp;<b><i>'.$hesklang['no_tickets_open'].'</i></b><br />&nbsp;</p>';
    }
}


function hesk_print_list_head()
{
	global $hesk_settings, $href, $query, $sort_possible, $hesklang;
	?>
	<div align="center">
	<table border="0" width="100%" cellspacing="1" cellpadding="3" class="white">
	<tr>
	<th class="admin_white" style="width:1px"><input type="checkbox" name="checkall" value="2" onclick="hesk_changeAll(this)" /></th>
	<?php
	foreach ($hesk_settings['ticket_list'] as $field)
	{
		echo '<th class="admin_white" style="text-align:left; white-space:nowrap;"><a href="' . $href . '?' . $query . $sort_possible[$field] . '&amp;sort=' . $field . '">' . $hesk_settings['possible_ticket_list'][$field] . '</a></th>';
	}
	?>
	<th class="admin_white" style="text-align:center; white-space:nowrap;width:1px"><a href="<?php echo $href . '?' . $query . $sort_possible['priority'] . '&amp;sort='; ?>priority"><img src="../img/sort_priority_<?php echo (($sort_possible['priority']) ? 'asc' : 'desc'); ?>.png" width="16" height="16" alt="<?php echo $hesklang['sort_by'].' '.$hesklang['priority']; ?>" title="<?php echo $hesklang['sort_by'].' '.$hesklang['priority']; ?>" border="0" /></a></th>
	</tr>
	<?php
} // END hesk_print_list_head()


function hesk_time_since($original)
{
	global $hesk_settings, $hesklang, $mysql_time;

    /* array of time period chunks */
    $chunks = array(
        array(60 * 60 * 24 * 365 , $hesklang['abbr']['year']),
        array(60 * 60 * 24 * 30 , $hesklang['abbr']['month']),
        array(60 * 60 * 24 * 7, $hesklang['abbr']['week']),
        array(60 * 60 * 24 , $hesklang['abbr']['day']),
        array(60 * 60 , $hesklang['abbr']['hour']),
        array(60 , $hesklang['abbr']['minute']),
        array(1 , $hesklang['abbr']['second']),
    );

	/* Invalid time */
    if ($mysql_time < $original)
    {
    	// DEBUG return "T: $mysql_time (".date('Y-m-d H:i:s',$mysql_time).")<br>O: $original (".date('Y-m-d H:i:s',$original).")";
        return "0".$hesklang['abbr']['second'];
    }

    $since = $mysql_time - $original;

    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {

        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            // DEBUG print "<!-- It's $name -->\n";
            break;
        }
    }

    $print = "$count{$name}";

    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];

        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            $print .= "$count2{$name2}";
        }
    }
    return $print;
} // END hesk_time_since()


function hesk_time_lastchange($original)
{
	global $hesk_settings, $hesklang;

	// Save time format setting so we can restore it later
	$copy = $hesk_settings['timeformat'];

	// We need this time format for this function
	$hesk_settings['timeformat'] = 'Y-m-d H:i:s';

	// Get HESK time-adjusted start of today if not already
	if ( ! defined('HESK_TIME_TODAY') )
	{
		// Adjust for HESK time and define constants for alter use
		define('HESK_TIME_TODAY',		date('Y-m-d 00:00:00', hesk_date(NULL, false, false, false) ) );
		define('HESK_TIME_YESTERDAY',	date('Y-m-d 00:00:00', strtotime(HESK_TIME_TODAY)-86400) ) ;
	}

	// Adjust HESK time difference and get day name
	$ticket_time = hesk_date($original, true);

	if ($ticket_time >= HESK_TIME_TODAY)
	{
		// For today show HH:MM
		$day = substr($ticket_time, 11, 5);
	}
	elseif ($ticket_time >= HESK_TIME_YESTERDAY)
	{
		// For yesterday show word "Yesterday"
		$day = $hesklang['r2'];
	}
	else
	{
		// For other days show DD MMM YY
		list($y, $m, $d) = explode('-', substr($ticket_time, 0, 10) );
		$day = '<span style="white-space: nowrap;">' . $d . ' ' . $hesklang['ms'.$m] . ' ' . substr($y, 2) . '</span>';
	}

	// Restore original time format setting
	$hesk_settings['timeformat'] = $copy;

	// Return value to display
	return $day;

} // END hesk_time_lastchange()
