<?php
class AmazonSimpleAdmin {
	
	const DB_COLL 		= 'asa_collection';
	const DB_COLL_ITEM 	= 'asa_collection_item';
	
	/**
	 * this plugins home directory
	 */
	protected $plugin_dir = '/wp-content/plugins/amazonsimpleadmin';
	
	protected $plugin_url = 'options-general.php?page=amazonsimpleadmin.php';
	
	/**
	 * supported amazon country IDs
	 */
	protected $amazon_valid_country_codes = array(
		'CA', 'DE', 'FR', 'JP', 'UK', 'US'
	);
	
	/**
	 * the international amazon product page urls
	 */
	protected $amazon_url = array(
		'CA'	=> 'http://www.amazon.ca/exec/obidos/ASIN/%s/%s',
		'DE'	=> 'http://www.amazon.de/exec/obidos/ASIN/%s/%s',
		'FR'	=> 'http://www.amazon.fr/exec/obidos/ASIN/%s/%s',
		'JP'	=> 'http://www.amazon.jp/exec/obidos/ASIN/%s/%s',
		'UK'	=> 'http://www.amazon.co.uk/exec/obidos/ASIN/%s/%s',
		'US'	=> 'http://www.amazon.com/exec/obidos/ASIN/%s/%s',
	);
	
	/**
	 * available template placeholders
	 */
	protected $tpl_placeholder = array(
		'SmallImageUrl',
		'SmallImageWidth',
		'SmallImageHeight',
		'MediumImageUrl',
		'MediumImageWidth',
		'MediumImageHeight',
		'LargeImageUrl',
		'LargeImageWidth',
		'LargeImageHeight',
		'Label',
		'Manufacturer',
		'Publisher',
		'Title',
		'AmazonUrl',
		'TotalOffers',
		'LowestOfferPrice',
		'LowestOfferCurrency',
		'AmazonPrice',
		'AmazonCurrency',
		'AmazonAvailability',
		'AmazonLogoSmallUrl',
		'AmazonLogoLargeUrl',
		'DetailPageURL',
		'Platform'
	);
	
	/**
	 * my tracking id's which will be used if the user doesn't have one
	 * (for all my good programming work :)
	 */
	protected $my_tacking_id = array(
		'DE'	=> 'ichdigital-21',
		'UK'	=> 'ichdigitaluk-21',
		'US'	=> 'ichdigitalus-21'
	);
	
	/**
	 * template placeholder prefix
	 */
	protected $tpl_prefix = '{$';
	
	/**
	 * template placeholder postfix
	 */
	protected $tpl_postfix = '}';
	
	/**
	 * AmazonSimpleAdmin bb tag regex
	 */
	protected $bb_regex = '#\[asa(.*)\]([\w-]+)\[/asa\]#i';
	
	/**
	 * AmazonSimpleAdmin bb tag regex
	 */
	protected $bb_regex_collection = '#\[asa_collection(.*)\]([\w-]+)\[/asa_collection\]#i';	
	
	/**
	 * my Amazon Access Key ID
	 */
	protected $amazon_api_key_internal = '0TA14MJ6AS7KEC5KN582';
	
	/**
	 * user's Amazon Access Key ID
	 */
	protected $amazon_api_key;
	
	/**
	 * user's Amazon Tracking ID
	 */
	protected $amazon_tracking_id;
	
	/**
	 * selected country code
	 */
	protected $amazon_country_code;
	
	/**
	 * product preview status
	 */
	protected $product_preview;
	
	protected $task;
	
	/**
	 * wpdb object
	 */
	protected $db;
	
	/**
	 * collection object
	 */
	protected $collection;
	
	protected $error = array();
	protected $success = array();
	
	/**
	 * the amazon webservice object
	 */
	protected $amazon;
	
	
	
	/**
	 * constructor
	 */
	public function __construct ($wpdb) 
	{
		$libdir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib';
		set_include_path(get_include_path() . PATH_SEPARATOR . $libdir);
		
		if (isset($_GET['task'])) {
			$this->task = strip_tags($_GET['task']);
		}
		
		$this->db = $wpdb;
		
		require_once 'Zend/Service/Amazon.php';
		
		// Hook for adding admin menus
		add_action('admin_menu', array($this, 'createAdminMenu'));
		
		// Hook for adding content filter
		add_filter('the_content', array($this, 'parseContent'), 1);
		
		//wp_enqueue_script( 'listman' );
		
		$this->_getAmazonUserData();
		
		if ($this->product_preview == '1') {
			add_action('wp_footer', array($this, 'addProductPreview'));
		}
		
		$this->amazon = $this->connect();		
	}
	
