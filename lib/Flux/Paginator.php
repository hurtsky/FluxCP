<?php
/**
 * The paginator helps in creating pages for SQL-stored data.
 */
class Flux_Paginator
{
    /**
     * Number of records.
     *
     * @var int
     */
    public $total = 0;

    /**
     * Current page.
     *
     * @var int
     */
    public $currentPage = 1;

    /**
     * Total number of pages.
     *
     * @var int
     */
    public $numberOfPages = 1;

    /**
     * Whether or not to show the page numbers even if there's only one page.
     *
     * @var bool
     */
    public $showSinglePage;

    /**
     * Records per-age.
     *
     * @var int
     */
    public $perPage;

    /**
     * The number of pages to display at once in the HTML pages output.
     *
     * @var int
     */
    public $pagesToShow;

    /**
     * GET variable holding the current page number.
     *
     * @var string
     */
    public $pageVariable;

    /**
     * Page separator used in the HTML pages generator.
     *
     * @var string
     */
    public $pageSeparator;

    /**
     * Array of sortable column names.
     *
     * @var array
     */
    protected $sortableColumns = [];

    /**
     * Current column sort order.
     *
     * @var array
     */
    public $currentSortOrder = [];

    /**
     * Original request URI.
     *
     * @var string
     */
    public $requestURI;

    /**
     * Create new paginator instance.
     *
     * @param int    $total      Number of record.
     * @param string $requestURI Original request URI.
     * @param array  $options    Paginator options.
     */
    public function __construct($total, $requestURI = null, array $options = [])
    {
        if (!$requestURI) {
            $requestURI = $_SERVER['REQUEST_URI'];
        }

        $this->requestURI = $requestURI;

        $perPage = Flux::config('ResultsPerPage');
        if (!$perPage) {
            $perPage = 20;
        }

        $pagesToShow = Flux::config('PagesToShow');
        if (!$pagesToShow) {
            $pagesToShow = 10;
        }

        $showSinglePage = (bool) Flux::config('ShowSinglePage');

        $options = array_merge(
            [
                'showSinglePage' => $showSinglePage,
                'perPage'        => $perPage,
                'pagesToShow'    => $pagesToShow,
                'pageVariable'   => 'p',
                'pageSeparator'  => '|', ],
            $options
        );

        $this->total = (int) $total;
        $this->showSinglePage = $options['showSinglePage'];
        $this->perPage = $options['perPage'];
        $this->pagesToShow = $options['pagesToShow'];
        $this->pageVariable = $options['pageVariable'];
        $this->pageSeparator = $options['pageSeparator'];
        $this->currentPage = isset($_GET[$this->pageVariable]) && $_GET[$this->pageVariable] > 0 ? $_GET[$this->pageVariable] : 1;

        $this->calculatePages();
    }

    /**
     * Calculate the number of pages.
     */
    private function calculatePages()
    {
        $this->numberOfPages = (int) ceil($this->total / $this->perPage);
    }

    /**
     * Get an SQL query with the "LIMIT offset,num" and appropriate "ORDER BY"
     * strings appended to the end.
     *
     * @param string $sql
     *
     * @return string
     */
    public function getSQL($sql)
    {
        $orderBy = false;

        foreach ($this->sortableColumns as $column => $value) {
            if (strpos($column, '.') !== false) {
                list($table, $column) = explode('.', $column, 2);
                $param = "{$table}_{$column}_order";
                $columnName = "`{$table}`.`{$column}`";
            } else {
                $table = false;
                $param = "{$column}_order";
                $columnName = "`$column`";
            }

            $sortValues = ['ASC', 'DESC', 'NONE'];

            // First, check if a GET parameter was passed for this column.
            if (isset($_GET[$param]) && in_array(strtoupper($_GET[$param]), $sortValues)) {
                $value = $_GET[$param];
            }

            // Check again just in case we're working with the default here.
            if (!is_null($value) && in_array(($value = strtoupper($value)), $sortValues) && $value != 'NONE') {
                $this->currentSortOrder[str_replace('`', '', $columnName)] = $value;

                if (!$orderBy) {
                    $sql .= ' ORDER BY';
                    $orderBy = true;
                }

                if ($value == 'ASC') {
                    $sql .= " (CASE WHEN $columnName IS NULL THEN 1 ELSE 0 END) ASC, $columnName ASC,";
                } else {
                    $sql .= " $columnName $value,";
                }
            }
        }

        if ($orderBy) {
            $sql = rtrim($sql, ',');
        }

        $offset = ($this->perPage * $this->currentPage) - $this->perPage;

        return "$sql LIMIT $offset,{$this->perPage}";
    }

