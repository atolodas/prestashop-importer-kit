<?php
/**
 * Array.php
 *
 * @package PrestashopImporterKit
 * @author  Michael Pratt <pratt@hablarmierda.net>
 * @link    http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class reads an array files and allows you to Iterate over
 * the values using a foreach loop.
 */
class Pik_Reader_Array implements Pik_Reader_Interface
{
    /** @var array  Array with configuration directives */
    protected $config = array();

    /** @var array  Array with the name of each column */
    protected $columns = array();

    /** @var object Instance of SplFileObject containing a csv file */
    protected $csv;

    /**
     * Construct
     *
     * @param string $file The full location of the file
     * @param string $delimiter CSV delimiter
     * @param string $multiDelimiter Delimiter for multiple values
     * @return void
     *
     */
    public function __construct($file, array $options = array())
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException(
                sprintf('the file "%s" doesnt exist', $file)
            );
        }

        $this->config = array_merge(array(
            'delimiter' => ';',
            'multiple_delimiter' => ',',
            'utf8' => true,
            'splitable_fields' => array('images', 'categories', 'category', 'attribute', 'value'),
        ), $options);

        $this->csv = new SplFileObject($file);
        $this->csv->setCsvControl($this->config['delimiter']);

        /**
         * @link http://www.php.net/manual/en/splfileobject.setflags.php
         * @link http://www.php.net/manual/en/class.splfileobject.php#splfileobject.constants.drop-new-line
         */
        $this->csv->setFlags(
            SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY
        );
    }

    /**
     * Sets the columns for the file
     *
     * @param array $columns the name of each column. When none are given, then the first line of the csv file is used to create them.
     * @return void
     */
    public function setColumns(array $columns = array())
    {
        $this->columns = $columns;
    }

    /**
     * Normalizes the column name
     *
     * @param string $column
     * @return string
     */
    protected function normalizeColumn($column)
    {
        if ($this->config['utf8']) {
            $column = utf8_encode($column);
        }

        $column = strtolower(trim($column));
        return trim(preg_replace('~[^\w\d]~', '_', $column), '_');
    }

    /**
     * Retrieve current line of file as a CSV array.
     * Use the column names as a key
     *
     * @return array
     */
    public function current()
    {
        $return = array();
        $data = array_combine($this->columns, $this->csv->current());
        foreach ($data as $k => $v) {
            if ($this->config['utf8']) {
                $v = utf8_encode($v);
            }

            if (in_array($k, $this->config['splitable_fields'])) {
                $v = explode($this->config['multiple_delimiter'], $v);
            }

            $return[$k] = $v;
        }

        return $return;
    }

    /**
     * Rewind the file to the first line and extract/normalize
     * the column names.
     *
     * TODO: There is a minor bug when the rewind method is called twice in a row and the column
     * names are extracted from the first line of the csv file. The first time, it runs as expected
     * but the second run might give an aditional array consisting of an array with the column
     * names as keys and values.
     *
     * @return void
     */
    public function rewind()
    {
        $this->csv->rewind();
        if (empty($this->columns)) {
            $this->columns = $this->csv->current();
            $this->csv->next();
        }

        $this->columns = array_map(array($this, 'normalizeColumn'), $this->columns);
    }

    /**
     * Checks that the pointer is not at the end of the file
     *
     * @return bool
     */
    public function valid()
    {
        return $this->csv->valid();
    }

    /**
     * Returns the line number, where the pointer
     * is currently at.
     *
     * @return int
     */
    public function key()
    {
        return $this->csv->key();
    }

    /**
     * Moves the pointer to the next line
     *
     * @return void
     */
    public function next()
    {
       $this->csv->next();
    }

    public function count()
    {
        return iterator_count($this->csv);
    }
}
?>