	/**
	 * connects the amazon webservice
	 */
	protected function connect ()
	{
		$amazon_api_key = $this->amazon_api_key_internal;
		if (!empty($this->amazon_api_key)) {
			$amazon_api_key = $this->amazon_api_key;
		}
		
		$country_code = null;
		if (!empty($this->amazon_country_code)) {
			$country_code = $this->amazon_country_code;
		}

		try {							
			if ($country_code !== null) {
				$amazon = new Zend_Service_Amazon($amazon_api_key, $country_code);
			} else {
				$amazon = new Zend_Service_Amazon($amazon_api_key);
			}					
			return $amazon;		
		} catch (Exception $e) {			
			//echo $e->getMessage();
			return null;
		}
	}
	
	/**
	 * action function for above hook
	 *
	 */
	public function createAdminMenu () 
	{   		
		// Add a new submenu under Options:
	    add_options_page('AmazonSimpleAdmin', 'AmazonSimpleAdmin', 8, dirname(__FILE__), array($this, 'createOptionsPage'));
	    add_action('admin_head', array($this, 'getOptionsHead'));
	    wp_enqueue_script( 'listman' );
	}
	
	/**
	 * creates the AmazonSimpleAdmin admin page
	 *
	 */
	public function createOptionsPage () 
	{	
		echo '<h2>AmazonSimpleAdmin</h2>';
				
		echo $this->getTabMenu($this->task);
		
		$this->_displayDispatcher($this->task);
	}
	
	/**
	 * 
	 */
	protected function getTabMenu ($task)
	{
		
		$nav  = '<ul id="asa_navigation">';
		$nav .= '<li><a href="'. $this->plugin_url .'"'. (($task == null) ? 'class="active"' : '') .'>Setup</a></li>';
		$nav .= '<li><a href="'. $this->plugin_url .'&task=collections"'. (($task == 'collections') ? 'class="active"' : '') .'>Collections</a></li>';
		$nav .= '<li><a href="'. $this->plugin_url .'&task=usage"'. (($task == 'usage') ? 'class="active"' : '') .'>Usage</a></li>';
		$nav .= '</ul>';
		
		return $nav;
	}
	
	/**
	 * the actual options page content
	 *
	 */
	protected function _displayDispatcher ($task) 
	{
		switch ($task) {
			
			case 'collections':
				
				require_once(dirname(__FILE__) . '/AsaCollection.php');
				$this->collection = new AsaCollection($this->db);

				$params = array();
				
				if (isset($_POST['submit_new_asin'])) {
					
					$asin 			= strip_tags($_POST['new_asin']);
					$collection_id 	= strip_tags($_POST['collection']);
					$item			= $this->_getItem($asin); 
					
					if ($item === null) {						
						// invalid asin
						$this->error['submit_new_asin'] = 'invalid ASIN';
						
					} else if ($this->collection->checkAsin($asin, $collection_id) !== null) {
						// asin already added to this collection
						$this->error['submit_new_asin'] = 'ASIN already added to collection <strong>'. 
							$this->collection->getLabel($collection_id) . '</strong>';
						
					} else {
						
						if ($this->collection->addAsin($asin, $collection_id) === true) {
							$this->success['submit_new_asin'] = '<strong>'. $item->Title . 
								'</strong> added to collection <strong>'. 
							$this->collection->getLabel($collection_id) . '</strong>';
						}
					}
					
				} else if (isset($_POST['submit_manage_collection'])) {
					
					$collection_id = strip_tags($_POST['select_manage_collection']);
					
					$params['collection_items'] = $this->collection->getItems($collection_id);
					$params['collection_id'] 	= $collection_id;

				} else if (isset($_GET['select_manage_collection']) && isset($_GET['update_timestamp'])) {
					
					$item_id = strip_tags($_GET['update_timestamp']);
					$this->collection->updateItemTimestamp($item_id);
					
					$collection_id = strip_tags($_GET['select_manage_collection']);
					$params['collection_items'] = $this->collection->getItems($collection_id);
					$params['collection_id'] 	= $collection_id;
					
				} else if (isset($_POST['submit_delete_collection'])) {
					
					$collection_id = strip_tags($_POST['select_manage_collection']);
					$collection_label = $this->collection->getLabel($collection_id);
					
					if ($collection_label !== null) {
						$this->collection->delete($collection_id);
					}
					
					$this->success['manage_collection'] = 'collection deleted: <strong>'. 
						$collection_label . '</strong>';
					
				} else if (isset($_POST['submit_new_collection'])) {
					
					$collection_label = strip_tags($_POST['new_collection']);
					
					if (empty($collection_label)) {
						$this->error['submit_new_collection'] = 'Invalid collection label';
					} else {
						if ($this->collection->create($collection_label) == true) {
							$this->success['submit_new_collection'] = 'New collection '.
								'<strong>'. $collection_label . '</strong> created';				
						} else {
							$this->error['submit_new_collection'] = 'This collection already exists';
						}
					}
				
				} else if (isset($_POST['submit_collection_init']) && 
					isset($_POST['activate_collections'])) {

					$this->collection->initDB();
				}
				
				//var_dump($this->db->get_var("SHOW TABLES LIKE '%asa_collection%'"));
				if ($this->db->get_var("SHOW TABLES LIKE '%asa_collection%'") === null) {
					$this->_displayCollectionsSetup();
				} else {
					$this->_displayCollectionsPage($params);
				}
				break;
				
			case 'usage':
				
				$this->_displayUsagePage();
				break;
				
			default:
				
				if (count($_POST) > 0) {
			
					$_asa_amazon_api_key 		= strip_tags($_POST['_asa_amazon_api_key']);
					$_asa_amazon_tracking_id 	= strip_tags($_POST['_asa_amazon_tracking_id']);					
					$_asa_product_preview		= strip_tags($_POST['_asa_product_preview']);
		
					update_option('_asa_amazon_api_key', $_asa_amazon_api_key);
					update_option('_asa_amazon_tracking_id', $_asa_amazon_tracking_id);
					update_option('_asa_product_preview', $_asa_product_preview);
					
					if (isset($_POST['_asa_amazon_country_code'])) {
						$_asa_amazon_country_code 	= strip_tags($_POST['_asa_amazon_country_code']);						
						if ($_asa_amazon_country_code == '0') {
							$_asa_amazon_country_code = '';
						}
						update_option('_asa_amazon_country_code', $_asa_amazon_country_code);
					}				
				}
				
				$this->_displaySetupPage();
		}
	}
	
