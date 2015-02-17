<?php
class AsaCollection {

    /**
     * wpdb object
     */
    protected $db;
    
    protected $db_version = '1.0';
    
    
    
    /**
     * constructor
     */
    public function __construct ($wpdb) 
    {        
        $this->db = $wpdb;
    }
    
    /**
     * initializes the database tables
     */
    public function initDB ()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $t1 = '
            CREATE TABLE `'. $this->db->prefix .'asa_collection` (
              `collection_id` int(11) NOT NULL auto_increment,
              `collection_label` varchar(255) NOT NULL,
              PRIMARY KEY  (`collection_id`),
              UNIQUE KEY `collection_label` (`collection_label`)
            )
        ';        
        dbDelta($t1);
        
        $t2 = '
            CREATE TABLE `'. $this->db->prefix .'asa_collection_item` (
              `collection_item_id` int(11) NOT NULL auto_increment,
              `collection_id` int(11) NOT NULL,
              `collection_item_asin` varchar(20) NOT NULL,
              `collection_item_timestamp` datetime NOT NULL,
              PRIMARY KEY  (`collection_item_id`),
              UNIQUE KEY `collection_item_asin` (`collection_id`,`collection_item_asin`)
            )
        ';        
        dbDelta($t2);
        
        add_option('_asa_db_collections_version', $this->db_version);
    }
    
    /**
     * 
     */
    public function create ($label) 
    {
        if ($this->checkLabel($label) === null) {
        
            $sql = '
                INSERT INTO `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL .'`
                    (collection_label)
                VALUES
                    ("'. $this->db->escape($label) .'")
            ';
            
            return ($this->db->query($sql) === 1);
            
        }
        
        return false;
    }
    
    /**
     * 
     */
    public function delete ($collection_id)
    {
        $sql = '
            DELETE FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL_ITEM .'`
            WHERE collection_id = '. $this->db->escape($collection_id) .'
        ';
        
        $this->db->query($sql);
        
        $sql = '
            DELETE FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL .'`
            WHERE collection_id = '. $this->db->escape($collection_id) .'
        ';
        
        $this->db->query($sql);
    }

    /**
     * @param $collection_id
     * @param $country_code
     */
    public function export($collection_id, $country_code)
    {
        $collections = array();

        if (is_numeric($collection_id)) {
            // single collection
            $collections = array($collection_id);
        } elseif (is_array($collection_id)) {
            // multiple collections
            $collections = $collection_id;
        }

        $result = "<asa2_collections>\n";

        foreach ($collections as $collId) {

            $collectionName = $this->getLabel($collId);
            $filename = 'ASA1_collection_export_'. date('Y-m-d_H_i_s');
            $items = $this->getItems($collId);

            $result .= "\t<asa2_collection>\n";

            $result .= "\t\t" . '<column name="name">'. $collectionName .'</column>' . "\n";
            $result .= "\t\t<items>\n";

            foreach ($items as $item) {
                $result .= "\t\t\t<item>\n";
                $result .= "\t\t\t\t<asin>" . $item->collection_item_asin . "</asin>\n";
                $result .= "\t\t\t\t<country_code>" . $country_code . "</country_code>\n";

                $result .= "\t\t\t</item>\n";
            }

            $result .= "\t\t</items>\n";

            $result .= "\t</asa2_collection>\n";
        }


        $result .= "</asa2_collections>\n";

        $xml = new SimpleXMLElement($result);

        $filename .= '.xml';

        header('Content-disposition: attachment; filename="'. $filename .'"');
        header('Content-type: "text/xml"; charset="utf8"');
        echo $xml->asXML();
        exit;
    }
    
    /**
     * 
     */
    public function getAll ()
    {
        $collections = array();
        
        $sql = '
            SELECT *
            FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL .'`
            ORDER by collection_label
        ';
        
        $result = $this->db->get_results($sql);
        
        foreach ($result as $row) {
            $collections[$row->collection_id] = $row->collection_label;            
        }
        
        return $collections;
    }
    
    /**
     * 
     */
    public function checkAsin ($asin, $collection)
    {
        $sql = '
            SELECT collection_item_id as id
            FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL_ITEM .'`
            WHERE collection_id = "'. $this->db->escape($collection) .'"
                AND collection_item_asin = "'. $this->db->escape($asin) .'"
        ';
        
        return $this->db->get_var($sql);
    }
    
    /**
     * 
     */
    public function checkLabel ($label)
    {
        if (empty($label)) {
            return false;
        }
        
        $sql = '
            SELECT collection_id
            FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL .'`
            WHERE collection_label = "'. $this->db->escape($label) .'"
        ';
        
        return $this->db->get_var($sql);
    }    
    
    /**
     * 
     */
    public function addAsin ($asin, $collection_id)
    {
        $sql = '
            INSERT INTO `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL_ITEM .'`
                (collection_id, collection_item_asin, collection_item_timestamp)
            VALUES
                ('. $this->db->escape($collection_id) .',
                 "'. $this->db->escape($asin) .'", NOW())
        ';
        
        return ($this->db->query($sql) === 1);
    }    
    
    /**
     * 
     */
    public function deleteAsin ($item_id)
    {
        $sql = '
            DELETE FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL_ITEM .'`
            WHERE collection_item_id = '. $this->db->escape($item_id) .'
        ';
        
        return $this->db->query($sql);
    }    
    
    /**
     * 
     */
    public function getLabel ($collection_id)
    {
        $sql = '
            SELECT collection_label as label
            FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL .'`
            WHERE collection_id = "'. $this->db->escape($collection_id) .'"
        ';
        
        return $this->db->get_var($sql);
    }
    
    /**
     * 
     */
    public function getId ($collection_label)
    {
        $sql = '
            SELECT collection_id
            FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL .'`
            WHERE collection_label = "'. $this->db->escape($collection_label) .'"
        ';
        
        return $this->db->get_var($sql);
    }    
    
    /**
     * renders a collection form select field
     */
    public function getSelectField ($id='collection', $selected=false)
    {
        $collections = $this->getAll();
        
        $html = '<select name="'. $id .'" id="'. $id .'">';
        
        foreach ($collections as $k => $v) {
            if ($k == $selected) {
                $s = ' selected="selected"';
            } else {
                $s = '';
            }
            $html .= '<option value="'. $k .'"'. $s .'>'. $v . '</option>';    
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    /**
     * get all collection items
     * 
     * @param     int        collection id
     * @return     array    items
     */
    public function getItems ($collection_id)
    {        
        $sql = '
            SELECT collection_item_id, collection_item_asin, 
                UNIX_TIMESTAMP(collection_item_timestamp) as timestamp
            FROM `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL_ITEM .'`
            WHERE collection_id = "'. $this->db->escape($collection_id) .'"
            ORDER by collection_item_timestamp DESC
        ';
        
        return $this->db->get_results($sql);
    }
    
    /**
     * 
     */
    public function updateItemTimestamp ($item_id)
    {
        $sql = '
            UPDATE `'. $this->db->prefix . AmazonSimpleAdmin::DB_COLL_ITEM .'`
            SET collection_item_timestamp = NOW()
            WHERE collection_item_id = '. $this->db->escape($item_id) .'
        ';
        
        return $this->db->query($sql);
    }
    
}
?>