    /**
     * Generate some basic HTML which creates a list of page numbers. Will
     * return an empty string if DisplaySinglePages config is set to false.
     *
     * @return string
     */
    public function getHTML()
    {
        if (!Flux::config('DisplaySinglePages') && $this->numberOfPages === 1) {
            return '';
        }

        $pages = [];
        $start = (floor(($this->currentPage - 1) / $this->pagesToShow) * $this->pagesToShow) + 1;
        $end = $start + $this->pagesToShow + 1;

        if ($end > $this->numberOfPages) {
            $end = $this->numberOfPages + 1;
        } else {
            $end = $end - 1;
        }

        $hasPrev = $start > 1;
        $hasNext = $end < $this->numberOfPages;

        for ($i = $start; $i < $end; $i++) {
            $request = $this->getPageURI($i);

            if ($i == $this->currentPage) {
                $pages[] = sprintf(
                    '<a title="Page #%d" class="page-num current-page">%d</a>',
                    $i, $i
                );
            } else {
                $pages[] = sprintf(
                    '<a href="%s" title="Page #%d" class="page-num">%d</a>',
                    $request, $i, $i
                );
            }
        }

        if ($hasPrev) {
            array_unshift($pages, sprintf('<a href="%s" title="Previous Pane (p#%d)" class="page-prev">Prev.</a> ', $this->getPageURI($start - 1), $start - 1));
        }

        if ($hasNext) {
            array_push($pages, sprintf(' <a href="%s" title="Next Pane (p#%d)" class="page-next">Next</a>', $this->getPageURI($end), $end));
        }

        $links = sprintf('<div class="pages">%s</div>', implode(" {$this->pageSeparator} ", $pages))."\n";

        if (Flux::config('ShowPageJump') && $this->numberOfPages > Flux::config('PageJumpMinimumPages')) {
            // This is some tricky shit.  Don't even attempt to understand it =(
            // Page jumping is entirely JavaScript dependent.
            $pageVar = preg_quote($this->pageVariable);
            $event = "location.href='".$this->getPageURI(0)."'";
            $event = preg_replace("/$pageVar=0/", "{$this->pageVariable}='+this.value+'", $event);
            $jump = '<label>Page Jump: <input type="text" name="jump_to_page" id="jump_to_page" size="4" onkeypress="if (event.keyCode == 13) { %s }" /></label>';
            $jump = sprintf($jump, $event);
            $links .= sprintf('<div class="jump-to-page">%s</div>', $jump);
        }

        if (!$this->showSinglePage && $this->numberOfPages === 1) {
            return;
        } else {
            return $links;
        }
    }

    /**
     * Create a link to the current request with a different page number.
     *
     * @param int $pageNumber
     *
     * @return string
     */
    protected function getPageURI($pageNumber)
    {
        $request = preg_replace('/(\?.*)$/', '', $this->requestURI);
        $qString = $_SERVER['QUERY_STRING'];
        $pageVar = preg_quote($this->pageVariable);
        $pageNum = (int) $pageNumber;

        $qStringVars = [];
        $qStringLines = preg_split('/&/', $qString, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($qStringLines as $qStringVar) {
            if (strpos($qStringVar, '=') !== false) {
                list($qStringKey, $qStringVal) = explode('=', $qStringVar, 2);
                $qStringVars[$qStringKey] = $qStringVal;
            }
        }

        $qStringVars[$pageVar] = $pageNum;
        $qStringLines = [];

        foreach ($qStringVars as $qStringKey => $qStringVal) {
            $qStringLines[] = sprintf('%s=%s', $qStringKey, $qStringVal);
        }

        return sprintf('%s?%s', $request, implode('&', $qStringLines));
    }

    /**
     * Specify an array (or a string single column name) of columns that are
     * sortable by the paginator's features.
     *
     * @param array $columns
     *
     * @return array
     */
    public function setSortableColumns($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $key => $column) {
            if (!is_numeric($key)) {
                $value = $column;
                $column = $key;
            } else {
                $value = null;
            }

            $this->sortableColumns[$column] = $value;
        }

        return $this->sortableColumns;
    }

    /**
     * Get an HTML anchor which automatically links to the current request
     * based on current sorting conditions and sets ascending/descending
     * sorting parameters accordingly.
     *
     * @param string $column
     * @param string $name
     *
     * @return string
     */
    public function sortableColumn($column, $name = null)
    {
        if (!$name) {
            $name = $column;
        }

        if (!array_key_exists($column, $this->sortableColumns)) {
            return htmlspecialchars($name);
        } else {
            if (strpos($column, '.') !== false) {
                list($_table, $_column) = explode('.', $column, 2);
                $param = "{$_table}_{$_column}_order";
            } else {
                $param = "{$column}_order";
            }

            $order = 'asc';
            $format = '<a href="%s" class="sortable">%s</a>';
            $name = htmlspecialchars($name);
            $request = $_SERVER['REQUEST_URI'];

            if (isset($this->currentSortOrder[$column])) {
                switch (strtolower($this->currentSortOrder[$column])) {
                    case 'asc':
                        $order = 'desc';
                        $name .= Flux::config('ColumnSortAscending');
                        break;
                    case 'desc':
                        $order = is_null($this->sortableColumns[$column]) ? false : 'none';
                        $name .= Flux::config('ColumnSortDescending');
                        break;
                    default:
                        $order = 'asc';
                        break;
                }
            }

            if ($order) {
                $value = "$param=$order";
                if (preg_match("/$param=(\w*)/", $request)) {
                    $request = preg_replace("/$param=(\w*)/", $value, $request);
                } elseif (empty($_SERVER['QUERY_STRING'])) {
                    $request = "$request?$value";
                } else {
                    $request = "$request&$value";
                }

                return sprintf($format, $request, $name);
            } else {
                $request = rtrim(preg_replace("%(?:(\?)$param=(?:\w*)&?|&?$param=(?:\w*))%", '$1', $request), '?');

                return sprintf($format, $request, $name);
            }
        }
    }

    public function infoText()
    {
        $currPage = $this->currentPage;
        $results = $this->perPage;
        $infoText = sprintf(
            Flux::message('FoundSearchResults'),
            $this->total, $this->numberOfPages, ($currPage * $results - ($results - 1)), $currPage * $results < $this->total ? ($currPage * $results) : ($this->total)
        );

        return sprintf('<p class="info-text">%s</p>', $infoText);
    }
}