	/**
	 * collections setup screen
	 *
	 */
	protected function _displayCollectionsSetup ()	 
	{	
		?>		
		<div id="asa_collections_setup" class="wrap">
		<fieldset class="options">
		<h2><?php _e('Collections') ?></h2>
		
		<p>Do you want to activate the AmazonSimpleAdmin collections feature?</p>
		<form name="form_collection_init" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
		<label for="activate_collections">yes</label>
		<input type="checkbox" name="activate_collections" id="activate_collections" value="1">
		<p class="submit" style="margin:0; display: inline;">
			<input type="submit" name="submit_collection_init" value="activate" />
		</p>
		</form>
		
		<?php
	}
	
	/**
	 * the actual options page content
	 *
	 */
	protected function _displayCollectionsPage ($params) 
	{
		extract($params);
				
		?>		
		<div id="asa_collections" class="wrap">
		<fieldset class="options">
		<h2><?php _e('Collections') ?></h2>
		
		<h3>Create new collection</h3>
		<?php
		if ($this->error['submit_new_collection']) {
			$this->_displayError($this->error['submit_new_collection']);	
		} else if ($this->success['submit_new_collection']) {
			$this->_displaySuccess($this->success['submit_new_collection']);	
		}
		?>
		
		<form name="form_new_collection" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
		<label for="new_collection">New collection:</label>
		<input type="text" name="new_collection" id="new_collection" />
		
		<p class="submit" style="margin:0; display: inline;">
			<input type="submit" name="submit_new_collection" value="save" />
		</p>
		</form>
		
		<h3>Add to collection</h3>
		<?php
		if ($this->error['submit_new_asin']) {
			$this->_displayError($this->error['submit_new_asin']);	
		} else if ($this->success['submit_new_asin']) {
			$this->_displaySuccess($this->success['submit_new_asin']);	
		}
		?>
		<form name="form_new_asin" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
		<label for="new_asin"><img src="<?=bloginfo('url')?><?=$this->plugin_dir?>/img/misc_add_small.gif" /> Add Amazon item (ASIN):</label>
		<input type="text" name="new_asin" id="new_asin" />
		<label for="collection">to collection:</label>
		
		<?php
		echo $this->collection->getSelectField('collection', $collection_id);
		?>
		
		<p class="submit" style="margin:0; display: inline;">
			<input type="submit" name="submit_new_asin" value="save" />
		</p>
		</form>
		
		<a name="manage_collection"></a>
		<h3>Manage collections</h3>
		<?php
		if ($this->error['manage_collection']) {
			$this->_displayError($this->error['manage_collection']);	
		} else if ($this->success['manage_collection']) {
			$this->_displaySuccess($this->success['manage_collection']);	
		}
		?>
		<form name="manage_colection" action="<?php echo $this->plugin_url .'&task=collections'; ?>#manage_collection" method="post">
		<label for="select_manage_collection">Collection:</label>
		
		<?php
		echo $this->collection->getSelectField('select_manage_collection', $collection_id);
		?>

		<p class="submit" style="margin:0; display: inline;">
			<input type="submit" name="submit_manage_collection" value="browse" />
		</p>
		<p class="submit" style="margin:0; display: inline;">
			<input type="submit" name="submit_delete_collection" value="delete" onclick="return asa_confirm_delete_collection();" />
		</p>
		</form>
		
		<?php
		if ($collection_items) {
			
			$table = '';
			
			$table .= '<table class="widefat"><thead><tr>';
			$table .= '<th scope="col" width="[thumb_width]"></th>';
			$table .= '<th scope="col" width="120">ASIN</th>';
			$table .= '<th scope="col">'. __('Title') .'</th>';
			$table .= '<th scope="col" width="160">'. __('Timestamp') . '</th>';
			$table .= '<th scope="col"></th>';
			$table .= '<th scope="col"></th>';
			$table .= '</tr></thead>';
			$table .= '<tbody id="the-list">';
			
			$thumb_max_width = array();
			
			for ($i=0;$i<count($collection_items);$i++) {
				
				$row = $collection_items[$i];
				$item = $this->_getItem((string) $row->collection_item_asin);
				
				if ($item === null) {
					continue;	
				}
				if ($i%2==0) {
					$tr_class ='';
				} else {
					$tr_class = ' class="alternate"';
				}
				
				$table .= '<tr id="collection_item_'. $row->collection_item_id .'"'.$tr_class.'>';
				
				$table .= '<td width="[thumb_width]"><a href="'. $item->DetailPageURL .'" target="_blank"><img src="'. $item->SmallImage->Url->getUri() .'" /></a></td>';
				$table .= '<td width="120">'. $row->collection_item_asin .'</td>';
				$table .= '<td><span id="">'. $item->Title .'</span></td>';
				$table .= '<td width="160">'. date(str_replace(' \<\b\r \/\>', ',', __('Y-m-d \<\b\r \/\> g:i:s a')), $row->timestamp) .'</td>';				
				$table .= '<td><a href="'. $this->plugin_url .'&task=collections&update_timestamp='. $row->collection_item_id .'&select_manage_collection='. $collection_id .'" class="edit" onclick="return asa_set_latest('. $row->collection_item_id .', \'Set timestamp of &quot;'. htmlspecialchars($item->Title) .'&quot; to actual time?\');" title="update timestamp">latest</a></td>';
				$table .= '<td><a href="javascript:void(0);" class="delete" onclick="asa_deleteSomething(\'collection_item\', '. $row->collection_item_id .', \'delete &quot;'. htmlspecialchars($item->Title) .'&quot; from collection?\');">delete</a></td>';
				$table .= '</tr>';
				
				$thumb_max_width[] = $item->SmallImage->Width;
			}
			
			rsort($thumb_max_width);			
									
			$table .= '</tbody></table>';
			
			$search = array(
				'/\[thumb_width\]/',
			);
			
			$replace = array(
				$thumb_max_width[0],
			);
			
			echo preg_replace($search, $replace, $table);
			echo '<div id="ajax-response"></div>';
		
		} else if (isset($collection_id)) {
			echo '<p>Nothing found. Add some products.</p>';
		}
		?>
		
		</fieldset>
		</div>
		<?php
	}
	
