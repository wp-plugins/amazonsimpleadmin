<?php
class AmazonSimpleAdmin {
    
    const DB_COLL         = 'asa_collection';
    const DB_COLL_ITEM    = 'asa_collection_item';

    const VERSION = '0.9.13';
    
    /**
     * this plugins home directory
     */
    protected $plugin_dir = '/wp-content/plugins/amazonsimpleadmin';
    
    protected $plugin_url = 'options-general.php?page=amazonsimpleadmin/amazonsimpleadmin.php';
    
    /**
     * supported amazon country IDs
     */
    protected $_amazon_valid_country_codes = array(
        'CA', 'DE', 'FR', 'JP', 'UK', 'US', 'IT', 'ES', 'CN', 'IN'
    );
    
    /**
     * the international amazon product page urls
     */
    protected $amazon_url = array(
        'CA'    => 'http://www.amazon.ca/exec/obidos/ASIN/%s/%s',
        'DE'    => 'http://www.amazon.de/exec/obidos/ASIN/%s/%s',
        'FR'    => 'http://www.amazon.fr/exec/obidos/ASIN/%s/%s',
        'JP'    => 'http://www.amazon.jp/exec/obidos/ASIN/%s/%s',
        'UK'    => 'http://www.amazon.co.uk/exec/obidos/ASIN/%s/%s',
        'US'    => 'http://www.amazon.com/exec/obidos/ASIN/%s/%s',
        'IN'    => 'http://www.amazon.in/exec/obidos/ASIN/%s/%s',
        'IT'    => 'http://www.amazon.it/exec/obidos/ASIN/%s/%s',
        'ES'    => 'http://www.amazon.es/exec/obidos/ASIN/%s/%s',
        'CN'    => 'http://www.amazon.cn/exec/obidos/ASIN/%s/%s',
    );

    /**
     * @var string
     */
    protected $amazon_shop_url;

