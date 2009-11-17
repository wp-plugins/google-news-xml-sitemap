<?php

/*
	Plugin Name: Google News XML Sitemap
	Plugin URI: http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress
	Description: Automatically generate XML sitemap for inclusion to newly formatted Google News. Visit <a href="options-general.php?page=chicago_gnsm.php">Settings >> Google News XML Sitemap</a> for configuration.
	Version: 1.0.0
	Author: Chicago Press Release Services
	Author URI: http://chicagopressrelease.com
	Contributors:
	
	Copyright (c) 2010 Chicago Press Release Services (sales@chicagopressrelease.com)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

	*/

	$chicago_gnsm_sitemap_version = "1.0.0";

	add_option('chicago_gnsm_news_active', true);
	add_option('chicago_gnsm_tags', true);
	add_option('chicago_gnsm_path', "/");
	add_option('chicago_gnsm_last_ping', 0);
	//add_option('chicago_gnsm_publication_name','<publication_name>');
	add_option('chicago_gnsm_n_name','News Publication Name');
	add_option('chicago_gnsm_n_lang','en');
	add_option('chicago_gnsm_n_genres',false);
	add_option('chicago_gnsm_n_genres_type','PressRelease');
	add_option('chicago_gnsm_n_access',false);
	add_option('chicago_gnsm_n_access_type','Subscription');
	//add_option('chicago_gnsm_n_access_type','Registration');
	
	add_action('delete_post', chicago_gnsm_autobuild ,9999,1);	
	add_action('publish_post', chicago_gnsm_autobuild ,9999,1);	
	add_action('publish_page', chicago_gnsm_autobuild ,9999,1);

	$chicago_gnsm_news_active = get_option('chicago_gnsm_news_active');
	$chicago_gnsm_path = get_option('chicago_gnsm_path');
	//$chicago_gnsm_publication_name = get_option('chicago_gnsm_publication_name','<publication_name>');
	$chicago_gnsm_n_name = get_option('chicago_gnsm_n_name','<n:name>');
	$chicago_gnsm_n_lang = get_option('chicago_gnsm_n_lang','<n:language>');
	$chicago_gnsm_n_access = get_option('chicago_gnsm_n_access','<n:access>');
	$chicago_gnsm_n_genres = get_option('chicago_gnsm_n_genres','<n:genres>');
	
	add_action('admin_menu', 'chicago_gnsm_add_pages');
	
	function chicago_gnsm_add_pages() {
		add_options_page("Google News XML Sitemap", "Google News XML Sitemap", 8, basename(__FILE__), "chicago_gnsm_admin_page");
	}

	function chicago_gnsm_escapexml($string) {
		return str_replace ( array ( '&', '"', "'", '<', '>'), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;'), $string);
	}
	
	function chicago_gnsm_permissions() {

		$chicago_gnsm_news_active = get_option('chicago_gnsm_news_active');
		
		$chicago_gnsm_path = ABSPATH . get_option('chicago_gnsm_path');
		$chicago_gnsm_news_file_path = $chicago_gnsm_path . "sitemap-news.xml";
		
		if ($chicago_gnsm_news_active && is_file($chicago_gnsm_news_file_path) && is_writable($chicago_gnsm_news_file_path)) $chicago_gnsm_permission += 0;
		elseif ($chicago_gnsm_news_active && !is_file($chicago_gnsm_news_file_path) && is_writable($chicago_gnsm_path)) {
			$fp = fopen($chicago_gnsm_news_file_path, 'w');
			fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:n=\"http://www.google.com/schemas/sitemap-news/0.9\" />");
			fclose($fp);
			if (is_file($chicago_gnsm_news_file_path) && is_writable($chicago_gnsm_news_file_path)) $chicago_gnsm_permission += 0;
			else $chicago_gnsm_permission += 2;
		}
		elseif ($chicago_gnsm_news_active) $chicago_gnsm_permission += 2;
		else $chicago_gnsm_permission += 0;

		return $chicago_gnsm_permission;
	}

	/*
		Auto Build sitemap
	*/
	function chicago_gnsm_autobuild($postID) {
		global $wp_version;
		$isScheduled = false;
		$lastPostID = 0;

		if($lastPostID != $postID && (!defined('WP_IMPORTING') || WP_IMPORTING != true)) {
			
			if(floatval($wp_version) >= 2.1) {
				if(!$isScheduled) {

					wp_clear_scheduled_hook(chicago_gnsm_generate_sitemap());
					wp_schedule_single_event(time()+15,chicago_gnsm_generate_sitemap());
					$isScheduled = true;
				}
			} else {

				if(!$lastPostID && (!isset($_GET["delete"]) || count((array) $_GET['delete'])<=0)) {
					chicago_gnsm_generate_sitemap();
				}
			}
			$lastPostID = $postID;
		}
	}
	
	function chicago_gnsm_generate_sitemap() {
		global $chicago_gnsm_sitemap_version, $table_prefix;
		global $wpdb;
		
		$t = $table_prefix;
		
		$chicago_gnsm_news_active = get_option('chicago_gnsm_news_active');
		$chicago_gnsm_path = get_option('chicago_gnsm_path');
		//add_option('chicago_gnsm_publication_name','<publication_name>');
		$chicago_gnsm_n_name = get_option('chicago_gnsm_n_name');
		$chicago_gnsm_n_lang = get_option('chicago_gnsm_n_lang');
		$chicago_gnsm_n_genres = get_option('chicago_gnsm_n_genres');
		$chicago_gnsm_n_genres_type = get_option('chicago_gnsm_n_genres_type');
		$chicago_gnsm_n_access = get_option('chicago_gnsm_n_access');
		$chicago_gnsm_n_access_type = get_option('chicago_gnsm_n_access_type');
		//add_option('chicago_gnsm_n_access_type','Registration');

		$chicago_gnsm_permission = chicago_gnsm_permissions();
		if ($chicago_gnsm_permission > 2 || (!$chicago_gnsm_active && !$chicago_gnsm_news_active)) return;

		//mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		//mysql_query("SET NAMES '".DB_CHARSET."'");
		//mysql_select_db(DB_NAME);

		$home = get_option('home') . "/";

		$xml_sitemap_google_news = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$xml_sitemap_google_news .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:n=\"http://www.google.com/schemas/sitemap-news/0.9\">
	<!-- Generated by Google News XML Sitemap Plugin ".$chicago_gnsm_sitemap_version." -->
	<!-- Plugin Created by Chicago Press Release Services | http://chicagopressrelease.com -->
	<!-- News XML Sitemap Last Published ".date("F d, Y, H:i")." -->";

		$posts = $wpdb->get_results("SELECT * FROM ".$wpdb->posts." WHERE `post_status`='publish' AND (`post_type`='page' OR `post_type`='post') GROUP BY `ID` ORDER BY `post_modified_gmt` DESC");		
		
		$now = time();
		$twoDays = 2*24*60*60;
		
		foreach ($posts as $post) {
			if ($chicago_gnsm_news_active && $chicago_gnsm_permission != 2) {
				$postDate = strtotime($post->post_date);
				if ($now - $postDate < $twoDays) {
					$xml_sitemap_google_news .= "
	<url>
		<loc>".chicago_gnsm_escapexml(get_permalink($post->ID))."</loc>
		<n:news>
			<n:publication>
				<n:name>".$chicago_gnsm_n_name."</n:name>
				<n:language>".$chicago_gnsm_n_lang."</n:language>
			</n:publication>";
			
					if ($chicago_gnsm_n_access == true) {
						$xml_sitemap_google_news .= "
						<n:access>".$chicago_gnsm_n_access_type."</n:access>";
						}		
					
					if ($chicago_gnsm_n_genres == true) {
						$xml_sitemap_google_news .= "
						<n:genres>".$chicago_gnsm_n_genres_type."</n:genres>";
						}	
						
					$xml_sitemap_google_news .= "	
			<n:publication_date>".str_replace(" ", "T", $post->post_date_gmt)."Z"."</n:publication_date>
			<n:title>".htmlspecialchars($post->post_title)."</n:title>
			<n:keywords>".htmlspecialchars($post->post_tags)."</n:keywords>
		</n:news>
	</url>";
				}
			}
		}

		$xml_sitemap_google_news .= "\n</urlset>";
		
		if ($chicago_gnsm_news_active && $chicago_gnsm_permission != 2) {
			$fp = fopen(ABSPATH . $chicago_gnsm_path . "sitemap-news.xml", 'w');
			fwrite($fp, $xml_sitemap_google_news);
			fclose($fp);
		}

		$chicago_gnsm_last_ping = get_option('chicago_gnsm_last_ping');
		if ((time() - $chicago_gnsm_last_ping) > 60 * 60) {
			//get_headers("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $chicago_gnsm_path . "sitemap.xml"));	//PHP5+
			$fp = @fopen("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $chicago_gnsm_path . "sitemap-news.xml"), 80);
			@fclose($fp);
			update_option('chicago_gnsm_last_ping', time());
		}
	}

	//Configuration page
	function chicago_gnsm_admin_page() {
		$msg = "";

		// Check form submission and update options
		if ('chicago_gnsm_submit' == $_POST['chicago_gnsm_submit']) {
			update_option('chicago_gnsm_news_active', $_POST['chicago_gnsm_news_active']);
			update_option('chicago_gnsm_n_name', $_POST['chicago_gnsm_n_name']);
			update_option('chicago_gnsm_n_lang', $_POST['chicago_gnsm_n_lang']);
			update_option('chicago_gnsm_n_access', $_POST['chicago_gnsm_n_access']);
			update_option('chicago_gnsm_n_genres', $_POST['chicago_gnsm_n_genres']);
			
			$newPath = trim($_POST['chicago_gnsm_path']);
			if ($newPath == "" || $newPath == "/") $newPath = "./";
			elseif ($newPath[strlen($newPath)-1] != "/") $newPath .= "/";
			
			update_option('chicago_gnsm_path', $newPath);
			
			if ( $_POST['chicago_gnsm_n_genres_type']=="Blog" 
				 || $_POST['chicago_gnsm_n_genres_type']=="PressRelease"
			     || $_POST['chicago_gnsm_n_genres_type']=="UserGenerated" 
			     || $_POST['chicago_gnsm_n_genres_type']=="Satire" 
				 || $_POST['chicago_gnsm_n_genres_type']=="OpEd" 
				 || $_POST['chicago_gnsm_n_genres_type']=="Opinion" ) {
				update_option('chicago_gnsm_n_genres_type', $_POST['chicago_gnsm_n_genres_type']);
			} else { 
				update_option('chicago_gnsm_n_genres_type', "blog"); 
			}
			
			if ($_POST['chicago_gnsm_n_access_type']=="Subscription" || $_POST['chicago_gnsm_n_access_type']=="Registration" ) update_option('chicago_gnsm_n_access_type', $_POST['chicago_gnsm_n_access_type']);
			else update_option('chicago_gnsm_n_access_type', "Subscription");
			
			chicago_gnsm_generate_sitemap();
		}
		
		$chicago_gnsm_news_active = get_option('chicago_gnsm_news_active');
		$chicago_gnsm_path = get_option('chicago_gnsm_path');
		$chicago_gnsm_n_name = get_option('chicago_gnsm_n_name');
		$chicago_gnsm_n_lang = get_option('chicago_gnsm_n_lang');
		$chicago_gnsm_n_genres = get_option('chicago_gnsm_n_genres');
		$chicago_gnsm_n_genres_type = get_option('chicago_gnsm_n_genres_type');
		$chicago_gnsm_n_access = get_option('chicago_gnsm_n_access');
		$chicago_gnsm_n_access_type = get_option('chicago_gnsm_n_access_type');

		$chicago_gnsm_permission = chicago_gnsm_permissions();
		
		if ($chicago_gnsm_permission == 1) $msg = "Error: there is a problem with <em>sitemap-news.xml</em>. This file does not exist or is not writable. Visit <a href=\"http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress\" target=\"_blank\" >plugin home</a> for more information.";
		elseif ($chicago_gnsm_permission == 2) $msg = "Error: there is a problem with <em>sitemap-news.xml</em>. This file does not exist or is not writable. Visit <a href=\"http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress\" target=\"_blank\" >plugin home</a> for more information.";
		elseif ($chicago_gnsm_permission == 3) $msg = "Error: there is a problem with <em>sitemap-news.xml</em>. This file does not exist or is not writable. Visit <a href=\"http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress\" target=\"_blank\" >plugin home</a> for more information.";
?>

<style type="text/css">
a.sm_button {
			padding:4px;
			display:block;
			padding-left:25px;
			background-repeat:no-repeat;
			background-position:5px 50%;
			text-decoration:none;
			border:none;
		}
		 
.sm-padded .inside {
	margin:12px!important;
}
.sm-padded .inside ul {
	margin:6px 0 12px 0;
}

.sm-padded .inside input {
	padding:1px;
	margin:0;
}
</style> 
 
<div class="wrap" id="sm_div">
    <h2> Google News XML Sitemap Plugin</h2> 
    by <strong>Chicago Press Release Services</strong>
    <p>
    &nbsp;<a title="Google News XML Sitemap Plugin" href="http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress" target="_blank">Plugin Info</a> 
    | <a target="_blank" title="Chicago Press Release Services" href="http://chicagopressrelease.com">Chicago Press Release Services</a>
	</p>
<?php	if ($msg) {	?>
	<div id="message" class="error"><p><strong><?php echo $msg; ?></strong></p></div>
<?php	}	?>

    <div style="width:824px;"> 
        <div style="float:left;background-color:white;padding: 10px 10px 10px 10px;margin-right:15px;border: 1px solid #ddd;"> 
            <div style="width:350px;height:130px;"> 
            <h3>Donate</h3> 
             <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
<table width="100%" border="0">
  <tr>
    <td><em>If you find this plugin useful, please</em></td>
    <td><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" /></td>
  </tr>
</table>
             </form>

            <p><em><strong>You can also follow us on <a href="http://twitter.com/windycitynews" title="Follow windycitynews on Twitter" target="_blank">Twitter</a>.</strong></em></p> 
            </div> 
        </div> 
         
        <div style="float:left;background-color:white;padding: 10px 10px 10px 10px;border: 1px solid #ddd;"> 
            <div style="width:415px;height:130px;"> 
                <h3>Google Guidelines and Credits</h3> 
                <p><em>Reference Google News latest sitemap guidelines <a href="http://www.google.com/support/webmasters/bin/answer.py?hl=en&answer=74288" title="Google News sitemap guidelines" target="_blank">here</a>.</em></p>
        <p><em>Plugin created by <a href="http://chicagopressrelease.com" title="Chicago Press Release Services" target="_blank">Chicago Press Release Services</a></em> </p>
            </div> 
        </div> 
    </div>
    <div style="clear:both";></div> 
</div>

<div id="wpbody-content"> 

<div class="wrap" id="sm_div">

<div id="poststuff" class="metabox-holder has-right-sidebar"> 
    <div class="inner-sidebar"> 
		<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;"> 
			<div id="sm_pnres" class="postbox"> 
				<h3 class="hndle"><span>Plugin info:</span></h3> 
				<div class="inside"> 
                    <a href="http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress" title="Google News XML Sitemap Plugin" target="_blank" class="sm_button sm_pluginHome">Plugin Home</a> 
                    <a href="http://www.google.com/support/webmasters/bin/answer.py?hl=en&answer=74288" title="Google News Sitemap Guidelines" target="_blank" class="sm_button sm_pluginList">Google News Sitemap Guidelines</a>
                    <a href="http://chicagopressrelease.com" title="Chicago Press Release Services" target="_blank" class="sm_button sm_pluginList">Chicago Press Release Services</a>
                </div> 
			</div>
        </div>
    </div>

<div class="has-sidebar sm-padded" > 
					
<div id="post-body-content" class="has-sidebar-content"> 

<div class="meta-box-sortabless"> 
                                
<div id="sm_rebuild" class="postbox"> 
	<h3 class="hndle"><span>Google News XML Sitemap Settings</span></h3>
    <div class="inside"> 

		<form name="form1" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>&amp;updated=true">
			<input type="hidden" name="chicago_gnsm_submit" value="chicago_gnsm_submit" />
            <ul>
                <li>
                <label for="chicago_gnsm_news_active">
                    <input name="chicago_gnsm_news_active" type="checkbox" id="chicago_gnsm_news_active" value="1" <?php echo $chicago_gnsm_news_active?'checked="checked"':''; ?> />
                    Create Google News XML Sitemap
                </label>
                </li>
                <li>
                  <label for="chicago_gnsm_n_name"> News Publication Name:
<input name="chicago_gnsm_n_name" type="text" id="chicago_gnsm_n_name" value="<?php echo $chicago_gnsm_n_name?>" /></label></li>
				<li>
				  <label for="chicago_gnsm_n_lang">News  Language (en, es, it, etc.): 
				    <input name="chicago_gnsm_n_lang" type="text" id="chicago_gnsm_n_lang" value="<?php echo $chicago_gnsm_n_lang?>" /></label></li>
				<li>
                <label for="chicago_gnsm_n_genres">
					<input name="chicago_gnsm_n_genres" type="checkbox" id="chicago_gnsm_n_genres" value="1" <?php echo $chicago_gnsm_n_genres?'checked="checked"':''; ?> />
					Enable GENRES  (if applicable)
				</label>
                </li>
                <li>
                <label for="chicago_gnsm_n_genres_type">If GENRES is selected, choose type: 
						<select name="chicago_gnsm_n_genres_type">
							<option <?php echo $chicago_gnsm_n_genres_type=="Blog"?'selected="selected"':'';?> value="Blog">Blog</option>
							<option <?php echo $chicago_gnsm_n_genres_type=="PressRelease"?'selected="selected"':'';?> value="PressRelease">PressRelease</option>
							<option <?php echo $chicago_gnsm_n_genres_type=="UserGenerated"?'selected="selected"':'';?> value="UserGenerated">UserGenerated</option>
                            <option <?php echo $chicago_gnsm_n_genres_type=="Satire"?'selected="selected"':'';?> value="Satire">Satire</option>
                            <option <?php echo $chicago_gnsm_n_genres_type=="OpEd"?'selected="selected"':'';?> value="OpEd">OpEd</option>
                            <option <?php echo $chicago_gnsm_n_genres_type=="Opinion"?'selected="selected"':'';?> value="Opinion">Opinion</option>
                        </select>
                </label>
                </li>
                <li>
                <label for="chicago_gnsm_n_access">
					<input name="chicago_gnsm_n_access" type="checkbox" id="chicago_gnsm_n_access" value="1" <?php echo $chicago_gnsm_n_access?'checked="checked"':''; ?> />
					Enable Limited ACCESS (if applicable)
				</label>
                </li>
                <li>
                <label for="chicago_gnsm_n_access_type">
                If ACCESS is selected, choose type: 
					<select name="chicago_gnsm_n_access_type">
						<option <?php echo $chicago_gnsm_n_access_type=="Subscription"?'selected="selected"':'';?> value="Subscription">Subscription</option>
						<option <?php echo $chicago_gnsm_n_access_type=="Registration"?'selected="selected"':'';?> value="Registration">Registration</option>		
					</select>
                </label>
                </li>
                </ul>
                <b>Advanced settings</b>
                <ul>
                <li>
                <label for="chicago_gnsm_path">
                Google News XML Sitemap Path (relative to site home): 
                  <input name="chicago_gnsm_path" type="text" id="chicago_gnsm_path" value="<?php echo $chicago_gnsm_path?>" />
                </label>
                </li>
				</ul>	
			<p class="submit"> <input type="submit" value="Rebuild Google News XML Sitemap" /></p>
		</form>
        </div>
        </div>
    </div>
    </div>
    </div>
</div>
</div> 
</div>
<?php
	}
?>
