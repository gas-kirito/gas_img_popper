<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ('abc' is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'gas_img_popper';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 0;

$plugin['version'] = '0.4';
$plugin['author'] = 'Leonardo Gaudino';
$plugin['author_uri'] = 'http://forum.textpattern.com/profile.php?id=201756';
$plugin['description'] = 'Rapid click-paste &lttxp:image /&gt or &lttxp:thumbnail /&gt image code inside Write panel';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and non-AJAX admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the non-AJAX admin side
// 4 = admin+ajax   : only on admin side
// 5 = public+admin+ajax   : on both the public and admin side
$plugin['type'] = '4';

// Plugin 'flags' signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use.
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
gas_img_popper_tab_title        => Link images
gas_img_popper_cat_label        => Category
gas_img_popper_view_all         => View all
gas_img_popper_append_textile   => Textile
gas_img_popper_append_tag       => Image
gas_img_popper_append_thumb     => Thumbnail
gas_img_popper_hint             => Choose category and date interval
gas_next                        => Next
gas_prev                        => Prev
gas_img_popper_from             => From
gas_img_popper_to               => To
gas_img_popper_reset            => Reset
gas_img_popper_update           => Update
gas_img_popper_no_pictures      => No pictures in database
gas_img_popper_count            => Found count image(s)
#@language it-it
gas_img_popper_tab_title        => Collega immagini
gas_img_popper_cat_label        => Categoria
gas_img_popper_view_all         => Tutte
gas_img_popper_append_textile   => Textile
gas_img_popper_append_tag       => Immagine
gas_img_popper_append_thumb     => Miniatura
gas_img_popper_hint             => Scegli una categoria e un intervallo di date
gas_next                        => Succ.
gas_prev                        => Prec.
gas_img_popper_from             => Da
gas_img_popper_to               => A
gas_img_popper_reset            => Azzera
gas_img_popper_update           => Aggiorna
gas_img_popper_no_pictures      => Nessuna immagine nel database
gas_img_popper_count            => Trovate count immagini
#@language ja-jp
gas_img_popper_tab_title        => 画像リンク
gas_img_popper_cat_label        => カテゴリ
gas_img_popper_view_all         => 全部
gas_img_popper_append_textile   => Textile
gas_img_popper_append_tag       => 画像
gas_img_popper_append_thumb     => サムネイル
gas_img_popper_hint             => カテゴリとか日付感覚を選んで下さい
gas_next                        => 次へ
gas_prev                        => 前
gas_img_popper_from             => から
gas_img_popper_to               => まで
gas_img_popper_reset            => リセット
gas_img_popper_update           =>　アップデート
gas_img_popper_no_pictures      => 画像がありません
gas_img_popper_count            => count枚の画像があります
EOT;

if (!defined('txpinterface'))
	@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---
h1. gas_img_popper

A plugin to fast click-paste images in the article's body on Write panel.

h2. Usage

Go to _Write_ panel. Expand _link images_ tab and select an image's category or _View all_ to initialize plugin.
Click on links to js-paste the code inside _body_ textarea.
# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---
if (txpinterface == 'admin') {
  add_privs('gas_img_popper_head', '1,2,3,4,5,6');
  register_callback('gas_img_popper_head', 'admin_side', 'head_end');
  add_privs('gas_img_popper_popup', '1,2,3,4,5,6');
  register_callback('gas_img_popper_popup', 'admin_side', 'pagetop_end');

  register_callback('gas_img_popper_article_ui', 'article_ui', 'extend_col_1');

  add_privs('gas_img_popper_php', '1,2,3,4,5,6');
  register_callback('gas_img_popper_php', 'gas_img_popper_php', '', 1);
}

function gas_img_popper_head($event, $step) {

  switch (gps('event')) {
    case '':
    case 'article':
        echo '<!-- gas_img_popper -->';
        echo <<<jquery
<script type="text/javascript" src="../js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
<style type="text/css" src="../js/jquery-ui.min.css"></style>
<script>
$( function() {
  $('.gas-input-date').datepicker({ dateFormat: 'yy-mm-dd', maxDate: 0});
});
</script>
jquery;

        echo <<<css
<style type="text/css">
#gas-img-popper-group-content {
  max-height:400px;
  overflow-y:auto;
}
#gas-img-popper-ul {
  position:relative;
  padding-left:0;
}
#gas-img-popper-ul li {
  list-style:none;
}
#gas-img-popper-ul li+li {margin-top:.25em}
.gas-img-popper-item a,
.gas-img-popper-item a:hover {
  text-decoration:none;
}
.gas-img-popper-item {
  overflow:hidden;
}
.gas-img-popper-item p {
  line-height:1.1em;
  margin:0 0 0 72px;
}
.gas-img-popper-info {font-size:.85em}
.gas-button {display:inline-block; font-size:1.125em; margin-top: .25em}
.gas-img-js {
  cursor:pointer;
  padding:.5em;
  display:inline-block;
  border:1px solid #aaa;
  color:#333;
  text-decoration:none;
  border-radius:.5em;
  background-color:#e8e8e8;
  background-image:linear-gradient(#f8f8f8,#e8e8e8);
}
.gas-img-popper-a-preview {
  display:block;
  float:left;
  clear:both;
}
.gas-img-popper-item img {
  width:64px;
  height:64px;
  padding:1px;
  border:1px solid #e3e3e3;
  display:inline-block;
  vertical-align:top;
}
.gas-img-popper-list:after {
  content: "";
  display:block;
  clear:both;
}

.gas-img-popper-table tr {border:0}
.gas-img-popper-table td {padding:0 .5em 0 0}
/*.gas-img-popper-table input {max-width:12ex}*/

#gas-popup {
/*  display:flex;*/
  display:none;
  flex-direction:column;
  position:fixed;
  top:4%;
  left:4%;
  width:92%;
  background-color:white;
  border:1px solid #E3E3E3;
  box-shadow:0 .2em .25em rgba(0,0,0,.15);
  padding: 2px;
  max-height:92%;
  box-sizing:border-box;
  z-index:999;
}
@media only screen and (min-width:47em) {
  #gas-popup {
    width: 640px;
/*    position:absolute;
    top:calc(100% + 1em);
    max-height:calc(100vh - 200%);*/
    left:calc(50% - 320px);
  }
}
#gas-popup.gas-popup-show {display:flex;}
.gas-popup-topbar {
  padding:.4em 1em;
  background-color:#eeeeee;
  background-image:linear-gradient(#eee,#ddd);
  font-weight:700;
  margin:0;
  display:block;
}
.gas-widget-icon {
  display:block;
  float:right;
  width:30px;
  height:20px;
  box-sizing:border-box;
  text-align:center;
  border:1px solid #ccc;
  border-radius:6px;
  cursor:pointer;
  line-height:18px;
}
.gas-popup-desc {
  padding:.5em 1em;
  text-align:center;
}
.gas-popup-item {
  flex-shrink:1;
  overflow:hidden;
  padding:0 1em .5em;
  text-align:center;
}
.gas-popup-item figure {
  height:100%;
}
.gas-popup-item figure img {max-height:100%}

.gas-cssload {
  display:none;
  border: 4px solid #f3f3f3; /* Light grey */
  border-top: 4px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: gas-spin 1s ease-in-out infinite;
  margin:auto;
  transition: visibility 5s linear;
}
.gas-cssload-show {
  display:block;
}
@keyframes gas-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

css;

  echo <<<js
<script type="text/javascript">
function insertAtCaret(areaId,text) {
//  text = text + "\\n";
    text = text + " ";
	var txtarea = document.getElementById(areaId);
	var scrollPos = txtarea.scrollTop;
	var strPos = 0;
	var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
		"ff" : (document.selection ? "ie" : false ) );
	if (br == "ie") {
		txtarea.focus();
		var range = document.selection.createRange();
		range.moveStart ('character', -txtarea.value.length);
		strPos = range.text.length;
	}
	else if (br == "ff") strPos = txtarea.selectionStart;

	var front = (txtarea.value).substring(0,strPos);
	var back = (txtarea.value).substring(strPos,txtarea.value.length);
	txtarea.value=front+text+back;
	strPos = strPos + text.length;
	if (br == "ie") {
		txtarea.focus();
		var range = document.selection.createRange();
		range.moveStart ('character', -txtarea.value.length);
		range.moveStart ('character', strPos);
		range.moveEnd ('character', 0);
		range.select();
	}
	else if (br == "ff") {
		txtarea.selectionStart = strPos;
		txtarea.selectionEnd = strPos;
		txtarea.focus();
	}
	txtarea.scrollTop = scrollPos;
}
</script>