	/**
	 * the actual options page content
	 *
	 */
	protected function _displayUsagePage () 
	{
		?>		
		<div id="asa_setup" class="wrap">
		<fieldset class="options">
		<h2><?php _e('Usage') ?></h2>
		
		<p>On the plugin's homepage you can find a more <a href="http://www.ichdigital.de/amazonsimpleadmin-documentation/" target="_blank">detailed documentation</a>.</p>
		<h3>Tags</h3>
			<p><?php _e('To embed products from Amazon into your post with AmazonSimpleAdmin, easily use tags like this:') ?></p>
			<p><strong>[asa]ASIN[/asa]</strong> where ASIN is the Amazon ASIN number you can find on each product's site, like: <strong>[asa]B000EWN5JM[/asa]</strong></p>
			<p><?php _e('Furthermmore you can declare an individual template file within the first [asa] tag, like:') ?></p>
			<p><strong>[asa mytemplate]ASIN[/asa]</strong> (notice the space after asa!)</p>
			<p><?php _e('You can create multiple template files and put them into the "tpl" folder in the AmazonSimpleAdmin plugin directory. Template files are simple HTML files with placeholders. See the documentation for more info. Template files must have the extension ".htm". Use the filename without ".htm" for declaration within the [asa] tag. If you do not declare a template file, AmazonSimpleAdmin uses the default template (tpl/default.htm).') ?></p>
			<p><?php _e('For embedding a whole collection of Amazon products into your post, use the collection tags:');?></p>
			<p><?php _e('<strong>[asa_collection]my_collection[/asa_collection]</strong> where "my_collection" between the tags stands for the collection label you have created in the collections section.');?></p>
			<p><?php _e('Like with the simple ASIN tags before, you can also use templates for collections. Declare your template file in the asa_collection tag, like this: <strong>[asa_collection my_template]my_collection[/asa_collection]</strong>');?></p>
			
		<h3>Functions</h3>
		
		<p><?php _e('AmazonSimpleAdmin features the following functions, which can be used in your sidebar file:') ?></p>
		<ul>
		<li>string <strong>asa_collection</strong> ($label [, string [$type], string [$tpl]])<br />
		<em>label</em> is mandatory and stands for the collection label<br />
		<em>type</em> is optional. "all" lists all collection items sorted by time of adding whereas "latest" only displays the latest added item. Default is "all"<br />
		<em>tpl</em>  is optional. Here you can define your own template file. Default is "collection_sidebar_default".
		</li>
		</ul>
		
		<h3>Templates</h3>
				
		<p><?php _e('Available templates in your tpl folder are:') ?></p>
		<ul>
		<?php
		$tpl_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl';

		if (is_dir($tpl_dir)) {
		    if ($dh = opendir($tpl_dir)) {
		        while (($file = readdir($dh)) !== false) {
		            if (!is_dir($file) && $file != '.' && $file != '..') {
		            	$info = pathinfo($file);
		            	if ($info['extension'] == 'htm') {
		            		echo '<li>'. basename($info['basename'], '.htm') .'</li>';
		            	}
		            }
		        }
		        closedir($dh);
		    }
		}
		?>
		</ul>
		</fieldset>
		</div>
		<?php
	}
	
