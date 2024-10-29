<?php
/**
 * @author Nguyen Huu Minh
 * @authorurl http://begood.vn
 * @copyright 2013
 * @email huuminh@begood.vn
 */

require_once '../../../wp-load.php';
global $wpdb;
global $current_user;
get_currentuserinfo();
if($_POST){
    $note_info = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."adminnote WHERE (`name` LIKE '%".$_POST['s']."%' OR `text` LIKE '%".$_POST['s']."%') AND `create_by` = '$current_user->ID' ORDER BY `id` DESC" );
    $xhtml = '';
    if(!empty($note_info)){
        foreach($note_info as $k=>$note):
            $xhtml .= '<tr class="note note_'.$note->id.'">';
            $xhtml .= '<td class="align-center"><input type="checkbox" value="'.$note->id.'" name="note_delete[]" /></td>';
            $xhtml .= '<td><a class="row-title" href="./admin.php?page=admin-note&action=edit&note_id='.$note->id.'">'.$note->name.'</a></td>';
            $xhtml .= '<td class="align-center">'.getUserName($note->userid).'</td>';
            $xhtml .= '<td class="align-center">'.getUserName($note->create_by).'</td>';
            $xhtml .= '<td class="align-center">'.$note->time.'</td>';
            $xhtml .= '</tr>';
        endforeach;
    }else{
        $xhtml .= '<tr class="note"><td colspan="5" align="center">No data show</td></tr>';
    }
    echo $xhtml;
}

if(!function_exists('getUserName')){
    function getUserName($id){
        global $wpdb;
        $user_info = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE `ID` = ".$id);
        return $user_info->display_name;
    }
}
?>