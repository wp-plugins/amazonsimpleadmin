<?php
class AsaCollectionImport
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var AsaCollection
     */
    protected $_collectionMapper;

    /**
     * @var string
     */
    protected $_error;

    /**
     * @var string
     */
    protected $_item_name_singular = 'asa2_collection';

    /**
     * @var string
     */
    protected $_item_name_plural = 'asa2_collections';

    /**
     * @var array
     */
    protected $_importedCollections = array();



    /**
     * @param $file
     */
    public function __construct($file, AsaCollection $collectionMapper)
    {
        $this->_file = $file;
        $this->_collectionMapper = $collectionMapper;
    }

    /**
     * @return bool|int
     */
    public function import()
    {
        if (empty($this->_file)) {
            $this->_error = __('Please select a valid import file.', 'asa1');
            return false;
        }

        $xml = simplexml_load_file($this->_file);

        // check for valid xml
        if (!$xml) {
            $this->_error = __('Please select a valid import file.', 'asa1');
            return false;
        }

        $items = $this->_getItems($xml);

        if (count($items) == 0) {
            $this->_error = __('No items found in import file.', 'asa1');
            return;
        }

//        var_dump($items);

        $this->_import($items);
    }

    protected function _import($items)
    {
        foreach ($items as $collection) {

            // check name
            $counter = 0;

            do {
                if ($counter > 0) {
                    $nameFormat = '%s_%d';
                } else {
                    $nameFormat = '%s';
                }
                $collectionName = sprintf($nameFormat, $collection['name'], $counter);

                $nameExits = $this->_collectionMapper->checkLabel($collectionName);
                $counter++;
            } while ($nameExits !== null);

            $this->_collectionMapper->create($collectionName);
            $newId = $this->_collectionMapper->getId($collectionName);

            foreach ($collection['item'] as $item) {

                $asin = (string)$item->asin;
                if (!empty($asin)) {
                    $this->_collectionMapper->addAsin((string)$item->asin, $newId);
                }
            }

            array_push($this->_importedCollections, $collectionName);
        }
    }

    /**
     * @param $xml
     * @return array
     */
    protected function _getItems($xml)
    {
        $items = array();
        $itemNameCol = 'name';

        // check if xml contains items
        if (count($xml->{$this->_item_name_singular}) == 0) {
            // no items found
            return $items;
        }

        foreach($xml->{$this->_item_name_singular} as $item) {

            $tmpItem = array();

            /**
             * @var SimpleXMLElement $col
             */
            foreach($item as $col) {

                $attr = $col->attributes();

                if (isset($attr[$itemNameCol])) {
                    $tmpItem[(string)$col[$itemNameCol]] = (string)$col;
                } else {
                    foreach (get_object_vars($col) as $colVar => $colVal) {
                        if (is_array($colVal) && !empty($colVal)) {
                            $tmpItem[$colVar] = $colVal;
                        }
                    }
                }
            }

            if (is_array($tmpItem)) {
                array_push($items, $tmpItem);
            }
        }

        return $items;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @param AsaCollection $collectionMapper
     */
    public function setCollectionMapper($collectionMapper)
    {
        $this->_collectionMapper = $collectionMapper;
    }

    /**
     * @return array
     */
    public function getImportedCollections()
    {
        return $this->_importedCollections;
    }
}