	/**
	 * the actual options page content
	 *
	 */
	protected function _displaySetupPage () 
	{	
		$_asa_status = false;
		
		$this->_getAmazonUserData();
			
		try {
			$this->amazon = $this->connect();
			$this->amazon->itemSearch(array('SearchIndex' => 'Books', 'Keywords' => 'php'));
			$_asa_status = true;
		} catch (Exception $e) {
			$_asa_error = $e->getMessage();
		}
		?>
		<div id="asa_setup" class="wrap">
		<form method="post">
		<fieldset class="options">
		<h2><?php _e('Setup') ?></h2>
		
		<p><span id="_asa_status_label">Status:</span> <?php echo ($_asa_status == true) ? '<span class="_asa_status_ready">Ready</span>' : '<span class="_asa_status_not_ready">Not Ready</span>'; ?></p>
		<?php
		if (!empty($_asa_error)) {
			echo '<p><strong>Error:</strong> '. $_asa_error . '</p>';	
		}
		?>
				
		<label for="_asa_amazon_tracking_id"><?php _e('Your Amazon Tracking ID:') ?></label>
		<input type="text" name="_asa_amazon_tracking_id" id="_asa_amazon_tracking_id" value="<?php echo (!empty($this->amazon_tracking_id)) ? $this->amazon_tracking_id : ''; ?>" />
		<br />	
		<label for="_asa_amazon_country_code"><?php _e('Your Amazon Country Code:') ?></label>
		<select name="_asa_amazon_country_code">
			<option value="0">-select-</option>
			<?php
			foreach ($this->amazon_valid_country_codes as $code) {
				if ($code == $this->amazon_country_code) {
					$selected = ' selected="selected"'; 	
				} else {
					$selected = '';
				}
				echo '<option value="'. $code .'"'.$selected.'>' . $code . '</option>';	
			}
			?>
		</select> (Default: US)<br /><br />
		
		<p><?php _e('If you want you can use your own Amazon Access Key ID:') ?></p>
		<label for="_asa_amazon_api_key"><?php _e('Your Amazon Access Key ID:') ?></label>
		<input type="text" name="_asa_amazon_api_key" id="_asa_amazon_api_key" value="<?php echo (!empty($this->amazon_api_key)) ? $this->amazon_api_key : ''; ?>" />
		<a href="http://aws.amazon.com/" target="_blank">get one</a>
		
		<br /><br />
		<p>Product preview layers are only supported by US, UK and DE so far. This can effect the site to be loaded a bit slower due to link parsing.</p>
		<label for="_asa_product_preview"><?php _e('Enable product preview links:') ?></label>
		<input type="checkbox" name="_asa_product_preview" id="_asa_product_preview" value="1"<?php echo (!empty($this->product_preview) ? 'checked="checked"' : '') ?> />
		
	
		<p class="submit">
		<input type="submit" name="info_update" value="<?php _e('Update Options') ?> &raquo;" />
		</p>
		
		</fieldset>
		</form>
		</div>		
		<?php
	}	
	