js;

echo <<<replace
<script type="text/javascript">
  $(document).ready(function() {
    gas_img_popper_reset_all();
    
    $('#gas-popup-close').click(function() {
      $('#gas-popup').removeClass('gas-popup-show');
    });
  });
  
  $(document).ajaxComplete(function() {
    $('.gas-img-popper-a-preview').click(function( event ) {
      event.preventDefault();
      
      var image = $(this);
//      console.log(image.attr('data-name'));

      $('#gas-popup').addClass('gas-popup-show');
      $('#gas-popup-title').html( image.attr('data-id') + ' - ' + image.attr('data-name') );
      $('#gas-popup-author').html( image.attr('data-author') );
      $('#gas-popup-date').html( image.attr('data-date') );
      $('#gas-popup-size').html( image.attr('data-size') );
      
      $('#gas-popup-figure').empty();
      $('#gas-popup-load').addClass( 'gas-cssload-show' );
//      $('#gas-popup-figure').html( '<img src="' + image.attr('href') + '" title="' + image.attr('data-title') + '" />' );
      $( '<img src="' + image.attr('href') + '" title="' + image.attr('data-title') + '" />' ).on('load', function() {
        $('#gas-popup-load').removeClass( 'gas-cssload-show' );
        $('#gas-popup-figure').html(this);
      });
    });
  });
  
  function gas_img_popper_reset(id) {
    $(id).val('');
  }

  function gas_img_popper_reset_all() {
    gas_img_popper_reset('#gas-img-popper-select');
    gas_img_popper_reset('#gas-img-popper-from');
    gas_img_popper_reset('#gas-img-popper-to');
  }

  function gas_img_popper_load() {
    // trig a spinner
    document.getElementById('gas-img-popper-ul').innerHTML = '<div class="gas-cssload gas-cssload-show"></div>';

    var cat = $('#gas-img-popper-select').val();
    var from = $('#gas-img-popper-from').val();
    var to = $('#gas-img-popper-to').val();

    // ajax request to populate list of images
    $.ajax({
      type: 'POST' ,
      url: 'index.php' ,
      data: { event : 'gas_img_popper_php',
        gas_img_popper_cat : cat,
        gas_img_popper_from : from,
        gas_img_popper_to : to
      },
      datatype: 'json',
      timeout: 10000,
      async: true
    }).done(function( list ) {
      document.getElementById('gas-img-popper-ul').innerHTML = list;
    }).fail(function() {
      document.getElementById('gas-img-popper-ul').innerHTML = '<li>Ajax request failed</li>';
    });
  }
