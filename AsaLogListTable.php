<?php
if(!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AsaLogListTable extends WP_List_Table
{
    /**
     * @var AsaLogger
     */
    protected $_logger;

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'          => 'ID',
            'type'        => 'Type',
            'message'     => 'Message',
            'extra'       => 'Text',
            'timestamp'   => 'Timestamp',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('timestamp' => array('timestamp', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        return $this->_logger->fetchAll();
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'message':
            case 'timestamp':
                return $item[ $column_name ];
                break;

            case 'type':
                require_once 'AsaLogger.php';
                $type = (int)$item[$column_name];
                if ($type == AsaLogger::LOG_TYPE_ERROR) {
                    return __('Error', 'asa1');
                }
                break;

            case 'extra':
                return nl2br($item[$column_name]);

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @param $a
     * @param $b
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'timestamp';
        $order = 'desc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'clear' => __('Clear log'),
        );

        return $actions;
    }

    /**
     * @param AsaLogger $logger
     */
    public function setLogger(AsaLogger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->_logger;
    }

}
 