	/**
	 * 
	 */
	protected function _displayError ($error) 
	{
		echo '<p><span class="_asa_error_label">Error:</span> '. $error .'</p>';	
	}
	
	/**
	 * 
	 */
	protected function _displaySuccess ($success) 
	{
		echo '<p><span class="_asa_success_label">Success:</span> '. $success .'</p>';	
	}	
	
	/**
	 * parses post content
	 * 
	 * @param 		string		post content
	 * @return 		string		parsed content
	 */
	public function parseContent ($content)
	{
		$matches 		= array();
		$matches_coll 	= array();
		
		preg_match_all($this->bb_regex, $content, $matches);
		
		if ($matches && count($matches[0]) > 0) {
			
			$tpl_src		= file_get_contents(dirname(__FILE__) .'/tpl/default.htm');									

			for ($i=0; $i<count($matches[0]); $i++) {
				
				$match 		= $matches[0][$i];
				$tpl_file	= strip_tags(trim($matches[1][$i]));
				$asin 		= $matches[2][$i];
				
				$tpl 		= $tpl_src;

				if (!empty($tpl_file) && 
					file_exists(dirname(__FILE__) .'/tpl/'. $tpl_file .'.htm')) {
					$tpl = file_get_contents(dirname(__FILE__) .'/tpl/'. $tpl_file .'.htm');	
				}
				
				if (!empty($asin)) {
									
					$content = str_replace($match, $this->_parseTpl($asin, $tpl), $content);
				}				
			}
		}
		
		preg_match_all($this->bb_regex_collection, $content, $matches_coll);
		
		if ($matches_coll && count($matches_coll[0]) > 0) {
			
			$tpl_src		= file_get_contents(dirname(__FILE__) .'/tpl/default.htm');									

			for ($i=0; $i<count($matches_coll[0]); $i++) {
				
				$match 		= $matches_coll[0][$i];
				$tpl_file	= strip_tags(trim($matches_coll[1][$i]));
				$coll_label	= $matches_coll[2][$i];
				
				$tpl 		= $tpl_src;

				if (!empty($tpl_file) && 
					file_exists(dirname(__FILE__) .'/tpl/'. $tpl_file .'.htm')) {
					$tpl = file_get_contents(dirname(__FILE__) .'/tpl/'. $tpl_file .'.htm');	
				}
				
				if (!empty($coll_label)) {
					
					require_once(dirname(__FILE__) . '/AsaCollection.php');
					$this->collection = new AsaCollection($this->db);
					
					$collection_id = $this->collection->getId($coll_label);
					
					$coll_items = $this->collection->getItems($collection_id);
					if (count($coll_items) == 0) {
						$content = str_replace($match, '', $content);
					} else {
						
						$coll_html = '';
						foreach ($coll_items as $row) {
							$coll_html .= $this->_parseTpl($row->collection_item_asin, $tpl);
						}
						$content = str_replace($match, $coll_html, $content);
					}					
				}				
			}
		}
		
		return $content;
	}
	
