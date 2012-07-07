<?php
  $api_key = get_option('swiftype_api_key');
  $engine_slug = get_option('swiftype_engine_slug');
  $engine_name = get_option('swiftype_engine_name');
  $num_indexed_documents = get_option('swiftype_num_indexed_documents');
  if (function_exists('get_post_types')) {
    $pt_1 = get_post_types(array('exclude_from_search' => '0'));
    $pt_2 = get_post_types(array('exclude_from_search' => false));
    $custom_types = array_merge($pt_1,$pt_2);
    $custom_types_string = implode(', ', $custom_types);
  }
  $total_post_count = 0;
  foreach($custom_types as $type) {
    if($type == "attachment") { continue; }
    $total_post_count += wp_count_posts($type)->publish;
  }
?>

<div class="wrap">

  <h2 class="swiftype-header">Swiftype Search Plugin</h2>

  <p><b>To administer your Swiftype Search Engine, visit the <a href="http://swiftype.com/users/sign_in" target="_new">Swiftype Dashboard</a></b>.</p>

  <table class="widefat" style="width: 650px;">
    <thead>
      <tr>
        <th class="row-title" colspan="2">Swiftype Search Plugin Settings</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>API Key:</td>
        <td><?php print($api_key); ?></td>
      </tr>
      <tr>
        <td>Search Engine:</td>
        <td><?php print($engine_name); ?></td>
      </tr>
      <tr>
        <td>Number of Indexed Documents:</td>
        <td><span id="num_indexed_documents"><?php print($num_indexed_documents); ?></span></td>
      </tr>
    </tbody>
  </table>
  <br/>

  <?php
    if($num_indexed_documents == 0)
      print("<p><b>Important:</b> Before your site is searchable, you need to index all of your posts. Click the 'synchronize' button below to begin indexing.</p>")
  ?>

  <a href="#" id="index_posts_button" class="gray-button">synchronize with swiftype</a>

  <div class="swiftype" id="progress_bar" style="display: none;">
    <div class="progress">
      <div class="bar" style="display: none;"></div>
    </div>
  </div>

  <p>
    <i>
    Synchronizing your posts with Swiftype ensures that your search engine has indexed all the content you have published.<br/>
    It shouldn't be necessary to synchronize posts regularly (the update process is automated after your initial setup), but<br/>
    you may use this feature any time you suspect your search index is out of date.
    </i>
  </p>

</div>

<script>

  jQuery('#index_posts_button').click(function() {
    index_batch_posts(0);
    delete_trash('delete_trashed_posts');
  });

  var batch_size = 30;

  var total_indexed_posts = 0;
  var total_posts = <?php print($total_post_count) ?>;
  function index_batch_posts(start) {
    set_progress();
    var offset = start || 0;
    if(offset >= total_posts) { return; }
    var data = { action: 'index_all', offset: offset, batch_size: batch_size };
    jQuery.ajax({
        url: ajaxurl,
        data: data,
        dataType: 'json',
        type: 'POST',
        success: function(response, textStatus) {
          total_indexed_posts += batch_size;
          index_batch_posts(offset + batch_size);
        },
        error: function(jqXHR, textStatus, errorThrown) {
          try {
            errorMsg = JSON.parse(jqXHR.responseText).message;
          } catch (e) {
            errorMsg = jqXHR.responseText;
          }
        }
      }
    );
  }

  function delete_trash(action) {
    var data = { action: action };
    jQuery.ajax({
        url: ajaxurl,
        data: data,
        dataType: 'json',
        type: 'POST',
        success: function(response, textStatus) {
          return;
        },
        error: function(jqXHR, textStatus, errorThrown) {
          try {
            errorMsg = JSON.parse(jqXHR.responseText).message;
          } catch (e) {
            errorMsg = jqXHR.responseText;
          }
        }
      }
    );
  }

  function set_progress() {

    if(total_indexed_posts > total_posts) { total_indexed_posts = total_posts; }
    var progress_width = Math.round(total_indexed_posts / total_posts * 245);
    if(progress_width < 10) { progress_width = 10; }
    if(total_indexed_posts == 0) {
      jQuery('#progress_bar').fadeIn();
    }
    jQuery('#num_indexed_documents').html(total_indexed_posts);
    jQuery('#progress_bar').find('div.bar').show().width(progress_width);
    if(total_indexed_posts >= total_posts) {
      jQuery('#index_posts_button').html('Indexing Complete!');
      jQuery('#progress_bar').fadeOut();
      jQuery('#index_posts_button').unbind();
    } else {
      jQuery('#index_posts_button').html('Indexing progress... ' + Math.round(total_indexed_posts / total_posts * 100) + '%');
    }
  }

</script>