</script>

replace;

//echo <<<preview
//<script type="text/javascript">
//  $(document).ajaxComplete(function() {    
//    $(".gas-img-popper-a-preview").click(function() {
////      $(this).css({"border": "5px solid red"});
//      document.getElementById('gas-popup').innerHTML = '<p>Hello World!</p>';
//    });
//    $(".gas-img-popper-a-preview").mouseleave(function() {
////      $(this).css({"border-color": "green"});
//    });
//  });
//</script>
//preview;

    break;
  }
}

function gas_img_popper_article_ui($event, $step, $data) {
  $_begin = '<section id="gas-img-popper-group" class="txp-details" aria-labelledby="gas-img-popper-group-label">
  <h3 id="gas-img-popper-group-label" class="txp-summary"><a role="button" href="#gas-img-popper-group-content">'.gTxt('gas_img_popper_tab_title').'</a></h3>
  <div id="gas-img-popper-group-content" role="group" class="toggle" style="display:none" aria-expanded="false">
    <div class="txp-container">';

  $_ul = n.'      <ul id="gas-img-popper-ul">
        <li>'.gTxt('gas_img_popper_hint').'</li>
      </ul>';

  $_end = n.'    </div>
  </div>
</section>';

  return $data.$_begin.gas_img_popper_category().$_ul.$_end;
}