	/**
	 * parses the choosen template
	 * 
	 * @param 	string		amazon asin
	 * @param 	string		the template contents
	 * 
	 * @return 	string		the parsed template
	 */
	protected function _parseTpl ($asin, $tpl)
	{
		// get the item data
		$item = $this->_getItem($asin);
		
		if ($item === null) {
			
			return '';
		
		} else {
			
			$search = $this->_getTplPlaceholders(true);
			
			$lowestOfferPrice = null;
			$amazonPrice = $item->Offers->Offers[0]->Price;
			
			$tracking_id 	= ''; 
			
			if (!empty($this->amazon_tracking_id)) {
				// use the user's tracking id
				$tracking_id = $this->amazon_tracking_id;
			} else {
				// otherwise use mine (for all my good programming work :)
				if (empty($this->amazon_country_code)) {
					$tracking_id = $this->my_tacking_id['US'];
				} else {
					$tracking_id = $this->my_tacking_id[$this->amazon_country_code];
				}
			}
			
			if ($item->Offers->LowestUsedPrice && $item->Offers->LowestNewPrice) {
				$lowestOfferPrice = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
					$item->Offers->LowestUsedPrice : $item->Offers->LowestNewPrice;
				$lowestOfferCurrency = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
					$item->Offers->LowestUsedPriceCurrency : $item->Offers->LowestNewPriceCurrency;
			} else if ($item->Offers->LowestNewPrice) {
				$lowestOfferPrice = $item->Offers->LowestNewPrice;
				$lowestOfferCurrency = $item->Offers->LowestNewPriceCurrency;
			} else if ($item->Offers->LowestUsedPrice) {
				$lowestOfferPrice = $item->Offers->LowestUsedPrice;
				$lowestOfferCurrency = $item->Offers->LowestUsedPriceCurrency;
			}
			
			$lowestOfferPrice = $this->_formatPrice($lowestOfferPrice);
			$amazonPrice = $this->_formatPrice($amazonPrice);
			
			$totalOffers = $item->Offers->TotalNew + $item->Offers->TotalUsed + 
				$item->Offers->TotalCollectible + $item->Offers->TotalRefurbished;
				
			if (empty($this->amazon_country_code)) {
				$amazon_url = sprintf($this->amazon_url['US'], 
					$item->ASIN, $tracking_id);
			} else {
				$amazon_url = sprintf($this->amazon_url[$this->amazon_country_code], 
					$item->ASIN, $tracking_id);
			}
			
			$platform = $item->Platform;
			if (is_array($platform)) {
				$platform = implode(', ', $platform);
			}
			
			$replace = array(
				$item->SmallImage->Url->getUri(),
				$item->SmallImage->Width,
				$item->SmallImage->Height,
				$item->MediumImage->Url->getUri(),
				$item->MediumImage->Width,
				$item->MediumImage->Height,
				$item->LargeImage->Url->getUri(),
				$item->LargeImage->Width,
				$item->LargeImage->Height,
				$item->Label,
				$item->Manufacturer,
				$item->Publisher,
				$item->Title,
				$amazon_url,
				empty($totalOffers) ? '0' : $totalOffers,
				empty($lowestOfferPrice) ? '---' : $lowestOfferPrice,
				$lowestOfferCurrency,
				empty($amazonPrice) ? '---' : $amazonPrice,
				$item->Offers->Offers[0]->CurrencyCode,
				$item->Offers->Offers[0]->Availability,
				get_bloginfo('wpurl') . $this->plugin_dir . '/img/amazon_' . 
					(empty($this->amazon_country_code) ? 'US' : $this->amazon_country_code) .'_small.gif',
				get_bloginfo('wpurl') . $this->plugin_dir . '/img/amazon_' . 
					(empty($this->amazon_country_code) ? 'US' : $this->amazon_country_code) .'.gif', 
				$item->DetailPageURL,
				$platform
			);
			
			return preg_replace($search, $replace, $tpl);									
		}
	}
	
	/**
	 * get item information from amazon webservice
	 * 
	 * @param		string		ASIN
	 * @return 		object		Zend_Service_Amazon_Item object
	 */	
	protected function _getItem ($asin)
	{
		try {
			$item = $this->amazon->itemLookup($asin, array(
				'ResponseGroup' => 'ItemAttributes,Images,Offers'));
			return $item;
		} catch (Exception $e) {			
			return null;
		}		
	}
	
	/**
	 * gets options from database options table
	 */
	protected function _getAmazonUserData ()
	{
		$this->amazon_api_key 		= get_option('_asa_amazon_api_key');
		$this->amazon_tracking_id 	= get_option('_asa_amazon_tracking_id');
		$this->amazon_country_code 	= get_option('_asa_amazon_country_code');
		$this->product_preview 		= get_option('_asa_product_preview');					
	}
	
	/**
	 * generates right placeholder format and returns them as array
	 * optionally prepared for use as regex
	 * 
	 * @param 		bool		true for regex prepared
	 */
	protected function _getTplPlaceholders ($regex=false)
	{
		$placeholders = array();
		foreach ($this->tpl_placeholder as $ph) {
			$placeholders[] = $this->tpl_prefix . $ph . $this->tpl_postfix;
		}
		if ($regex == true) {
			return array_map(array($this, 'TplPlaceholderToRegex'), $placeholders);
		}
		return $placeholders;
	}
	
