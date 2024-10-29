<?php
/*
Plugin Name: Admin Notes
Plugin URI: http://changeyourthinking.net
Description: Create Note For Admin
Version: 1.1
Author: Minh Nguyen
Author URI: http://begood.vn
License: GPL2
http://www.gnu.org/licenses/gpl-2.0.html
*/

global $admin_note_db_version;
$admin_note_db_version = "1.0";


function admin_note_install(){
     global $wpdb;
     global $admin_note_db_version;
     $table_name = $wpdb->prefix . "adminnote";
     
     $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      name VARCHAR(255) NOT NULL,
      text text NOT NULL,
      create_by INT(11) NOT NULL,
      userid INT(11) NOT NULL,
      UNIQUE KEY id (id)
      )";
      
     require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
     dbDelta( $sql );
     add_option( "admin_note_version", $admin_note_db_version );
}

register_activation_hook( __FILE__, 'admin_note_install');


      
function admin_note_menu(){
    $url_icon = plugins_url().'/adminnote/notes.png'; 
    add_menu_page( 'Admin Notes', 'Notes', 'manage_options', 'admin-note', 'admin_note_menu_callback', $url_icon, 3 );

}

function add_custom_style(){
    echo '<link rel="stylesheet" href="'.plugins_url().'/adminnote/note.css" />';
    echo '<script type="text/javascript" src="'.plugins_url().'/adminnote/jquery.validate.min.js"></script>';
    echo '<script type="text/javascript">jQuery("#addNoteForm").validate();</script>';
}
add_action('admin_head', 'add_custom_style');


function getUserName($id){
    global $wpdb;
    $user_info = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE `ID` = ".$id);
    return $user_info->display_name;
}