function gas_img_popper_list($category = '', $from = '', $to = '') {
  global $siteurl, $path_to_site, $img_dir;

  if(defined('ihu')) {
    $img_path = ihu.$img_dir;
  } else {
    $img_path = hu.$img_dir;
  }

//  if(!defined('IMPATH')) {
//    define('IMPATH', $path_to_site.'/'.$img_dir.'/');
//  }

  $where = (!empty($category) ? 'category LIKE "'.$category.'"' : '1 = 1');
  if (!empty($from)) {
    $where = $where.' AND date>="'.$from.' 00:00:00"';
  }
  if (!empty($to)) {
    $where = $where.' AND date<"'.$to.' 23:59:59"';
  }

  $count = safe_count('txp_image', $where);

  if ($rs = safe_query('SELECT id,name,alt,ext,caption,w,h,category,thumbnail,date,author FROM txp_image WHERE '.$where.' ORDER BY name asc, id asc') and $count != 0) {
    $count_statement = '<p>'.gTxt('gas_img_popper_count', array('count' => $count)).'</p>';
    $items = '';
    // cycle through the rows of the query
    while ($row = nextRow($rs)) {
      // if 'caption' is not set use 'name' in the list
      $description = (!empty($row['caption']) ? $row['caption'] : $row['name']);
      $alt = (!empty($row['alt']) ? $row['alt'] : $row['name']);
      $thumb_alt = 'data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAABmJLR0QA/wD/AP+gvaeTAAAACXBI WXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QEMDx8aXR5EVgAAAB1pVFh0Q29tbWVudAAAAAAAQ3Jl YXRlZCB3aXRoIEdJTVBkLmUHAAABmElEQVR42u2b246DMAxEbScg9f8/tkDieJ/Cpi27Kr2ESyaP UAQ5HttjUPl6vRo1vIQaXwAAAAAAAAAAAAAAAAAAAAAAAAAAAA0uv/YCM6PL5fJwPIRAqlrtwbuu I+fczTFVpRACFPBVBSxF3swopVT1wWOMpKokIuS93w7AK7JnZnLOkcivAFNKpKpkZk+n4rO//SqA 1Tf0fjFiOZIxRooxnrML3MtVVWcpl4BKZey+BqyNfpnDIQRi5lnS+bz3nsZxnM+dSgE538vN52Ka C6mIVNl8dQWUUr/fIDPfFDURqdJZqgJYa1Kas8KlKj7R4g4HoKwRtYzVbgCUHSKlVK0Iyl6knwGk lM5rhP6b7Ep/0EwRNDPqum7O/Rhj9aFKto58nulfmeUPDaC0vkvO8PQAag48uwSwhenZxfuAstcP wwArvLkB27II/pUSTQBwzlHf93M6TNOEFAAAdAEo4HgKKF9i1DZSzPy2o3wbQK7ktT+Oeu8fPo4i BV5REv4vAB8AAAAAAAAAAAAAAAAAAAAAAAAAAM2tH2r1uPtLpFDNAAAAAElFTkSuQmCC';
      $thumb = (($row['thumbnail'] == 1) ? $img_path.'/'.$row['id'].'t'.$row['ext'] : $thumb_alt);

      $items .= n. '<li class="gas-img-popper-item">';
//      $items .= n. '  <a class="gas-img-popper-a-preview" href="'.$img_path.'/'.$row['id'].$row['ext'].'" onclick="window.open(this.href, \'gas_img_popper_window\', \'width=640, height=480, scrollbars, resizable\'); return false;">';
      $items .= n. '  <a class="gas-img-popper-a-preview" href="'.$img_path.'/'.$row['id'].$row['ext'].'" data-id="'.$row['id'].'" data-date="'.$row['date'].'" data-name="'.$row['name'].'" data-size="'.$row['w'].'x'.$row['h'].'" data-author="'.$row['author'].'">';
      // thumbnail
      $items .= n. '    <img src="'.$thumb.'" alt="'.$alt.'" />'.n.'  </a>';
      // echo description
      $items .= n. '  <p class="gas-img-popper-info">'.$row['id'].'. '.$description.' (<i>'.(!empty($row['category']) ? $row['category'] : "-").'</i>)<br />';
      // echo category of current image
      $items .= n. '    <span>'.$row['date'].'</span><br />';
      // echo dimension w x h px
      $items .= n. '    <span>'.$row['author'].', '.$row['w'].'&times'.$row['h'].'px</span><br />';
      // echo links to click-paste code
      // insert tag <txp:image />
      $items .= n. '    <span class="gas-button"><a class="txp-button" onclick="insertAtCaret(\'body\', \'<txp:image id=&quot;'.$row['id'].'&quot; />\');return false;">'.gTxt('gas_img_popper_append_tag').'</a></span>';
      // insert tag <txp:thumbnail />
      $items .= n. '    <span class="gas-button"><a class="txp-button" onclick="insertAtCaret(\'body\', \'<txp:thumbnail id=&quot;'.$row['id'].'&quot; link=&quot;1&quot; poplink=&quot;1&quot; />\');return false;">'.gTxt('gas_img_popper_append_thumb').'</a></span>';
      // insert textile !images/1.jpg(alt)!
      //$items .= n. '    <span class="gas-button"><a class="txp-button" onclick="insertAtCaret(\'body\', \'!'.$img_path.'/'.$row['id'].$row['ext'].(!empty($row['alt']) ? '('.strtr($row['alt'], "()", "[]").')' : "").'!\');return false;">'.gTxt('gas_img_popper_append_textile').'</a></span>';
      // close item
      $items .= n. '  </p>'.n.'</li>';
    }
  } else {
    $items = '<li class="gas-img-popper-item">'.gTxt('gas_img_popper_no_pictures').'</li>';
  }

//  $gas_debug_var .= '<p>$path_to_site: '.$path_to_site.'<br />$siteurl: '.$siteurl.'<br />IMPATH: '.IMPATH.'<br />hu: '.hu.'<br />ihu: '.ihu.'<br />img_dir: '.$img_dir.'</p>';
  return $gas_debug_debug_var.$count_statement.n.$items.n;
}