	/**
	 * excapes placeholder for regex usage
	 * 
	 * @param 		string		placehoder
	 * @return 		string		escaped placeholder
	 */
	public function TplPlaceholderToRegex ($ph)
	{
		$search = array(
			'{',
			'}',
			'$'
		);
		
		$replace = array(
			'\{',
			'\}',
			'\$'
		);
		
		$ph = str_replace($search, $replace, $ph);
		
		return '/'. $ph .'/';
	}
	
	/**
	 * formats the price value from amazon webservice
	 * 
	 * @param 		string		price
	 * @return 		mixed		price (float, int for JP)
	 */
	protected function _formatPrice ($price)
	{
		if ($price === null) {
			return $price;
		}
		
		if ($this->amazon_country_code != 'JP') {
			$price = (float) substr_replace($price, '.', (strlen($price)-2), -2);
		} else {
			$price = intval($price);
		}	
		
		$dec_point 		= '.';
		$thousands_sep 	= ',';
		
		if ($this->amazon_country_code == 'DE' ||
			$this->amazon_country_code == 'FR') {
			// taken the amazon websites as example
			$dec_point 		= ',';
			$thousands_sep 	= '.';
		}
		
		if ($this->amazon_country_code != 'JP') {
			$price = number_format($price, 2, $dec_point, $thousands_sep);
		} else {
			$price = number_format($price, 0, $dec_point, $thousands_sep);
		}
		return $price;
	}
	
	/**
	 * includes the css file for admin page
	 */
	public function getOptionsHead ()
	{
		echo '<link rel="stylesheet" type="text/css" media="screen" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/amazonsimpleadmin/css/options.css" />';
		echo '<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/amazonsimpleadmin/js/asa.js"></script>';
	}
	
	/**
	 * enabled amazon product preview layers
	 */
	public function addProductPreview ()
	{
		$js = '<script type="text/javascript" src="http://www.assoc-amazon.[domain]/s/link-enhancer?tag=[tag]&o=[o_id]"></script>';
		$js .= '<noscript><img src="http://www.assoc-amazon.[domain]/s/noscript?tag=[tag]" alt="" /></noscript>';
		
		$search = array(
			'/\[domain\]/',
			'/\[tag\]/',
			'/\[o_id\]/',
		);		
		
		switch ($this->amazon_country_code) {
			
			case 'DE':
				$replace = array(
					'de',
					(!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : 
						$this->my_tacking_id['DE']),
					'3'
				);				
				$js = preg_replace($search, $replace, $js);
				break;
				
			case 'UK':
				$replace = array(
					'co.uk',
					(!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : 
						$this->my_tacking_id['UK']),
					'2'
				);				
				$js = preg_replace($search, $replace, $js);
				break;
				
			case 'US':
			case false:
				$replace = array(
					'com',
					(!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : 
						$this->my_tacking_id['US']),
					'1'
				);
				
				$js = preg_replace($search, $replace, $js);
				break;

			default:
				$js = '';
		}
		
		echo $js . "\n";	
	}
	
	
		
	/**
	 * 
	 */
	public function getCollection ($label, $type=false, $tpl=false)
	{	
		$collection_html = '';
		
		$sql = '
			SELECT a.collection_item_asin as asin
			FROM `'. $this->db->prefix . self::DB_COLL_ITEM .'` a
			INNER JOIN `'. $this->db->prefix . self::DB_COLL .'` b USING(collection_id)
			WHERE b.collection_label = "'. $this->db->escape($label) .'"
			ORDER by a.collection_item_timestamp DESC
		';
		
		$result = $this->db->get_results($sql);
		
		if (count($result) == 0) {
			return $collection_html;	
		}
		
		if ($tpl == false) {
			$tpl = 'collection_sidebar_default';	
		}
		if ($type == false) {
			$type = 'all';	
		}
		
		$tpl_src = file_get_contents(dirname(__FILE__) .'/tpl/'. $tpl .'.htm');
		
		switch ($type) {
			
			case 'latest':
				$collection_html .= $this->_parseTpl($result[0]->asin, $tpl_src);
				break;
			
			case 'all':
			default:
				foreach ($result as $row) {
					$collection_html .= $this->_parseTpl($row->asin, $tpl_src);			
				}
				
		}
		
		return $collection_html;
	}
}


$asa = new AmazonSimpleAdmin($wpdb);


function asa_collection ($label, $type=false, $tpl=false)
{
	global $asa;
	echo $asa->getCollection($label, $type, $tpl);
}
?>