    /**
     * available template placeholders
     */
    protected $tpl_placeholder = array(
        'ASIN',
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
        'Studio',
        'Title',
        'AmazonUrl',
        'TotalOffers',
        'LowestOfferPrice',
        'LowestOfferCurrency',
        'LowestOfferFormattedPrice',
        'LowestNewPrice',
        'LowestNewOfferFormattedPrice',
        'LowestUsedPrice',
        'LowestUsedOfferFormattedPrice',
        'AmazonPrice',
        'AmazonPriceFormatted',
        'ListPriceFormatted',
        'AmazonCurrency',
        'AmazonAvailability',
        'AmazonLogoSmallUrl',
        'AmazonLogoLargeUrl',
        'DetailPageURL',
        'Platform',
        'ISBN',
        'EAN',
        'NumberOfPages',
        'ReleaseDate',
        'Binding',
        'Author',
        'Creator',
        'Edition',
        'AverageRating',
        'TotalReviews',
        'RatingStars',
        'RatingStarsSrc',
        'Director',
        'Actors',
        'RunningTime',
        'Format',
        'CustomRating',
        'ProductDescription',
        'AmazonDescription',
        'Artist',
        'Comment',
        'PercentageSaved',
        'Prime',
        'PrimePic',
        'ProductReviewsURL',
        'TrackingId',
        'AmazonShopURL',
        'SalePriceAmount',
        'SalePriceCurrencyCode',
        'SalePriceFormatted',
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
     * template dir
     */
    protected $tpl_dir = 'tpl';
    
    /**
     * AmazonSimpleAdmin bb tag regex
     */
    protected $bb_regex = '#\[asa(.[^\]]*|)\]([\w-]+)\[/asa\]#Usi';
    
    /**
     * AmazonSimpleAdmin bb tag regex
     */
    protected $bb_regex_collection = '#\[asa_collection(.[^\]]*|)\]([\w-\s]+)\[/asa_collection\]#Usi';
    
    /**
     * param separator regex
     */
    protected $_regex_param_separator = '/(,)(?=(?:[^"]|"[^"]*")*$)/m';    
    
    /**
     * my Amazon Access Key ID
     */
    protected $amazon_api_key_internal = '';
    
    /**
     * user's Amazon Access Key ID
     */
    protected $_amazon_api_key;
    
    /**
     * user's Amazon Access Key ID
     * @var string
     */
    protected $_amazon_api_secret_key = '';    
    
    /**
     * user's Amazon Tracking ID
     */
    protected $amazon_tracking_id;
    
    /**
     * selected country code
     */
    protected $_amazon_country_code = 'US';
    
    /**
     * product preview status
     * @var bool
     */
    protected $_product_preview = false;
    
    /**
     * product preview status
     * @var bool
     */
    protected $_parse_comments = false;

    /**
     * use AJAX
     * @var bool
     */
    protected $_async_load = false;

    /**
     * use only amazon prices for placeholder $AmazonPrice
     * @var bool
     */
    protected $_asa_use_amazon_price_only = false;
    
    /**
     * internal param delimiter
     * @var string
     */
    protected $_internal_param_delimit = '[#asa_param_delim#]';
    
    /**
     * 
     * @var string
     */
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
     * the cache object
     */
    protected $cache;

    /**
     * @var Asa_Debugger
     */
    protected $_debugger;

    /**
     * @var debugger error message
     */
    protected $_debugger_error;
        
    
    /**
     * constructor
     */
    public function __construct ($wpdb) 
    {
        $libdir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib';
        set_include_path(get_include_path() . PATH_SEPARATOR . $libdir);

        require_once dirname(__FILE__) . '/AsaFunctions.php';
        require_once 'AsaZend/Uri/Http.php';
        require_once 'AsaZend/Service/Amazon.php';
        require_once 'AsaZend/Service/Amazon/Accessories.php';
        require_once 'AsaZend/Service/Amazon/EditorialReview.php';
        require_once 'AsaZend/Service/Amazon/Image.php';
        require_once 'AsaZend/Service/Amazon/Item.php';        
        require_once 'AsaZend/Service/Amazon/ListmaniaList.php';       
        require_once 'AsaZend/Service/Amazon/Offer.php';
        require_once 'AsaZend/Service/Amazon/OfferSet.php';
        require_once 'AsaZend/Service/Amazon/Query.php';
        require_once 'AsaZend/Service/Amazon/ResultSet.php';
        require_once 'AsaZend/Service/Amazon/SimilarProduct.php';
        require_once dirname(__FILE__) . '/AsaWidget.php';

        if ($this->isDebug()) {
            $this->_initDebugger();
        }
        
        if (isset($_GET['task'])) {
            $this->task = strip_tags($_GET['task']);
        }
        
        $this->tpl_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->tpl_dir . DIRECTORY_SEPARATOR;
        
        $this->db = $wpdb;
        
        $this->cache = $this->_initCache();

        // init translation
        load_plugin_textdomain('asa1', false, 'amazonsimpleadmin/lang');
                
        // Hook for adding admin menus
        add_action('admin_menu', array($this, 'createAdminMenu'));
        
        // Hook for adding content filter
        add_filter('the_content', array($this, 'parseContent'), 1);
        add_filter('the_excerpt', array($this, 'parseContent'), 1);
        add_filter('widget_text', array($this, 'parseContent'), 1);
        
        // register shortcode handler for [asa] tags
        add_shortcode( 'asa', 'asa_shortcode_handler' );

        if (!get_option('_asa_hide_meta_link')) {
            add_action('wp_meta', array($this, 'addMetaLink'));
        }
        
        $this->_getAmazonUserData();
        $this->_loadOptions();
                
        if ($this->_parse_comments == true) {
            // Hook for adding content filter for user comments
            // Feature request from Sebastian Steinfort
            add_filter('comment_text', array($this, 'parseContent'), 1);
        }
        
        if ($this->_product_preview == true) {
            add_action('wp_footer', array($this, 'addProductPreview'));
        }
        
        add_filter('upgrader_pre_install', array($this, 'onPreInstall'), 10, 2);
        add_filter('upgrader_post_install', array($this, 'onPostInstall'), 10, 2);

        $this->amazon = $this->connect();        
    }
        
    /**
     * Called before installation / upgrade
     * 
     */
    public function onPreInstall()
    {
        try {
            $this->backupTemplates();
        } catch (Exception $e) {
            
        }
    }
    
    /**
     * Called after installation / upgrade
     * 
     */
    public function onPostInstall()
    {
        try {
            $this->restoreTemplates();
        } catch (Exception $e) {
            
        }
    }
    
    /**
     * Backups the template files
     * 
     */
    public function backupTemplates()
    {
        $dirIt = new DirectoryIterator($this->tpl_dir);
        
        $custom_tpl = array();
        foreach ($dirIt as $fileinfo) {
            
            if ($fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            $custom_tpl[] = $fileinfo->getFilename();
        }
        
        if (count($custom_tpl) > 0) {
            $backup_destination = $this->_getBackupDestination();
            mkdir($backup_destination);
            
            foreach($custom_tpl as $tpl_file) {
                
                $tpl_source_file = $this->tpl_dir . $tpl_file;
                $tpl_destination_file = $backup_destination . $tpl_file;
                
                $cp = copy($tpl_source_file, $tpl_destination_file);
                
                if ($cp == false) {
                    $tpl_data = file_get_contents($tpl_source_file);
                    $handle   = fopen($tpl_destination_file, 'w');
                    fwrite($handle, $tpl_data);
                    fclose($handle);
                }                
            }
        }
    }
    
    /**
     * Restores the template files
     * 
     */
    public function restoreTemplates()
    {
        $backup_destination = $this->_getBackupDestination();
        
        if (!is_dir($backup_destination)) {
            return false;
        }
        
        $dirIt = new DirectoryIterator($backup_destination);
        
        $custom_tpl = array();
        foreach ($dirIt as $fileinfo) {
            
            if ($fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            $custom_tpl[] = $fileinfo->getFilename();
        }
        
        if (count($custom_tpl) > 0) {
            
            foreach($custom_tpl as $tpl_file) {
                
                $tpl_source_file = $backup_destination . $tpl_file;
                $tpl_destination_file = $this->tpl_dir . $tpl_file;
                
                $cp = copy($tpl_source_file, $tpl_destination_file);
                
                if ($cp == false) {
                    $tpl_data = file_get_contents($tpl_source_file);
                    $handle   = fopen($tpl_destination_file, 'w');
                    fwrite($handle, $data);
                    fclose($handle);
                }

                unlink($tpl_source_file);
            }
        }

        rmdir($backup_destination);
    }    
    
    protected function _getBackupDestination()
    {
        $tmp = get_temp_dir() . 'amazonsimpleadmin_tpl_backup' . DIRECTORY_SEPARATOR;
        return $tmp;
    }
    
    /**
     * trys to connect to the amazon webservice
     */
    protected function connect ()
    {
        require_once 'Asa/Service/Amazon.php';
        
        try {                    
            $amazon = Asa_Service_Amazon::factory(
                $this->_amazon_api_key, 
                $this->_amazon_api_secret_key, 
                $this->amazon_tracking_id, 
                $this->_amazon_country_code
            );

            return $amazon;
                
        } catch (Exception $e) {
            if ($this->isDebug() && $this->_debugger != null) {
                $this->_debugger->write($e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * 
     */
    protected function _initCache ()
    {
        $_asa_cache_active    = get_option('_asa_cache_active');
        if (empty($_asa_cache_active)) {
            return null;
        }
        
        try {    
            
            require_once 'AsaZend/Cache.php';
            
            $_asa_cache_lifetime  = get_option('_asa_cache_lifetime');
            $_asa_cache_dir       = get_option('_asa_cache_dir');
            
            $current_cache_dir    = (!empty($_asa_cache_dir) ? $_asa_cache_dir : 'cache');
            
            $frontendOptions = array(
               'lifetime' => !empty($_asa_cache_lifetime) ? $_asa_cache_lifetime : 7200, // cache lifetime in seconds
               'automatic_serialization' => true
            );
            
            $backendOptions = array(
                'cache_dir' => dirname(__FILE__) . DIRECTORY_SEPARATOR . $current_cache_dir
            );
            
            // getting a AsaZend_Cache_Core object
            $cache = AsaZend_Cache::factory('Core', 'File', $frontendOptions,
                                         $backendOptions);                                         
           return $cache;
       } catch (Exception $e) {
              return null;
       }
    }
    
    /**
     * Determines if cache is working fine
     * @return bool
     */
    protected function _isCache()
    {
        $_asa_cache_dir    = get_option('_asa_cache_dir');
        $current_cache_dir = (!empty($_asa_cache_dir) ? $_asa_cache_dir : 'cache');
        
        if (get_option('_asa_cache_active') && 
            is_writable(dirname(__FILE__) . '/' . $current_cache_dir)) {
            return true;
        }
        
        return false;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return get_option('_asa_debug');
    }

    public function getDebugger()
    {
        return $this->_debugger;
    }

    /**
     * @return void
     */
    protected function _initDebugger()
    {
        require_once 'Asa/Debugger.php';
        try {
            $this->_debugger = Asa_Debugger::factory();
        } catch (Exception $e) {
            $this->_debugger_error = $e->getMessage();
        }
    }
    
    /**
     * action function for above hook
     *
     */
    public function createAdminMenu () 
    {           
        // Add a new submenu under Options:
        add_options_page('AmazonSimpleAdmin', 'AmazonSimpleAdmin', 'manage_options', 'amazonsimpleadmin/amazonsimpleadmin.php', array($this, 'createOptionsPage'));
        add_action('admin_head', array($this, 'getOptionsHead'));
        wp_enqueue_script( 'listman' );
    }
    
    /**
     * creates the AmazonSimpleAdmin admin page
     *
     */
    public function createOptionsPage () 
    {
        echo '<div id="amazonsimpleadmin-general" class="wrap">';
        screen_icon('asa');
        echo '<h2>AmazonSimpleAdmin</h2>';
                
        echo $this->getTabMenu($this->task);
        #echo '<div style="clear: both"></div>';
        echo '<div id="asa_content">';
        $this->_displayDispatcher($this->task);
        echo '</div>';
    }
    
    /**
     * 
     */
    protected function getTabMenu ($task)
    {
        $navItemFormat = '<a href="%s" class="nav-tab %s">%s</a>';

        $nav  = '<h2 class="nav-tab-wrapper">';
        $nav .= sprintf($navItemFormat, $this->plugin_url, (in_array($task, array(null, 'checkDonation'))) ? 'nav-tab-active' : '', __('Setup', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=options', (($task == 'options') ? 'nav-tab-active' : ''), __('Options', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=collections', (($task == 'collections') ? 'nav-tab-active' : ''), __('Collections', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=cache', (($task == 'cache') ? 'nav-tab-active' : ''), __('Cache', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=usage', (($task == 'usage') ? 'nav-tab-active' : ''), __('Usage', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=faq', (($task == 'faq') ? 'nav-tab-active' : ''), __('FAQ', 'asa1'));

        $nav .= '</h2><br />';
        return $nav;
    }
    
    /**
     * 
     * Enter description here ...
     * @param $task
     */
    protected function _getSubMenu ($task)
    {
        $_asa_donated = get_option('_asa_donated');
        
        $nav = '<div style="clear: both"></div>';
        
        if (empty($_asa_donated)) {
            $nav .= '<h3>'. __('Donation', 'asa1') .'</h3><div class="asa_info_box asa_info_box_outer">';

            $nav .= '<form name="form_paypal" id="form_paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">';
            $nav .= '<p style="margin: 0">'. sprintf( __('If you like this plugin and make some money with it, feel free to <a href="javascript:void(0)" onclick="%s">support me</a> so that I can keep up the updates!', 'asa1'), 'document.getElementById(\'form_paypal\').submit();') . ' :-)</p>';
            $nav .= '<input type="hidden" name="cmd" value="_s-xclick">
                <input type="image" src="'. asa_plugins_url( 'img/paypal.gif', __FILE__ ) .'" border="0" name="submit" style="vertical-align: middle">&nbsp;('. __('Thank you', 'asa1') . '!)
                <img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
                <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHPwYJKoZIhvcNAQcEoIIHMDCCBywCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYB4Gn/43sh7ivqcAZVoHAy0CR/W5URzhpr2X6s7UtG+LCSfECwRre+GVUnEjyK5VTEvXXOAusxprqMg3OO8hJm0zinh8IKLndybsWVdDnN/RQL/ddHffvY/znBzYZ3dHBCTjWjvnQDqfEqe0ixIdGeR/NixexTjOL2Je3aD585qWTELMAkGBSsOAwIaBQAwgbwGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIObfY9R61a/+AgZj1X57ukmmHlspczXa/l2mM0yZZLYRVU7c7vrPIi1ExGQB+aSeXODq3EK50qT8OlLdhMUSewL4q1wF0jxvZd5Pxlf4UOnM8SKQVrQNrvaV/BALdABuTFHaoAxPP/kDIRUgOduVzsQaEDxwOe6boPaXi4shwfliXMpXG2R1t+eWCTSRNKe/fexBqTdXBH5ewyym3ANA24e2SP6CCA4cwggODMIIC7KADAgECAgEAMA0GCSqGSIb3DQEBBQUAMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbTAeFw0wNDAyMTMxMDEzMTVaFw0zNTAyMTMxMDEzMTVaMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAwUdO3fxEzEtcnI7ZKZL412XvZPugoni7i7D7prCe0AtaHTc97CYgm7NsAtJyxNLixmhLV8pyIEaiHXWAh8fPKW+R017+EmXrr9EaquPmsVvTywAAE1PMNOKqo2kl4Gxiz9zZqIajOm1fZGWcGS0f5JQ2kBqNbvbg2/Za+GJ/qwUCAwEAAaOB7jCB6zAdBgNVHQ4EFgQUlp98u8ZvF71ZP1LXChvsENZklGswgbsGA1UdIwSBszCBsIAUlp98u8ZvF71ZP1LXChvsENZklGuhgZSkgZEwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tggEAMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAgV86VpqAWuXvX6Oro4qJ1tYVIT5DgWpE692Ag422H7yRIr/9j/iKG4Thia/Oflx4TdL+IFJBAyPK9v6zZNZtBgPBynXb048hsP16l2vi0k5Q2JKiPDsEfBhGI+HnxLXEaUWAcVfCsQFvd2A1sxRr67ip5y2wwBelUecP3AjJ+YcxggGaMIIBlgIBATCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwCQYFKw4DAhoFAKBdMBgGCSqGSIb3DQEJAzELBgkqhkiG9w0BBwEwHAYJKoZIhvcNAQkFMQ8XDTA4MDcxNDIzNTgwMFowIwYJKoZIhvcNAQkEMRYEFCXPG5S8+/tzHiooWJRCCARE/wlpMA0GCSqGSIb3DQEBAQUABIGAf7Eq7s7pIllabW7cb8hIe0IGLPIlx6QuLtOXj6iMqkzjY7IOE8r1P8xA+JqMA4GBv8ZyX0Ljm+TAx6lk1NvHYvvxJHUWkDmwtFs+BK8wMMtDTC8Msa0148jZQvL8IEMYaZEID1nm3qUy1pdwODUcMDomZFQfCyZRH0CRWpGS+UY=-----END PKCS7-----">
                </form>';
            $nav .= '<p style="float: right;"><form action="'. $this->plugin_url .'&task=checkDonation" method="post" style="display: inline; float: right;"><input type="checkbox" name="asa_donated" id="asa_donated" value="1" />&nbsp;<label for="asa_donated">'. __('I donated already. Please hide this box.', 'asa1') .'</label><br /><input type="submit" value="'. __('Send', 'asa1') .'" /></form></p>';
            $nav .= '<div style="clear:both"></div>';   
            $nav .= '</div>';
        }

        $_asa_newsletter = get_option('_asa_newsletter');
        if (empty($_asa_newsletter)) :
            ?>
            <h3><?php _e('Newsletter', 'asa1') ?></h3>

            <div class="asa_info_box asa_info_box_outer">
                <div id="asa_newsletter_teaser"><img src="<?php echo asa_plugins_url( 'img/newsletter.png', __FILE__); ?>" /></div>
                <div id="asa_newsletter_form">
                    <p><?php _e('Curious about the <b>new features</b> of the upcoming <b>ASA Pro</b> version?<br>Then you should subscribe to the ASA newsletter!', 'asa1'); ?></p>

                    <form action="http://wp-amazon-plugin.us7.list-manage.com/subscribe/post?u=a11948220f94721bb8bcddc8b&amp;id=69a6051b59" method="post" target="_blank" novalidate>
                        <div class="mc-field-group">
                            <label for="mce-EMAIL"><?php _e('Email Address (required)', 'asa1'); ?></label>
                            <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
                        </div>
                        <input type="submit" value="<?php _e('Subscribe', 'asa1') ?>" name="subscribe" id="mc-embedded-subscribe" class="button">
                    </form>

                </div>
                <p style="float: right; padding: 5px 10px;"><form action="<?php echo $this->plugin_url; ?>&task=checkNewsletter" method="post" style="display: inline; float: right;"><input type="checkbox" name="asa_check_newsletter" id="asa_check_newsletter" value="1" />&nbsp;<label for="asa_check_newsletter"><?php _e('I subscribed to the ASA newsletter already. Please hide this box.', 'asa1'); ?></label><br /><input type="submit" value="<?php _e('Send', 'asa1'); ?>" /></form></p>
            </div>
        <?php
        endif;
        
        if (!$this->_isCache()) {
            $nav .= '<div class="error"><p>'. sprintf( __('It is highly recommended to activate the <a href="%s">cache</a>!', 'asa1'), $this->plugin_url .'&task=cache') .'</p></div>';
        }
        if ($this->isDebug()) {
            $nav .= '<div class="asa_box_warning"><p>'. __('Debugging mode is active. Be sure to deactivate it when you do not need it anymore.', 'asa1') .'</p></div>';
        }
        return $nav;
    }
    
    /**
     * the actual options page content
     *
     */
    protected function _displayDispatcher ($task) 
    {
        $_asa_donated = get_option('_asa_donated');
        if ($task == 'checkDonation' && empty($_asa_donated)) {
            $this->_checkDonated();
        }
        $_asa_newsletter = get_option('_asa_newsletter');
        if ($task == 'checkNewsletter' && empty($_asa_newsletter)) {
            $this->_checkNewsletter();
        }
                
        
        
        switch ($task) {
                
            case 'collections':
                
                require_once(dirname(__FILE__) . '/AsaCollection.php');
                $this->collection = new AsaCollection($this->db);

                $params = array();
                
                
                
                if (isset($_POST['deleteit_collection_item'])) {
                    $delete_items = $_POST['delete_collection_item'];
                    if (count($delete_items) > 0) {
                        foreach ($delete_items as $item) {
                           $this->collection->deleteAsin($item);
                        }
                    }
                }
                
                if (isset($_POST['submit_new_asin'])) {
                    
                    $asin             = strip_tags($_POST['new_asin']);
                    $collection_id     = strip_tags($_POST['collection']);
                    $item            = $this->_getItem($asin); 
                    
                    if ($item === null) {                        
                        // invalid asin
                        $this->error['submit_new_asin'] = __('invalid ASIN', 'asa1');
                        
                    } else if ($this->collection->checkAsin($asin, $collection_id) !== null) {
                        // asin already added to this collection
                        $this->error['submit_new_asin'] = sprintf(
                            __('ASIN already added to collection <strong>%s</strong>', 'asa1'),
                            $this->collection->getLabel($collection_id)
                        );
                        
                    } else {
                        
                        if ($this->collection->addAsin($asin, $collection_id) === true) {
                            $this->success['submit_new_asin'] = sprintf(
                                __('<strong>%s</strong> added to collection <strong>%s</strong>', 'asa1'),
                                $item->Title,
                                $this->collection->getLabel($collection_id)
                            );
                        }
                    }
                    
                } else if (isset($_POST['submit_manage_collection'])) {
                    
                    $collection_id = strip_tags($_POST['select_manage_collection']);
                    
                    $params['collection_items'] = $this->collection->getItems($collection_id);
                    $params['collection_id']     = $collection_id;

                } else if (isset($_GET['select_manage_collection']) && isset($_GET['update_timestamp'])) {
                    
                    $item_id = strip_tags($_GET['update_timestamp']);
                    $this->collection->updateItemTimestamp($item_id);
                    
                    $collection_id = strip_tags($_GET['select_manage_collection']);
                    $params['collection_items'] = $this->collection->getItems($collection_id);
                    $params['collection_id']     = $collection_id;
                    
                } else if (isset($_POST['submit_delete_collection'])) {
                    
                    $collection_id = strip_tags($_POST['select_manage_collection']);
                    $collection_label = $this->collection->getLabel($collection_id);
                    
                    if ($collection_label !== null) {
                        $this->collection->delete($collection_id);
                    }
                    
                    $this->success['manage_collection'] = sprintf(
                        __('collection deleted: <strong>%s</strong>', 'asa1'),
                        $collection_label
                    );
                    
                } else if (isset($_POST['submit_new_collection'])) {
                    
                    $collection_label = str_replace(' ', '_', trim($_POST['new_collection']));
                    $collection_label = preg_replace("/[^a-zA-Z0-9_]+/", "", $collection_label);

                    if (empty($collection_label)) {
                        $this->error['submit_new_collection'] = __('Invalid collection label', 'asa1');
                    } else {
                        if ($this->collection->create($collection_label) == true) {
                            $this->success['submit_new_collection'] = sprintf(
                                __('New collection <strong>%s</strong> created'),
                                $collection_label
                            );
                        } else {
                            $this->error['submit_new_collection'] = __('This collection already exists', 'asa1');
                        }
                    }
                
                } else if (isset($_POST['submit_collection_init']) && 
                    isset($_POST['activate_collections'])) {

                    $this->collection->initDB();
                }
                
                echo $this->_getSubMenu($task);
                
                if ($this->db->get_var("SHOW TABLES LIKE '". $this->db->prefix ."asa_collection%'") === null) {
                    $this->_displayCollectionsSetup();
                } else {
                    $this->_displayCollectionsPage($params);
                }
                break;
                
            case 'usage':

                echo $this->_getSubMenu($task);

                $this->_displayUsagePage();
                break;

            case 'faq':

                echo $this->_getSubMenu($task);

                $this->_displayFaqPage();
                break;
                
            case 'cache':
                
                if (isset($_POST['clean_cache'])) {
                    
                    if (empty($this->cache)) {
                        $this->error['submit_cache'] = __('Cache not activated!', 'asa1');
                    } else {
                        $this->cache->clean(AsaZend_Cache::CLEANING_MODE_ALL);
                        $this->success['submit_cache'] = __('Cache cleaned up!', 'asa1');
                    }
                    
                } else if (count($_POST) > 0) {
                    
                    $_asa_cache_lifetime      = strip_tags($_POST['_asa_cache_lifetime']);
                    $_asa_cache_dir           = strip_tags($_POST['_asa_cache_dir']);
                    $_asa_cache_active        = strip_tags($_POST['_asa_cache_active']);
                    $_asa_cache_skip_on_admin = strip_tags($_POST['_asa_cache_skip_on_admin']);
                    update_option('_asa_cache_lifetime', intval($_asa_cache_lifetime));
                    update_option('_asa_cache_dir', $_asa_cache_dir);
                    update_option('_asa_cache_active', intval($_asa_cache_active));
                    update_option('_asa_cache_skip_on_admin', intval($_asa_cache_skip_on_admin));

                    $this->success['submit_cache'] = __('Cache options updated!', 'asa1');
                }
                
                echo $this->_getSubMenu($task);
                
                $this->_displayCachePage();
                break;

            case 'options':

                if (count($_POST) > 0 && isset($_POST['info_update'])) {

                    $_asa_product_preview        = strip_tags($_POST['_asa_product_preview']);
                    $_asa_parse_comments         = strip_tags($_POST['_asa_parse_comments']);
                    $_asa_async_load             = strip_tags($_POST['_asa_async_load']);
                    $_asa_hide_meta_link         = strip_tags($_POST['_asa_hide_meta_link']);
                    $_asa_use_short_amazon_links = strip_tags($_POST['_asa_use_short_amazon_links']);
                    $_asa_use_amazon_price_only  = strip_tags($_POST['_asa_use_amazon_price_only']);
                    $_asa_debug                  = strip_tags($_POST['_asa_debug']);

                    update_option('_asa_product_preview', $_asa_product_preview);
                    update_option('_asa_parse_comments', $_asa_parse_comments);
                    update_option('_asa_async_load', $_asa_async_load);
                    update_option('_asa_hide_meta_link', $_asa_hide_meta_link);
                    update_option('_asa_use_short_amazon_links', $_asa_use_short_amazon_links);
                    update_option('_asa_use_amazon_price_only', $_asa_use_amazon_price_only);
                    update_option('_asa_debug', $_asa_debug);

                    $this->_displaySuccess(__('Settings saved.', 'asa1'));
                }

                if ($this->isDebug()) {
                    $this->_initDebugger();
                    if (!empty($_POST['_asa_debug_clear'])) {
                        $this->_debugger->clear();
                    }
                }

                echo $this->_getSubMenu($task);

                $this->_displayOptionsPage();
                break;

            default:
                
                if (count($_POST) > 0 && isset($_POST['setup_update'])) {
                    
                    $_asa_amazon_api_key         = strip_tags(trim($_POST['_asa_amazon_api_key']));
                    $_asa_amazon_api_secret_key  = base64_encode(strip_tags(trim($_POST['_asa_amazon_api_secret_key'])));
                    $_asa_amazon_tracking_id     = strip_tags(trim($_POST['_asa_amazon_tracking_id']));

                    update_option('_asa_amazon_api_key', $_asa_amazon_api_key);
                    update_option('_asa_amazon_api_secret_key', $_asa_amazon_api_secret_key);
                    update_option('_asa_amazon_tracking_id', $_asa_amazon_tracking_id);

                    if (isset($_POST['_asa_amazon_country_code'])) {
                        $_asa_amazon_country_code = strip_tags($_POST['_asa_amazon_country_code']);                        
                        if (!Asa_Service_Amazon::isSupportedCountryCode($_asa_amazon_country_code)) {
                            $_asa_amazon_country_code = 'US';
                        }
                        update_option('_asa_amazon_country_code', $_asa_amazon_country_code);
                    }

                    $this->_displaySuccess(__('Settings saved.', 'asa1'));
                }
                
                echo $this->_getSubMenu($task);
                
                $this->_displaySetupPage();
        }
    }
    
    /**
     * check if user wants to hide the donation notice
     */
    protected function _checkDonated () {
        
        if ($_POST['asa_donated'] == '1') {
            update_option('_asa_donated', '1');            
        }
    }

    /**
     * check if user wants to hide the newsletter box
     */
    protected function _checkNewsletter () {

        if ($_POST['asa_check_newsletter'] == '1') {
            update_option('_asa_newsletter', '1');
        }
    }
    
    /**
     * collections asasetup screen
     *
     */
    protected function _displayCollectionsSetup ()     
    {    
        ?>        
        <div id="asa_collections_setup" class="wrap">
        <fieldset class="options">
        <h2><?php _e('Collections') ?></h2>
        
        <p><?php _e('Do you want to activate the AmazonSimpleAdmin collections feature?', 'asa1'); ?></p>
        <form name="form_collection_init" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
        <label for="activate_collections">yes</label>
        <input type="checkbox" name="activate_collections" id="activate_collections" value="1">
        <p class="submit" style="margin:0; display: inline;">
            <input type="submit" name="submit_collection_init" value="<?php _e('Activate', 'asa1'); ?>" />
        </p>
        </form>
        </fieldset>
        </div>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        
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
        <h2><?php _e('Collections', 'asa1') ?></h2>

        <p><?php printf( __('Check out the <a href="%s" target="_blank">guide</a> if you do not know how to use collections.', 'asa1'), 'http://www.wp-amazon-plugin.com/guide/'); ?></p>
        
        <h3><?php _e('Create new collection', 'asa1'); ?></h3>
        <?php
        if (isset($this->error['submit_new_collection'])) {
            $this->_displayError($this->error['submit_new_collection']);    
        } else if (isset($this->success['submit_new_collection'])) {
            $this->_displaySuccess($this->success['submit_new_collection']);    
        }
        ?>
        
        <form name="form_new_collection" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
        <label for="new_collection"><?php _e('New collection', 'asa1'); ?>:</label>
        <input type="text" name="new_collection" id="new_collection" />
        
        <p style="margin:0; display: inline;">
            <input type="submit" name="submit_new_collection" value="<?php _e('Save', 'asa1'); ?>" class="button" />
        </p><br>
            (<?php _e('Only alpha-numeric characters and underscore allowed', 'asa1'); ?>)
        </form>
        
        <h3><?php _e('Add to collection', 'asa1'); ?></h3>
        <?php
        if (isset($this->error['submit_new_asin'])) {
            $this->_displayError($this->error['submit_new_asin']);    
        } else if (isset($this->success['submit_new_asin'])) {
            $this->_displaySuccess($this->success['submit_new_asin']);    
        }
        ?>
        <form name="form_new_asin" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
        <label for="new_asin"><img src="<?php echo asa_plugins_url( 'img/misc_add_small.gif', __FILE__ ); ?>" /> <?php _e('Add Amazon item (ASIN)', 'asa1'); ?>:</label>
        <input type="text" name="new_asin" id="new_asin" />
        <label for="collection"><?php _e('to collection', 'asa1'); ?>:</label>
        
        <?php
        $collection_id = false;
        if (isset($_POST['collection'])) {
            $collection_id = trim($_POST['collection']);
        }
        echo $this->collection->getSelectField('collection', $collection_id);
        ?>
        
        <p style="margin:0; display: inline;">
            <input type="submit" name="submit_new_asin" value="<?php _e('Save', 'asa1'); ?>" class="button" />
        </p>
        </form>
        
        <a name="manage_collection"></a>
        <h3><?php _e('Manage collections', 'asa1'); ?></h3>
        <?php
        if (isset($this->error['manage_collection'])) {
            $this->_displayError($this->error['manage_collection']);    
        } else if (isset($this->success['manage_collection'])) {
            $this->_displaySuccess($this->success['manage_collection']);    
        }
        ?>
        <form name="manage_colection" action="<?php echo $this->plugin_url .'&task=collections'; ?>#manage_collection" method="post">
        <label for="select_manage_collection"><?php _e('Collection', 'asa1'); ?>:</label>
        
        <?php
        $manage_collection_id = false;
        if (isset($_POST['select_manage_collection'])) {
            $manage_collection_id = trim($_POST['select_manage_collection']);
        }
        echo $this->collection->getSelectField('select_manage_collection', $manage_collection_id);
        ?>

        <p style="margin:0; display: inline;">
            <input type="submit" name="submit_manage_collection" value="<?php _e('Browse', 'asa1'); ?>" class="button" />
        </p>
        <p style="margin:0; display: inline;">
            <input type="submit" name="submit_delete_collection" value="<?php _e('Delete collection', 'asa1'); ?>" onclick="return asa_deleteCollection();" class="button" />
        </p>
        </form>
        
        <?php
        if (isset($collection_items) && !empty($collection_items)) {

            $table = '';
            $table .= '<form id="collection-filter" action="'.$this->plugin_url .'&task=collections" method="post">';
            
            $table .= '<div class="tablenav">
                <div class="alignleft">
                <input type="submit" class="button-secondary delete" name="deleteit_collection_item" value="'. __('Delete selected', 'asa1') .'" onclick="return asa_deleteCollectionItems(\''. __("Delete selected collection items from collection?", "asa1") .'\');"/>
                <input type="hidden" name="submit_manage_collection" value="1" />   
                <input type="hidden" name="select_manage_collection" value="'. $collection_id .'" />             
                </div>              
                <br class="clear"/>
                </div>';
                     
            $table .= '<table class="widefat"><thead><tr>';
            $table .= '<th scope="col" style="text-align: center"><input type="checkbox" onclick="asa_checkAll();"/></th>';
            $table .= '<th scope="col" width="[thumb_width]"></th>';
            $table .= '<th scope="col" width="120">ASIN</th>';
            $table .= '<th scope="col" width="120">'. __('Price', 'asa1') .'</th>';
            $table .= '<th scope="col">'. __('Title', 'asa1') .'</th>';
            $table .= '<th scope="col" width="160">'. __('Timestamp', 'asa1') . '</th>';
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
                
                $title = str_replace("'", "\'", $item->Title);
                
                $table .= '<tr id="collection_item_'. $row->collection_item_id .'"'.$tr_class.'>';
                
                $table .= '<th class="check-column" scope="row" style="text-align: center"><input type="checkbox" value="'. $row->collection_item_id .'" name="delete_collection_item[]"/></th>';
                if ($item->SmallImage == null) {
                    $thumbnail = asa_plugins_url( 'img/no_image.gif', __FILE__ );
                } else {
                    $thumbnail = $item->SmallImage->Url->getUri();
                }
                $table .= '<td width="[thumb_width]"><a href="'. $item->DetailPageURL .'" target="_blank"><img src="'. $thumbnail .'" /></a></td>';
                $table .= '<td width="120">'. $row->collection_item_asin .'</td>';
                $table .= '<td width="120">'. $item->Offers->Offers[0]->FormattedPrice .'</td>';
                $table .= '<td><span id="">'. $item->Title .'</span></td>';
                $table .= '<td width="160">'. date(str_replace(' \<\b\r \/\>', ',', __('Y-m-d \<\b\r \/\> g:i:s a')), $row->timestamp) .'</td>';                
                $table .= '<td><a href="'. $this->plugin_url .'&task=collections&update_timestamp='. $row->collection_item_id .'&select_manage_collection='. $collection_id .'" class="edit" onclick="return asa_set_latest('. $row->collection_item_id .', \''. sprintf(__('Set timestamp of &quot;%s&quot; to actual time?', 'asa1'), $title) . '\');" title="update timestamp">'. __('latest', 'asa1') .'</a></td>';
                $table .= '</tr>';
                
                $thumb_max_width[] = $item->SmallImage->Width;
            }
            
            rsort($thumb_max_width);            
                                    
            $table .= '</tbody></table></form>';
            
            $search = array(
                '/\[thumb_width\]/',
            );
            
            $replace = array(
                $thumb_max_width[0],
            );
            
            echo preg_replace($search, $replace, $table);
            echo '<div id="ajax-response"></div>';
        
        } else if (isset($collection_id)) {
            echo '<p>' . __('Nothing found. Add some products.', 'asa1') .'</p>';
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
        <h2><?php _e('Usage', 'asa1') ?></h2>
        
        <p><?php printf( __('Please visit the <a href="%s" target="_blank">Usage Guide</a> on the plugin\'s homepage to learn how to use it.', 'asa1'), 'http://www.wp-amazon-plugin.com/usage/' ); ?></a></p>

        <h3><?php _e('Step by Step Guide', 'asa1'); ?></h3>
        <p><?php printf( __('Please read the <a href="%s" target="_blank">Step by Step Guide</a> if you are new to this plugin.', 'asa1'), 'http://www.wp-amazon-plugin.com/guide/'); ?></p>

        <h3><?php _e('Available templates', 'asa1'); ?></h3>
                
        <p><?php _e('This is a list of template files, ASA found on your server:', 'asa1') ?></p>
        <ul>
        <?php
        $templates = $this->getAllTemplates();
        foreach ($templates as $template) {
            echo '<li>'. $template .'</li>';
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
    protected function _displayFaqPage ()
    {
        $faqUrl = 'http://www.wp-amazon-plugin.com/faq-remote/';

        $client = new AsaZend_Http_Client($faqUrl);
        $response = $client->request('GET');

        if ($response->isSuccessful()) {
            echo $response->getBody();
        } else {
            echo '<p>Could not load FAQ from '. $faqUrl . '</p>';
        }
    }
    
    /**
     * Load options panel
     *
     */
    protected function _displayOptionsPage()
    {
        $this->_loadOptions();
    ?>
    <h2><?php _e('Options', 'asa1') ?></h2>

    <form method="post">

    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row">
                    <label for="_asa_async_load"><?php _e('Use asynchronous mode (AJAX):', 'asa1') ?></label><br>
                    (BETA)
                </th>
                <td>
                    <input type="checkbox" name="_asa_async_load" id="_asa_async_load" value="1"<?php echo (($this->_async_load == true) ? 'checked="checked"' : '') ?> />
                    <p class="description"><?php _e('Requests to the Amazon webservice will be executed asynchronously. This will improve page load speed.', 'asa1'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_asa_parse_comments"><?php _e('Parse comments:', 'asa1') ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_asa_parse_comments" id="_asa_parse_comments" value="1"<?php echo (($this->_parse_comments == true) ? 'checked="checked"' : '') ?> />
                    <p class="description"><?php _e('[asa] tags in comments will be parsed.', 'asa1'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_asa_product_preview"><?php _e('Enable product preview links:', 'asa1') ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_asa_product_preview" id="_asa_product_preview" value="1"<?php echo (($this->_product_preview == true) ? 'checked="checked"' : '') ?> />
                    <p class="description"><?php _e('Product preview layers are only supported by US, UK and DE so far. This can effect the site to be loaded a bit slower due to link parsing.', 'asa1'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_asa_hide_meta_link"><?php _e('Hide ASA link:', 'asa1') ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_asa_hide_meta_link" id="_asa_hide_meta_link" value="1"<?php echo ((get_option('_asa_hide_meta_link') == true) ? 'checked="checked"' : '') ?> />
                    <p class="description"><?php _e('Hides link to ASA homepage from Meta widget. Do not hide it to support this plugin.', 'asa1'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_asa_use_short_amazon_links"><?php _e('Use short Amazon links:', 'asa1') ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_asa_use_short_amazon_links" id="_asa_use_short_amazon_links" value="1"<?php echo ((get_option('_asa_use_short_amazon_links') == true) ? 'checked="checked"' : '') ?> />
                    <p class="description"><?php printf( __('Activates the short version of affiliate links like %s', 'asa1'), 'http://www.amazon.com/exec/obidos/ASIN/123456789/trackingid-12' ); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_asa_debug"><?php _e('Activate debugging:', 'asa1') ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_asa_debug" id="_asa_debug" value="1"<?php echo ((get_option('_asa_debug') == true) ? 'checked="checked"' : '') ?> />
                    <p class="description"><?php printf( __('Important: Use debugging only temporarily if you are facing problems with ASA. Ask the <a href="%s" target="_blank">support</a> how to interpret the debugging information.', 'asa1'), 'http://www.wp-amazon-plugin.com/contact/' ); ?></p>
                    <?php if ($this->isDebug()): ?>
                    <?php if ($this->_debugger_error != null): ?>
                        <p><b><?php _e('Debugger error', 'asa1'); ?>: </b><?php echo $this->_debugger_error; ?></p>
                        <?php else:?>
                        <a href="<?php echo $this->plugin_url; ?>&task=options"><?php _e('Refresh', 'asa1'); ?></a>
                        <br />
                        <textarea name="debug_contents" id="debug_contents" rows="20" cols="100"><?php if (!empty($this->_debugger)) echo $this->_debugger->read(); ?></textarea>
                        <br />
                        <input type="checkbox" name="_asa_debug_clear" id="_asa_debug_clear" value="1" /><label for="_asa_debug_clear"><?php _e('Clear debugging data', 'asa1') ?></label>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <input type="submit" name="info_update" class="button-primary" value="<?php _e('Update Options', 'asa1') ?> &raquo;" />
    </p>
    </form>

    <?php
    }

    /**
     * Tests connection
     *
     * @return array
     */
    public function testConnection()
    {
        $success = false;
        $message = '';

        try {
            $this->amazon = $this->connect();
            if ($this->amazon != null) {
                $this->amazon->testConnection();
                $success = true;
            } else {
                $message = __('Connection to Amazon Webservice failed. Please check the mandatory data.', 'asa1');
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        return array('success' => $success, 'message' => $message);
    }

    /**
     * Retrieves connections status
     *
     * @return bool
     */
    public function getConnectionStatus()
    {
        $result = $this->testConnection();
        return $result['success'] === true;
    }

    /**
     * Loads setup panel
     *
     */
    protected function _displaySetupPage ()
    {
        $_asa_status = false;
        
        $this->_getAmazonUserData();

        $connectionTestResult = $this->testConnection();
        if ($connectionTestResult['success'] === true) {
            $_asa_status = true;
        } else {
            $_asa_error = $connectionTestResult['message'];
        }
            
//        try {
//            $this->amazon = $this->connect();
//            if ($this->amazon != null) {
//                $this->amazon->testConnection();
//                $_asa_status = true;
//            } else {
//                 throw new Exception('Connection to Amazon Webservice failed. Please check the mandatory data.');
//            }
//        } catch (Exception $e) {
//            $_asa_error = $e->getMessage();
//        }
        ?>
        <div id="asa_setup" class="wrap">


        <h2><?php _e('Setup', 'asa1') ?></h2>

        <h3><?php _e('Need help?', 'asa1') ?></h3>

        <div class="help_item">
            <img src="<?php echo asa_plugins_url( 'img/faq.png', __FILE__); ?>" />
            <a href="<?php echo $this->plugin_url?>&task=faq"><?php _e('FAQ', 'asa1') ?></a>
        </div>
            <div class="help_item">
                <img src="<?php echo asa_plugins_url( 'img/documentation.png', __FILE__); ?>" />
                <a href="http://www.wp-amazon-plugin.com/documentation/" target="_blank"><?php _e('Online documentation', 'asa1') ?></a>
        </div>
        <div class="help_item">
            <img src="<?php echo asa_plugins_url( 'img/forums.png', __FILE__); ?>" />
            <a href="http://www.wp-amazon-plugin.com/forums/" target="_blank"><?php _e('Forums', 'asa1') ?></a>
        </div>
        <div class="help_item">
            <img src="<?php echo asa_plugins_url( 'img/contact.png', __FILE__); ?>" />
            <a href="http://www.wp-amazon-plugin.com/contact/" target="_blank"><?php _e('Contact', 'asa1') ?></a>
        </div>

        <br><br>

        <h3><?php _e('Credentials', 'asa1') ?></h3>

        <p><span id="_asa_status_label"><?php _e('Status', 'asa1') ?>:</span> <?php echo ($_asa_status == true) ? '<span class="_asa_status_ready">'. __('Ready', 'asa1') .'</span>' : '<span class="_asa_status_not_ready">'. __('Not Ready', 'asa1') .'</span>'; ?></p>

        <?php
        if (!empty($_asa_error)) {
            echo '<div id="message" class="error"><p><strong>Error:</strong> '. $_asa_error;
            echo '<br>'. __('Get help at', 'asa1') .' <a href="http://www.wp-amazon-plugin.com/faq/#setup_errors" target="_blank">http://www.wp-amazon-plugin.com/faq/#setup_errors</a></p></div>';
        }
        ?>

        <p><?php _e('Please fill in your Amazon Product Advertising API credentials.', 'asa1') ?></p>
        <p><?php _e('Fields marked with * are mandatory:', 'asa1') ?></p>

        <form method="post">
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">
                        <label for="_asa_amazon_api_key"<?php if (empty($this->_amazon_api_key)) { echo ' class="_asa_error_color"'; } ?>><?php _e('Your Amazon Access Key ID*:', 'asa1') ?></label>
                    </th>
                    <td>
                        <input type="text" name="_asa_amazon_api_key" id="_asa_amazon_api_key" autocomplete="off" value="<?php echo (!empty($this->_amazon_api_key)) ? $this->_amazon_api_key : ''; ?>" />
                        <a href="http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/AboutAWSAccounts.html" target="_blank"><?php _e('How do I get one?', 'asa1'); ?></a>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="_asa_amazon_api_secret_key"<?php if (empty($this->_amazon_api_secret_key)) { echo ' class="_asa_error_color"'; } ?>><?php _e('Your Secret Access Key*:', 'asa1'); ?></label>
                    </th>
                    <td>
                        <input type="password" name="_asa_amazon_api_secret_key" id="_asa_amazon_api_secret_key" autocomplete="off" value="<?php echo (!empty($this->_amazon_api_secret_key)) ? $this->_amazon_api_secret_key : ''; ?>" />
                        <a href="http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/ViewingCredentials.html" target="_blank"><?php _e('What is this?', 'asa1'); ?></a>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="_asa_amazon_tracking_id"<?php if (empty($this->amazon_tracking_id)) { echo ' class="_asa_error_color"'; } ?>><?php _e('Your Amazon Tracking ID*:', 'asa1') ?></label>
                    </th>
                    <td>
                        <input type="text" name="_asa_amazon_tracking_id" id="_asa_amazon_tracking_id" autocomplete="off" value="<?php echo (!empty($this->amazon_tracking_id)) ? $this->amazon_tracking_id : ''; ?>" />
                        <a href="http://amazon.com/associates" target="_blank"><?php _e('Where do I get one?', 'asa1'); ?></a>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="_asa_amazon_country_code"<?php if (empty($this->_amazon_country_code)) { echo ' class="_asa_status_not_ready"'; } ?>><?php _e('Your Amazon Country Code*:', 'asa1') ?></label>
                    </th>
                    <td>
                        <select name="_asa_amazon_country_code">
                            <?php
                            foreach (Asa_Service_Amazon::getCountryCodes() as $code) {
                                if ($code == $this->_amazon_country_code) {
                                    $selected = ' selected="selected"';
                                } else {
                                    $selected = '';
                                }
                                echo '<option value="'. $code .'"'.$selected.'>' . $code . '</option>';
                            }
                            ?>
                        </select> <img src="<?php echo asa_plugins_url( 'img/amazon_'. $this->_amazon_country_code .'_small.gif', __FILE__); ?>" id="selected_store" /> (<?php _e('Default', 'asa1'); ?>: US)
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
        <input type="submit" name="setup_update" class="button-primary" value="<?php _e('Update Options', 'asa1') ?> &raquo;" />
        <!-- <input type="submit" name="info_clear" value="<?php _e('Clear Settings', 'asa1') ?> &raquo;" /> -->
            <br /><br />
            <b><?php _e('Notice', 'asa1'); ?>:</b><br />
            <?php _e('If your status is ready but your implemented Amazon product boxes do not show any data, check the FAQ panel for more information (first entry).', 'asa1'); ?><br />
        </p>

        </form>
        </div>        
        <?php
    }    
    
    /**
     * the cache options page content
     *
     */
    protected function _displayCachePage () 
    {    
        $_asa_cache_lifetime      = get_option('_asa_cache_lifetime');
        $_asa_cache_dir           = get_option('_asa_cache_dir');
        $_asa_cache_active        = get_option('_asa_cache_active');
        $_asa_cache_skip_on_admin = get_option('_asa_cache_skip_on_admin');
        $current_cache_dir        = (!empty($_asa_cache_dir) ? $_asa_cache_dir : 'cache');

        ?>
        <div id="asa_cache" class="wrap">
        <form method="post">
        <fieldset class="options">
        <h2><?php _e('Cache') ?></h2>
                       
        <?php
        if (isset($this->error['submit_cache'])) {
            $this->_displayError($this->error['submit_cache']);    
        } else if (isset($this->success['submit_cache'])) {
            $this->_displaySuccess($this->success['submit_cache']);    
        }
        ?>
        
        <label for="_asa_cache_active"><?php _e('Activate cache:', 'asa1') ?></label>
        <input type="checkbox" name="_asa_cache_active" id="_asa_cache_active" value="1" <?php echo (!empty($_asa_cache_active)) ? 'checked="checked"' : ''; ?> />  
        <br />
        <label for="_asa_cache_skip_on_admin"><?php _e('Do not use cache when logged in as admin:', 'asa1') ?></label>
        <input type="checkbox" name="_asa_cache_skip_on_admin" id="_asa_cache_skip_on_admin" value="1" <?php echo (!empty($_asa_cache_skip_on_admin)) ? 'checked="checked"' : ''; ?> />
        <br />
        <label for="_asa_cache_lifetime"><?php _e('Cache Lifetime (in seconds):', 'asa1') ?></label>
        <input type="text" name="_asa_cache_lifetime" id="_asa_cache_lifetime" value="<?php echo (!empty($_asa_cache_lifetime)) ? $_asa_cache_lifetime : '7200'; ?>" />
        <br />  
        <label for="_asa_cache_dir"><?php _e('Cache directory:', 'asa1') ?></label>
        <input type="text" name="_asa_cache_dir" id="_asa_cache_dir" value="<?php echo $current_cache_dir; ?>" /> (<?php _e('within asa plugin directory / default = "cache" / must be <strong>writable</strong>!', 'asa1'); ?>)
        <br />
        <div style="border: 1px solid #EDEDED; padding: 4px; background: #F8F8F8;">
        <?php
        echo dirname(__FILE__) . DIRECTORY_SEPARATOR . $current_cache_dir . ' ' . __('is', 'asa1') . ' ';
        if (is_writable(dirname(__FILE__) . '/' . $current_cache_dir)) {
            echo '<strong style="color:#177B31">'. __('writable', 'asa1') . '</strong>';
        } else {
            echo '<strong style="color:#B41216">'. __('not writable', 'asa1') . '</strong>';
        }
        ?>
        </div>
        <br />        
    
        <p class="submit">
        <input type="submit" name="info_update" class="button-primary" value="<?php _e('Update Options', 'asa1') ?> &raquo;" />
        <input type="submit" name="clean_cache" value="<?php _e('Clear Cache', 'asa1') ?> &raquo;" class="button" />
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
        echo '<div class="error"><p>'. __('Error', 'asa1') .': '. $error .'</p></div>';
    }
    
    /**
     * 
     */
    protected function _displaySuccess ($success) 
    {
        echo '<div class="updated"><p>'. __('Success', 'asa1') .': '. $success .'</p></div>';
    }    
    
    /**
     * parses post content
     * 
     * @param         string        post content
     * @return         string        parsed content
     */
    public function parseContent ($content)
    {
        $matches         = array();
        $matches_coll     = array();

        // single items
        preg_match_all($this->bb_regex, $content, $matches);
        
        if ($matches && count($matches[0]) > 0) {
            
            // get defaul template file
            $tpl_src = $this->getDefaultTpl();

            for ($i=0; $i<count($matches[0]); $i++) {
                
                $match         = $matches[0][$i];
                                
                $tpl_file        = null;
                $asin           = $matches[2][$i]; 

                $params         = preg_replace($this->_regex_param_separator, $this->_internal_param_delimit, strip_tags(trim($matches[1][$i])));
                $params         = explode($this->_internal_param_delimit, $params);
                $params         = array_map('trim', $params);
                $parse_params   = array();

                if (!empty($params[0])) {
                    foreach ($params as $param) {
                        $param = trim($param);
                        if (!strstr($param, '=')) {
                            $tpl_file = $param;
                        } else {
                            if (strstr($param, 'comment=')) {
                                // the comment feature
                                preg_match('/comment="([^"\r\n]*)"/', $param, $comment_match);
                                $parse_params['comment'] = html_entity_decode($comment_match[1]);
                            } else {
                                $tp = explode('=', $param);
                                $parse_params[$tp[0]] = $tp[1];
                            }    
                        }
                    }
                }

                $tpl = $this->getTpl($tpl_file, $tpl_src);
                
                if (!empty($asin)) {
                    $content = str_replace($match, $this->parseTpl($asin, $tpl, $parse_params, $tpl_file), $content);
                }
            }
        }

        // collections
        preg_match_all($this->bb_regex_collection, $content, $matches_coll);
        
        if ($matches_coll && count($matches_coll[0]) > 0) {
            
            // get defaul template file
            $tpl_src = $this->getDefaultTpl();

            for ($i=0; $i<count($matches_coll[0]); $i++) {
                
                $match         = $matches_coll[0][$i];
                $coll_label    = $matches_coll[2][$i];
                
                $tpl_file        = null;
                $params            = explode(',', strip_tags(trim($matches_coll[1][$i])));
                $params         = array_map('trim', $params);
                $coll_options   = array();

                if (!empty($params[0])) {
                    foreach ($params as $param) {
                        if (!strstr($param, '=')) {
                            $tpl_file = $param;
                        } else {
                            $tp = explode('=', $param);
                            $coll_options[$tp[0]] = $tp[1];    
                        }
                    }
                }
                
                $tpl = $this->getTpl($tpl_file, $tpl_src);
                
                if (!empty($coll_label)) {
                    
                    require_once(dirname(__FILE__) . '/AsaCollection.php');
                    $this->collection = new AsaCollection($this->db);
                    
                    $collection_id = $this->collection->getId($coll_label);

                    $coll_items = $this->collection->getItems($collection_id);
                    
                    if (count($coll_items) == 0) {
                        $content = str_replace($match, '', $content);
                    } else {
                        
                        $coll_html = '';
                        if (isset($coll_options['type']) && $coll_options['type'] == 'random' && !isset($coll_options['items'])) {
                            // only one random collection item
                            $coll_items = array($coll_items[rand(0, count($coll_items)-1)]);
                        } else if (isset($coll_options['items']) && (!isset($coll_options['type']) || $coll_options['type'] == 'latest')) {
                            // only get the defined number of the latest collection items 
                            if ((int)$coll_options['items'] !== 0) {
                                $coll_items_limit = (int)$coll_options['items'];
                            }
                        } else if (isset($coll_options['items']) && isset($coll_options['type']) && $coll_options['type'] == 'random') {
                            // only get a limited number of random items                            
                            $new_coll_items = array();
                            $items_limit = (int)$coll_options['items'];
                            if ($items_limit > count($coll_items) || $items_limit === 0) {
                                $items_limit = count($coll_items);
                            }
                            while (count($new_coll_items) < $items_limit) {

                                $rand_item_index = rand(0, count($coll_items)-1);
                                
                                if (!isset($new_coll_items[$rand_item_index])) {
                                    $new_coll_items[$rand_item_index] = $coll_items[$rand_item_index];
                                }
                            }
                            $coll_items = $new_coll_items;                            
                        }
                        
                        $coll_items_counter = 1;
                        foreach ($coll_items as $row) {
                            $coll_html .= $this->parseTpl($row->collection_item_asin, $tpl, null, $tpl_file);
                            $coll_items_counter++;
                            if (isset($coll_items_limit) && $coll_items_counter > $coll_items_limit) {
                                break;
                            }
                        }
                        $content = str_replace($match, $coll_html, $content);
                    }                    
                }                
            }
        }
        
        return $content;
    }
    
    /**
     * Retrieves default template file
     */
    public function getDefaultTpl()
    {
        $tpl_src_custom  = dirname(__FILE__) .'/tpl/default.htm';
        $tpl_src_builtin = dirname(__FILE__) .'/tpl/built-in/default.htm';
        
        if (file_exists($tpl_src_custom)) {
            $tpl_src = file_get_contents($tpl_src_custom);
        } else {
            $tpl_src = file_get_contents($tpl_src_builtin);
        }    
        return $tpl_src;
    }
    
    /**
     * Retrieves all existing template files
     */
    public function getAllTemplates()
    {
        $availableTemplates = array();

        foreach($this->getTplLocations() as $loc) {

            if (!is_dir($loc)) {
                continue;
            }
            $dirIt = new DirectoryIterator($loc);

            foreach ($dirIt as $fileinfo) {

                $filename = $fileinfo->getFilename();

                if ($fileinfo->isDir() || $fileinfo->isDot()) {
                    continue;
                }

                $filePathinfo = pathinfo($filename);

                if (!in_array($filePathinfo['extension'], $this->getTplExtensions())) {
                    continue;
                }

                array_push($availableTemplates, $filePathinfo['filename']);
            }
        }

        $availableTemplates = array_unique($availableTemplates);
        sort($availableTemplates);

        return $availableTemplates;
    }
    
    /**
     * Retrieves template file to use
     */
    public function getTpl($tpl_file, $default=false)
    {
        if (!empty($tpl_file)) {

            $tplLocations = array(
                get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'asa' . DIRECTORY_SEPARATOR,
                dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR,
                dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'built-in' . DIRECTORY_SEPARATOR
            );
            $tplExtensions = array('htm', 'html');

            foreach ($this->getTplLocations() as $loc) {
                if (!is_dir($loc)) {
                    continue;
                }
                foreach ($this->getTplExtensions() as $ext) {
                    $tplPath = $loc . $tpl_file . '.' . $ext;
                    if (file_exists($tplPath)) {
                        $tpl = file_get_contents($tplPath);
                    }
                }
                if (isset($tpl)) {
                    break;
                }
            }
        }

        if (!isset($tpl)) {
            $tpl = $default;
        }

        return $tpl;
    }

    /**
     * @return mixed|void
     */
    public function getTplLocations()
    {
        $tplLocations = array(
            get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'asa' . DIRECTORY_SEPARATOR,
            dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR,
            dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'built-in' . DIRECTORY_SEPARATOR
        );
        return apply_filters('asa_tpl_locations', $tplLocations);
    }

    /**
     * @return mixed|void
     */
    public function getTplExtensions()
    {
        $tplExtensions = array('htm', 'html');
        return apply_filters('asa_tpl_extensions', $tplExtensions);
    }


    /**
     * parses the choosen template
     *
     * @param $asin
     * @param $tpl
     * @param null $parse_params
     * @param null $tpl_file
     * @internal param \amazon $string asin
     * @internal param \the $string template contents
     *
     * @return     string        the parsed template
     */
    public function parseTpl ($asin, $tpl, $parse_params=null, $tpl_file=null)
    {
        if (($this->_isAsync() && !defined('ASA_ASYNC_REQUEST') && !isset($parse_params['no_ajax'])) ||
            (!$this->_isAsync() && isset($parse_params['force_ajax']))) {
            // on AJAX request
            return $this->_getAsyncContent($asin, $tpl_file, $parse_params);
        }

        // get the item data
        $item = $this->_getItem($asin);
        
        if ($item === null) {
            
            return '';
        
        } else {
                        
            $search = $this->_getTplPlaceholders(true);
            
            $lowestOfferPrice = null;
            
            $tracking_id = '';
            
            if (!empty($this->amazon_tracking_id)) {
                // set the user's tracking id
                $tracking_id = $this->amazon_tracking_id;
            }

            // get the customer rating object
            $customerReviews = $this->getCustomerReviews($item);


//            if ($item->CustomerReviewsIFrameURL != null) {
//                require_once(dirname(__FILE__) . '/AsaCustomerReviews.php');
//                $customerReviews = new AsaCustomerReviews($item->ASIN, $item->CustomerReviewsIFrameURL, $this->cache);
//
//
//                if (strstr($averageRating, ',')) {
//                    $averageRating = str_replace(',', '.', $averageRating);
//                }
//
//            }



            if ($item->Offers->LowestUsedPrice && $item->Offers->LowestNewPrice) {
                
                $lowestOfferPrice = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
                    $item->Offers->LowestUsedPrice : $item->Offers->LowestNewPrice;
                $lowestOfferCurrency = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
                    $item->Offers->LowestUsedPriceCurrency : $item->Offers->LowestNewPriceCurrency;
                $lowestOfferFormattedPrice = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
                    $item->Offers->LowestUsedPriceFormattedPrice : $item->Offers->LowestNewPriceFormattedPrice;
                    
            } else if ($item->Offers->LowestNewPrice) {
                
                $lowestOfferPrice          = $item->Offers->LowestNewPrice;
                $lowestOfferCurrency       = $item->Offers->LowestNewPriceCurrency;
                $lowestOfferFormattedPrice = $item->Offers->LowestNewPriceFormattedPrice;
                
            } else if ($item->Offers->LowestUsedPrice) {
                
                $lowestOfferPrice          = $item->Offers->LowestUsedPrice;
                $lowestOfferCurrency       = $item->Offers->LowestUsedPriceCurrency;
                $lowestOfferFormattedPrice = $item->Offers->LowestUsedPriceFormattedPrice;
            }
            
            $lowestOfferPrice = $this->_formatPrice($lowestOfferPrice);
            $lowestNewPrice = $this->_formatPrice($item->Offers->LowestNewPrice);
            $lowestNewOfferFormattedPrice = $item->Offers->LowestNewPriceFormattedPrice;
            $lowestUsedPrice = $this->_formatPrice($item->Offers->LowestUsedPrice);
            $lowestUsedOfferFormattedPrice = $item->Offers->LowestUsedPriceFormattedPrice;
            
//            if ($item->Offers->Offers[0]->Price != null) {
//                $amazonPrice = $item->Offers->Offers[0]->Price;
//                $amazonPriceFormatted = $item->Offers->Offers[0]->FormattedPrice;
//            } elseif (!empty($lowestNewPrice)) {
//                $amazonPrice = $lowestNewPrice;
//                $amazonPriceFormatted = $lowestNewOfferFormattedPrice;
//            } elseif (!empty($lowestUsedPrice)) {
//                $amazonPrice = $lowestUsedPrice;
//                $amazonPriceFormatted = $lowestUsedOfferFormattedPrice;
//            }
//
//            if (isset($amazonPrice)) {
//                $amazonPrice = $this->_formatPrice($amazonPrice);
//            }

            $amazonPrice = $this->getAmazonPrice($item);
            $amazonPriceFormatted = $this->getAmazonPrice($item, true);

            
            $listPriceFormatted = $item->ListPriceFormatted;
            
            $totalOffers = $item->Offers->TotalNew + $item->Offers->TotalUsed + 
                $item->Offers->TotalCollectible + $item->Offers->TotalRefurbished;
            
            $platform = $item->Platform;
            if (is_array($platform)) {
                $platform = implode(', ', $platform);
            }

            $percentageSaved = $item->PercentageSaved;

            $no_img_url = asa_plugins_url( 'img/no_image.gif', __FILE__ );

            $replace = array(
                $item->ASIN,
                ($item->SmallImage != null) ? $item->SmallImage->Url->getUri() : $no_img_url,
                ($item->SmallImage != null) ? $item->SmallImage->Width : 60,
                ($item->SmallImage != null) ? $item->SmallImage->Height : 60,
                ($item->MediumImage != null) ? $item->MediumImage->Url->getUri() : $no_img_url,
                ($item->MediumImage != null) ? $item->MediumImage->Width : 60,
                ($item->MediumImage != null) ? $item->MediumImage->Height : 60,
                ($item->LargeImage != null) ? $item->LargeImage->Url->getUri() : $no_img_url,
                ($item->LargeImage != null) ? $item->LargeImage->Width : 60,
                ($item->LargeImage != null) ? $item->LargeImage->Height : 60,
                $item->Label,
                $item->Manufacturer,
                $item->Publisher,
                $item->Studio,
                $item->Title,
                $this->getItemUrl($item),
                empty($totalOffers) ? '0' : $totalOffers,
                empty($lowestOfferPrice) ? '---' : $lowestOfferPrice,
                $lowestOfferCurrency,
                str_replace('$', '\$', $lowestOfferFormattedPrice),
                empty($lowestNewPrice) ? '---' : $lowestNewPrice,
                str_replace('$', '\$', $lowestNewOfferFormattedPrice),
                empty($lowestUsedPrice) ? '---' : $lowestUsedPrice,
                str_replace('$', '\$', $lowestUsedOfferFormattedPrice),
                empty($amazonPrice) ? '---' : str_replace('$', '\$', $amazonPrice),
                empty($amazonPriceFormatted) ? '---' : str_replace('$', '\$', $amazonPriceFormatted),
                empty($listPriceFormatted) ? '---' : str_replace('$', '\$', $listPriceFormatted),
                $item->Offers->Offers[0]->CurrencyCode,
                $item->Offers->Offers[0]->Availability,
                asa_plugins_url( 'img/amazon_' . (empty($this->_amazon_country_code) ? 'US' : $this->_amazon_country_code) .'_small.gif', __FILE__ ),
                asa_plugins_url( 'img/amazon_' . (empty($this->_amazon_country_code) ? 'US' : $this->_amazon_country_code) .'.gif', __FILE__ ),
                $item->DetailPageURL,
                $platform,
                $item->ISBN,
                $item->EAN,
                $item->NumberOfPages,
                $this->getLocalizedDate($item->ReleaseDate),
                $item->Binding,
                is_array($item->Author) ? implode(', ', $item->Author) : $item->Author,
                is_array($item->Creator) ? implode(', ', $item->Creator) : $item->Creator,
                $item->Edition,
                $customerReviews->averageRating,
                ($customerReviews->totalReviews != null) ? $customerReviews->totalReviews : 0,
                ($customerReviews->imgTag != null) ? $customerReviews->imgTag : '<img src="'. asa_plugins_url( 'img/stars-0.gif', __FILE__ ) .'" class="asa_rating_stars" />',
                ($customerReviews->imgSrc != null) ? $customerReviews->imgSrc : asa_plugins_url( 'img/stars-0.gif', __FILE__ ),
                is_array($item->Director) ? implode(', ', $item->Director) : $item->Director,
                is_array($item->Actor) ? implode(', ', $item->Actor) : $item->Actor,
                $item->RunningTime,
                is_array($item->Format) ? implode(', ', $item->Format) : $item->Format,
                !empty($parse_params['custom_rating']) ? '<img src="' . asa_plugins_url( 'img/stars-'. $parse_params['custom_rating'] .'.gif', __FILE__ ) .'" class="asa_rating_stars" />' : '',
                isset($item->EditorialReviews[0]) ? $item->EditorialReviews[0]->Content : '',
                !empty($item->EditorialReviews[1]) ? $item->EditorialReviews[1]->Content : '',
                is_array($item->Artist) ? implode(', ', $item->Artist) : $item->Artist,
                !empty($parse_params['comment']) ? $parse_params['comment'] : '',
                !empty($percentageSaved) ? $percentageSaved : 0,
                !empty($item->Offers->Offers[0]->IsEligibleForSuperSaverShipping) ? 'AmazonPrime' : '',
                !empty($item->Offers->Offers[0]->IsEligibleForSuperSaverShipping) ? '<img src="'. asa_plugins_url( 'img/amazon_prime.png', __FILE__ )  .'" class="asa_prime_pic" />' : '',
                $this->getAmazonShopUrl() . 'product-reviews/' . $item->ASIN . '/&tag=' . $this->getTrackingId(),
                $this->getTrackingId(),
                $this->getAmazonShopUrl(),
                $this->_formatPrice($item->Offers->SalePriceAmount),
                $item->Offers->SalePriceCurrencyCode,
                $item->Offers->SalePriceFormatted,
            );

            $result = preg_replace($search, $replace, $tpl);

            // check for unresolved
            preg_match_all('/\{\$([a-z0-9\-\>]*)\}/i', $result, $matches);
            
            $unresolved = $matches[1];
            
            if (count($unresolved) > 0) {

                $unresolved_names        = $matches[1];
                $unresolved_placeholders = $matches[0];
                
                $unresolved_search  = array();
                $unresolved_replace = array();
                
                
                for ($i=0; $i<count($unresolved_names);$i++) {

                    $value = $item->$unresolved_names[$i];

                    if (strstr($value, '$')) {
                        $value = str_replace('$', '\$', $value);
                    }
                    
                    $unresolved_search[]  = $this->TplPlaceholderToRegex($unresolved_placeholders[$i]);
                    $unresolved_replace[] = $value;                    
                }
                if (count($unresolved_search) > 0) {
                    $result = preg_replace($unresolved_search, $unresolved_replace, $result);
                }
            }
            return $result;
        }
    }
    
    /**
     * get item information from amazon webservice or cache
     * 
     * @param        string        ASIN
     * @return         object        AsaZend_Service_Amazon_Item object
     */    
    protected function _getItem ($asin)
    {
        try {
                        
            if ($this->cache == null || $this->_useCache() === false) {
                // if cache could not be initialized
                $item = $this->_getItemLookup($asin);
            } else if (!$item = $this->cache->load($asin)) {
                // if asin is not cached yet
                $item = $this->_getItemLookup($asin);
                
                // put asin in cache now
                $this->cache->save($item, $asin);
            }
            return $item;
            
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Public alias for self::_getItem($asin)
     *
     * @param $asin
     * @return object
     */
    public function getItemObject($asin)
    {
        return $this->_getItem($asin);
    }
    
    /**
     * get item information from amazon webservice
     * 
     * @param       string      ASIN
     * @return      object      AsaZend_Service_Amazon_Item object
     */     
    protected function _getItemLookup ($asin)
    {
        $result = $this->amazon->itemLookup($asin, array(
                    'ResponseGroup' => 'ItemAttributes,Images,Offers,OfferListings,Reviews,EditorialReview,Tracks'));
        return $result;
    }
            
    
    /**
     * gets options from database options table
     */
    protected function _getAmazonUserData ()
    {
        $this->_amazon_api_key            = get_option('_asa_amazon_api_key');
        $this->_amazon_api_secret_key     = base64_decode(get_option('_asa_amazon_api_secret_key'));
        $this->amazon_tracking_id         = get_option('_asa_amazon_tracking_id');

        $amazon_country_code = get_option('_asa_amazon_country_code');
        if (!empty($amazon_country_code)) {
            $this->_amazon_country_code = $amazon_country_code;
        }
    }

    /**
     * Loads options
     */
    protected function _loadOptions()
    {
        $_asa_product_preview = get_option('_asa_product_preview');
        if (empty($_asa_product_preview)) {
            $this->_product_preview = false;
        } else {
            $this->_product_preview = true;
        }

        $_asa_parse_comments = get_option('_asa_parse_comments');
        if (empty($_asa_parse_comments)) {
            $this->_parse_comments = false;
        } else {
            $this->_parse_comments = true;
        }

        $_asa_async_load = get_option('_asa_async_load');
        if (empty($_asa_async_load)) {
            $this->_async_load = false;
        } else {
            $this->_async_load = true;
        }

        $_asa_use_amazon_price_only = get_option('_asa_use_amazon_price_only');
        if (empty($_asa_use_amazon_price_only)) {
            $this->_asa_use_amazon_price_only = false;
        } else {
            $this->_asa_use_amazon_price_only = true;
        }
    }
    
    /**
     * generates right placeholder format and returns them as array
     * optionally prepared for use as regex
     * 
     * @param         bool        true for regex prepared
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
     * @param         string        placehoder
     * @return         string        escaped placeholder
     */
    public function TplPlaceholderToRegex ($ph)
    {
        $search = array(
            '{',
            '}',
            '$',
            '-',
            '>'
        );
        
        $replace = array(
            '\{',
            '\}',
            '\$',
            '\-',
            '\>'
        );
        
        $ph = str_replace($search, $replace, $ph);
        
        return '/'. $ph .'/';
    }
    
    /**
     * formats the price value from amazon webservice
     * 
     * @param         string        price
     * @return         mixed        price (float, int for JP)
     */
    protected function _formatPrice ($price)
    {
        if ($price === null || empty($price)) {
            return $price;
        }
        
        if ($this->_amazon_country_code != 'JP') {
            $price = (float) substr_replace($price, '.', (strlen($price)-2), -2);
        } else {
            $price = intval($price);
        }    
        
        $dec_point         = '.';
        $thousands_sep     = ',';
        
        if ($this->_amazon_country_code == 'DE' ||
            $this->_amazon_country_code == 'FR') {
            // taken the amazon websites as example
            $dec_point         = ',';
            $thousands_sep     = '.';
        }
        
        if ($this->_amazon_country_code != 'JP') {
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
        echo '<link rel="stylesheet" type="text/css" media="screen" href="' . asa_plugins_url( 'css/options.css?v='. self::VERSION , __FILE__ ) .'" />';
        echo '<script type="text/javascript" src="' . asa_plugins_url( 'js/asa.js?v='. self::VERSION , __FILE__ ) .'"></script>';
    }
    
    /**
     * Adds the meta link
     */
    public function addMetaLink() 
    {
        echo '<li>Powered by <a href="http://www.wp-amazon-plugin.com/" target="_blank" title="Open AmazonSimpleAdmin homepage">AmazonSimpleAdmin</a></li>';
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
        
        switch ($this->_amazon_country_code) {
            
            case 'DE':
                $replace = array(
                    'de',
                    (!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : ''),
                    '3'
                );                
                $js = preg_replace($search, $replace, $js);
                break;
                
            case 'UK':
                $replace = array(
                    'co.uk',
                    (!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : ''),
                    '2'
                );                
                $js = preg_replace($search, $replace, $js);
                break;
                
            case 'US':
            case false:
                $replace = array(
                    'com',
                    (!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : ''),
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

        //$tpl_src = file_get_contents(dirname(__FILE__) .'/tpl/'. $tpl .'.htm');
        $tpl_src = $this->getTpl($tpl);
        
        switch ($type) {
            
            case 'latest':
                $collection_html .= $this->parseTpl($result[0]->asin, $tpl_src, null, $tpl);
                break;
            
            case 'all':
            default:
                foreach ($result as $row) {
                    $collection_html .= $this->parseTpl($row->asin, $tpl_src, null, $tpl);
                }
                
        }
        
        return $collection_html;
    }
    
    /**
     * 
     */
    public function getItem ($asin, $tpl=false)
    {   
        $item_html = '';
        
        if ($tpl == false) {
            $tpl = 'sidebar_item';
        }

        $tpl_src = $this->getTpl($tpl);
        
        $item_html .= $this->parseTpl(trim($asin), $tpl_src, null, $tpl);
        
        return $item_html;
    }

    /**
     * @return bool
     */
    protected function _isAsync()
    {
        return $this->_async_load === true;
    }

    /**
     * @param $asin
     * @param $tpl
     * @param $parse_params
     * @param $match
     */
    protected function _getAsyncContent($asin, $tpl, $parse_params)
    {
        $containerID = 'asa-' . md5(uniqid(mt_rand()));
        $params = json_encode($parse_params);
        $nonce = wp_create_nonce('amazonsimpleadmin');
        $site_url = site_url();
        if (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE == true) {
            $site_url = network_site_url();
        }
        if (empty($tpl)) {
            $tpl = 'default';
        }

        $output = '<span id="'. $containerID .'" class="asa_async_container asa_async_container_'. $tpl .'"></span>';
        $output .= "<script type='text/javascript'>jQuery(document).ready(function($){var data={action:'asa_async_load',asin:'$asin',tpl:'$tpl',params:'$params',nonce:'$nonce'};if(typeof ajaxurl=='undefined'){var ajaxurl='$site_url/wp-admin/admin-ajax.php'}$.post(ajaxurl,data,function(response){jQuery('#$containerID').html(response)})});</script>";
        return $output;
    }

    /**
     * @param $item
     * @param bool $formatted
     * @return mixed|null
     */
    public function getAmazonPrice($item, $formatted=false)
    {
        $result = null;

        if (!empty($item->Offers->LowestNewPrice)) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->LowestNewPrice);
            } else {
                $result = $item->Offers->LowestNewPriceFormattedPrice;
            }
        } elseif ($item->Offers->SalePriceAmount != null) {
                if ($formatted === false) {
                    $result = $this->_formatPrice($item->Offers->SalePriceAmount);
                } else {
                    $result = $item->Offers->SalePriceFormatted;
                }
        } elseif ($item->Offers->Offers[0]->Price != null) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->Offers[0]->Price);
            } else {
                $result = $item->Offers->Offers[0]->FormattedPrice;
            }
        } elseif (!empty($item->Offers->LowestUsedPrice)) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->LowestUsedPrice);
            } else {
                $result = $item->Offers->LowestUsedPriceFormattedPrice;
            }
        }

        return $result;
    }

    /**
     * Retrieve the customer reviews object
     *
     * @param $item
     * @return AsaCustomerReviews|null
     */
    public function getCustomerReviews($item)
    {
        require_once(dirname(__FILE__) . '/AsaCustomerReviews.php');

        $iframeUrl = ($item->CustomerReviewsIFrameURL != null) ? $item->CustomerReviewsIFrameURL : '';

        return new AsaCustomerReviews($item->ASIN, $iframeUrl, $this->cache);
    }

    /**
     * @param $item
     */
    public function getItemUrl($item)
    {
        if (get_option('_asa_use_short_amazon_links')) {
            $url = sprintf($this->amazon_url[$this->_amazon_country_code],
                $item->ASIN, $this->amazon_tracking_id);
        } else {
            $url = $item->DetailPageURL;
        }

        return $url;
    }

    /**
     * @param $date
     * @return bool|string
     */
    public function getLocalizedDate($date)
    {
        if (!empty($date)) {
            $dt = new DateTime($date);

            $format = get_option('date_format');

            $date = date($format, $dt->format('U'));
        }

        return $date;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->_amazon_country_code;
    }

    /**
     * @return mixed
     */
    public function getAmazonShopUrl()
    {
        if ($this->amazon_shop_url == null) {
            $url = $this->amazon_url[$this->getCountryCode()];
            $this->amazon_shop_url = current(explode('exec', $url));
        }
        return $this->amazon_shop_url;
    }

    /**
     * @return mixed
     */
    public function getTrackingId()
    {
        return $this->amazon_tracking_id;
    }

    /**
     * @return bool
     */
    protected function _useCache()
    {
        if ((int)get_option('_asa_cache_skip_on_admin') === 1 && current_user_can('install_plugins')) {
            return false;
        }
        return true;
    }

}


$asa = new AmazonSimpleAdmin($wpdb);


/**
 * displays a collection
 */
function asa_collection ($label, $type=false, $tpl=false)
{
    global $asa;
    echo $asa->getCollection($label, $type, $tpl);
}

/**
 * returns the rendered collection
 */
function asa_get_collection ($label, $type=false, $tpl=false)
{
    global $asa;
    return $asa->getCollection($label, $type, $tpl);
}

/**
 * displays one item, can be used everywhere in php code, eg sidebar
 */
function asa_item ($asin, $tpl=false)
{
    global $asa;
    echo $asa->getItem($asin, $tpl);
}

/**
 * return the rendered product template
 * @param string $asin
 * @param string $tpl
 */
function asa_get_item($asin, $tpl=false)
{
    global $asa;
    return $asa->getItem($asin, $tpl);
}

/**
 * shortcode handler for [asa] tags
 * @param array $atts
 * @param string $content
 * @param string $code
 * @return string
 */
function asa_shortcode_handler($atts, $content=null, $code="") 
{
    $tpl = false;
    if (!empty($atts[0])) {
        $tpl = $atts[0];
    }
    return asa_get_item($content, $tpl);
}

// add the ajax actions
add_action('wp_ajax_asa_async_load', 'asa_async_load_callback');
add_action('wp_ajax_nopriv_asa_async_load', 'asa_async_load_callback');

/**
 * Load asynchronous
 */
function asa_async_load_callback() {
    global $asa;
    check_ajax_referer('amazonsimpleadmin', 'nonce');
    define('ASA_ASYNC_REQUEST', 1);

    $asin = esc_attr($_POST['asin']);
    $tpl = $asa->getTpl(esc_attr($_POST['tpl']), $asa->getDefaultTpl());
    $params = json_decode(esc_attr($_POST['params']));

    // debug
    //echo '<pre>' . print_r($_POST) . '</pre>';

    echo $asa->parseTpl($asin, $tpl, $params);
    exit;
}

/**
 * @param $var
 */
function asa_debug($var) {

    $debugFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'asadebug.txt';
    if (!is_writable($debugFile)) {
        return false;
    }

    $bt = debug_backtrace();
    $info = pathinfo($bt[0]['file']);

    $output = 'File: '. $info['basename'] . PHP_EOL .
        'Line: '. $bt[0]['line'] . PHP_EOL .
        'Time: '. date('Y/m/d H:i:s') . PHP_EOL .
        'Type: '. gettype($var) . PHP_EOL . PHP_EOL;

    if (is_array($var) || is_bool($var) || is_object($var)) {
        $output .= var_export($var, true);
    } else {
        $output .= $var;
    }

    $output .=
        PHP_EOL . PHP_EOL .
            '-------------------------------------------------------'.
            PHP_EOL . PHP_EOL;

    file_put_contents($debugFile, $output, FILE_APPEND);
}