function admin_note_menu_callback(){       
    $plugin_dir_path = plugin_dir_url(__FILE__);
    
    if ( !current_user_can( 'manage_options' ))  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
    global $current_user;
    get_currentuserinfo();

    if(empty($_GET['action'])):
    global $wpdb;
    
    
    if($_POST){
        $note_delete = $_POST['note_delete'];
        $count = 0;
        foreach($note_delete as $note){
            $wpdb->delete( $wpdb->prefix."adminnote" , array( 'id' => $note ) );
            $count += 1;
        }
        header('Location: ./admin.php?page=admin-note');
    }
    
    $totalItems	= $wpdb->get_var( "SELECT COUNT(*) FROM ".$wpdb->prefix."adminnote WHERE `create_by` = $current_user->ID" );
    $totalItemsPerPage = 10;
    $pageRange = 5;
    if($pageRange %2 == 0) $pageRange = $pageRange + 1;
    $totalPage = ceil($totalItems/$totalItemsPerPage);
    $currentPage = (isset($_GET['nv'])) ? $_GET['nv'] : 1;
    
    if($currentPage < 1) {
		wp_die( __( 'This page does not exist' ) );
	}elseif($totalPage > 0 && $currentPage > $totalPage){
	   wp_die( __( 'This page does not exist' ) );
	}
    $paginationHTML = '';
    
	if($totalPage > 1){
		$start 	= '';
		$prev 	= '<li><span>Previous</span></li>';
		if($currentPage > 1){
			$start 	= '';
			$prev 	= '<li><a href="./admin.php?page=admin-note&nv='.($currentPage-1).'">Previous</a></li>';
		}
		
		$next 	= '<li><span>Next</span></li>';
		$end 	= '';
		if($currentPage < $totalPage){
			$next 	= '<li><a href="./admin.php?page=admin-note&nv='.($currentPage+1).'">Next</a></li>';
			$end 	= '';
		}
		
		if($pageRange < $totalPage){
			if($currentPage == 1){
				$startPage 	= 1;
				$endPage 	= $pageRange;
			}else if($currentPage == $totalPage){
				$startPage		= $totalPage - $pageRange + 1;
				$endPage		= $totalPage;
			}else{
				$startPage		= $currentPage - ($pageRange-1)/2;
				$endPage		= $currentPage + ($pageRange-1)/2;
				
				if($startPage < 1){
					$endPage	= $endPage + 1;
					$startPage = 1;
				}
				
				if($endPage > $totalPage){
					$endPage	= $totalPage;
					$startPage 	= $endPage - $pageRange + 1;
				}
			}
		}else{
			$startPage		= 1;
			$endPage		= $totalPage;
		}
		
		for($i = $startPage; $i <= $endPage; $i++){
			if($i == $currentPage) {
				$listPages .= '<li class="active"><span>'.$i.'</span></a>';
			}else{
				$listPages .= '<li><a href="./admin.php?page=admin-note&nv='.$i.'">'.$i.'</a>';
			}
		}
		
		$paginationHTML = '<ul class="pagination">' . $start . $prev . $listPages . $next . $end . '</ul>';
	}
    $position	= ($currentPage-1)*$totalItemsPerPage;
    $note_info = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."adminnote WHERE `create_by` = $current_user->ID ORDER BY `id` DESC LIMIT $position , $totalItemsPerPage" );
    ?>
    <div class="wrap">
        <h2>List notes <a class="add-new-h2" href="./admin.php?page=admin-note&action=add">Add Note</a></h2>
        <div class="search_form" style="padding-right: 4px; padding-bottom: 20px">
            <form id="search_form">
                <p class="search-box">
            	<label class="screen-reader-text" for="post-search-input">Search Notes:</label>
            	<input type="search" id="post-search-input" name="s" value="" placeholder="Enter keyword..." />
            	<input type="submit" name="" id="search-submit" class="button" value="Search Notes" /></p>
            </form>
        </div>
    </div>
    <form method="post" id="note-list-table">
        <table id="note_table" style="width: 98%;" cellpadding="0" cellspacing="0" >
            <tr>
                <th class="cl1">#</th>
                <th class="cl2">Title</th>
                <th class="cl3">Note for</th>
                <th class="cl4">Created by</th>
                <th class="cl5">Created date</th>
            </tr>
            <tr class="loading">
                <td colspan="5"><img src="<?php echo $plugin_dir_path;?>loading.gif" /><br />Loading</td>
            </tr>
            <?php
            foreach($note_info as $k=>$note):
                if($note->create_by == $current_user->ID){
                ?>
                <tr class="note note_<?php echo $note->id;?>">
                    <td class="align-center"><input type="checkbox" value="<?php echo $note->id?>" name="note_delete[]" /></td>
                    <td><a class="row-title" href="./admin.php?page=admin-note&action=edit&note_id=<?php echo $note->id;?>"><?php echo $note->name;?></a></td>
                    <td class="align-center"><?php echo getUserName($note->userid);?></td>
                    <td class="align-center"><?php echo getUserName($note->create_by);?></td>
                    <td class="align-center"><?php echo $note->time;?></td>
                </tr>
                <?php
                }
            endforeach;
            ?>
            <tr class="bottom_control">
                <td>
                    <input class="button" type="submit" value="Delete Note" />
                </td>
                <td class="pagination_box" colspan="4">    
                    <?php echo $paginationHTML;?>
                </td>
            </tr>
        </table>
    </form>
    <script type="text/javascript">
        jQuery('#search_form').submit(function(){
            var url = '<?php echo $plugin_dir_path?>adminnoteajax.php';
            var data = jQuery(this).serialize();
            jQuery.ajax({
                type: "POST",
                url: url,
                data: data,
                beforeSend: function(){
                    jQuery('tr.note').remove();
                    jQuery('tr.loading').fadeIn('slow');
                },
                success: function(data){
                    setTimeout(function(){
                        jQuery('tr.loading').fadeOut('fast');
                        jQuery('tr.bottom_control').before(data);
                    }, 1000);
                },
            });
            
            return false;
        })
        
    </script>
    <?php
    elseif(!empty($_GET['action']) && $_GET['action'] == 'add'):
    global $wpdb;
    $users = $wpdb->get_results("SELECT `ID`,`user_login`,`display_name` FROM $wpdb->users");
    global $current_user;
    get_currentuserinfo();
    
    if($_POST){
        $datetime = current_time( 'mysql' );
        $title = $_POST['title'];
        $content = $_POST['content'];
        $create_by = $current_user->ID;
        $userid = $_POST['note_for'];
        $wpdb->insert($wpdb->prefix.'adminnote', array(
            'time'  => $datetime,
            'name'  => $title,
            'text'  => $content,
            'userid'=> $userid,
            'create_by' => $create_by,
        ), array(
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
        ));
        $last_insert_id = $wpdb->insert_id;
        if($last_insert_id > 0){
            header('Location: ./admin.php?page=admin-note');
        }else{
            header('Location: ./admin.php?page=admin-note');
        }
    }
    ?>
    <div class="wrap">
        <h2>Add Note</h2>
        <div class="form_wrapper">
            <form method="post" action="" id="addNoteForm">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" size="40" name="title" id="title" required="" />
                </div>
                <div class="form-group">
                    <label for="note_for">Note For</label>
                    <select style="width: 349px;" name="note_for" id="note_for" required>
                        <option value="">--- Select User ---</option>
                        <?php
                        foreach($users as $k=>$v):
                        ?>
                        <option value="<?php echo $v->ID; ?>"><?php echo $v->display_name; ?></option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <?php 
                        $content = stripslashes(htmlspecialchars_decode($note_info->text));
                        $editor_id = 'content';
                        wp_editor( $content, $editor_id );
                    ?>
                </div>
                <div class="form-group">
                    <input class="button button-primary" type="submit" value="Save Note" />
                </div>
            </form>
        </div>
    </div>
    <?php
    elseif(!empty($_GET['action']) && $_GET['action'] == 'edit' && !empty($_GET['note_id'])):
        
        $note_id = $_GET['note_id'];
        global $wpdb;
        $note_info = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."adminnote WHERE `id` = ".$note_id);
        $users = $wpdb->get_results("SELECT `ID`,`user_login`,`display_name` FROM $wpdb->users");
        
        global $current_user;
        get_currentuserinfo();
        
        if($current_user->ID != $note_info->create_by){
            wp_die( __( 'You do not have sufficient permissions to access this page.' ));
                    
        }        
        
        if($_POST){
            $title = $_POST['title'];
            $content = $_POST['content'];
            $create_by = $current_user->ID;
            $userid = $_POST['note_for'];
            
            $wpdb->update($wpdb->prefix.'adminnote', array(
                'name'  => $title,
                'text'  => $content,
                'userid'=> $userid,
            ),
            array('id' => $note_id),
            array(
                '%s',
                '%s',
                '%d',
            ), 
            array('%d'));
            $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            header("Location: $actual_link");
        }
    ?>
        <div class="wrap">
            <h2>Edit Note <a class="add-new-h2" href="./admin.php?page=admin-note&action=add">Add Note</a></h2>
            <div class="form_wrapper">
                <form method="post" action="" id="addNoteForm">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" size="40" name="title" id="title" value="<?php echo $note_info->name; ?>" required="" />
                    </div>
                    <div class="form-group">
                        <label for="note_for">Note For</label>
                        <select style="width: 349px;" name="note_for" id="note_for" required>
                            <option value="">--- Select User ---</option>
                            <?php
                            foreach($users as $k=>$v):
                            if($note_info->userid == $v->ID){
                            ?>
                            <option value="<?php echo $v->ID; ?>" selected=""><?php echo $v->display_name; ?></option>
                            <?php
                            }else{
                            ?>
                            <option value="<?php echo $v->ID; ?>"><?php echo $v->display_name; ?></option>
                            <?php
                            }
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <?php 
                        $content = stripslashes(htmlspecialchars_decode($note_info->text));
                        $editor_id = 'content';
                        wp_editor( $content, $editor_id );
                        ?>
                    </div>
                    <div class="form-group">
                        <input class="button button-primary" type="submit" value="Update Note" />
                        <a href="./admin.php?page=admin-note" class="button">Close</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
    endif;
}
add_action( 'admin_menu', 'admin_note_menu' );
?>