function gas_img_popper_php() {
  $cat = gps('gas_img_popper_cat');
  $from = gps('gas_img_popper_from');
  $to = gps('gas_img_popper_to');
  echo gas_img_popper_list($cat, $from, $to);
  die();
}

function gas_img_popper_category() {
  $filter = '<div class="txp-form-field">
    <div class="txp-form-field-label">
      <label for="gas-img-popper-category">'.gTxt('gas_img_popper_cat_label').'</label>
    </div>
    <div class="txp-form-field-value">
      <select id="gas-img-popper-select">
        <option value="" selected="selected"></option>';

  if ($rs = getTree('root', 'image')) {
    foreach ($rs as $a) {
      extract($a);
      $filter .= '<option value="'.$name.'" data-level="'.$level.'">'.str_repeat(sp, $level).$title.'</option>';
    }
  }

  $filter .='  </select>
      <!-- <a href="#" onclick="gas_img_popper_reset(\'#gas-img-popper-select\');gas_img_popper_load();return false;">'.gTxt('gas_img_popper_view_all').'</a> -->
    </div>
  </div>

  <div class="txp-form-field">
    <table class="gas-img-popper-table">
      <thead>
        <tr><td>'.gTxt('gas_img_popper_from').'</td><td>'.gTxt('gas_img_popper_to').'</td></tr>
      </thead>
      <tbody>
        <tr>
          <td><input size="11" id="gas-img-popper-from" class="gas-input-date" type="text" placeholder="1999-01-01" maxlength="10" /></td>
          <td><input size="11" id="gas-img-popper-to" class="gas-input-date" type="text" placeholder="2199-12-12" maxlength="10" /></td>
        </tr>
      </tbody>
    </table>
  </div>

  <p class="plain-text">
    <a class="txp-button" href="#" onclick="gas_img_popper_reset_all();return false;">'.gTxt('gas_img_popper_reset').'</a>
    <a class="txp-button" href="#" onclick="gas_img_popper_load();return false;">'.gTxt('gas_img_popper_update').'</a>
  </p>';

  return $filter.n;
}

function gas_img_popper_pager() {
  $pagination = '<div class="txp-form-field">
    <nav class="nav-tertiary" id="gas-pagination">
      <a class="navlink" href="#" id="gas-img-popper-prev">'.gTxt('gas_prev').'</a><a class="navlink" href="#" id="gas-img-popper-next">'.gTxt('gas_next').'</a>
    </nav>
  </div>
  ';

  return $pagination;
}

function gas_img_popper_popup($event, $step) {
  switch (gps('event')) {
    case '':
    case 'article':
      echo n.'<div id="gas-popup">
        <div class="gas-popup-header">
          <span class="gas-popup-topbar">
            <span id="gas-popup-title"></span>
            <span id="gas-popup-close" class="gas-widget-icon">X</span>
          </span>
        </div>
        <div class="gas-popup-desc">
          <span>Author: <b id="gas-popup-author"></b>, Date: <b id="gas-popup-date"></b>, Size: <b id="gas-popup-size"></b>px</span>
        </div>
        <div class="gas-popup-item">
          <div id="gas-popup-load" class="gas-cssload"></div>
          <figure id="gas-popup-figure">
            <img src="" title="" />
          </figure>
        </div>
      </div>';
    
    break; //break case statement  
  }
}
# --- END PLUGIN CODE ---

?>
