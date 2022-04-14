<?php
/**
 * Created by PhpStorm.
 * User: quantm
 * Date: 4/17/2016
 * Time: 6:47 PM
 */

namespace Q\Views;


class Paginator
{
    private $currentPage;
    private $count;
    private $perPage;
    private $lastPage;
    private $data;
    private $linkLimit = 7;

    /**
     * @param array |\stdClass data
     * @param int $currentPage
     * @param int $count
     * @param int $perPage
     * @param int $linkLimit
     */
    public function __construct($data, $currentPage, $count, $perPage = 25, $linkLimit = 7)
    {
        $this->data = $data;
        $this->currentPage = (int)$currentPage;
        $this->count = (int) $count;
        $this->perPage = 25;
        $this->lastPage = ceil($count/$perPage);
        $this->linkLimit = $linkLimit;
    }

    /**
     * @return int
     */
    public function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function perPage()
    {
        return $this->perPage;
    }

    /**
     * @return float
     */
    public function lastPage()
    {
        return $this->lastPage;
    }

    /**
     * @return int
     */
    public function pageCount()
    {
        return $this->lastPage;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @return string
     */
    public function render()
    {
        if ($this->lastPage <= 1) {
            return '' ;
        }

        $this->linkLimit = 7;
        $startClass = $this->currentPage == 1 ? 'disabled' : '';
        $html = '<ul class="pagination"><li class="%s"><a href="%s">«</a></li><li class="%s"><a href="%s">‹</a></li>';

        $html = sprintf(
            $html, 
            $startClass, 
            self::url(null, ['page' => 1]), 
            $startClass, 
            self::url(null, ['page' => $this->currentPage - 1])
        );

        for ($i = 1; $i <= $this->lastPage; $i++) {

            $halfTotalLinks = floor($this->linkLimit / 2);
            $from = $this->currentPage - $halfTotalLinks;
            $to = $this->currentPage + $halfTotalLinks;
            if ($this->currentPage < $halfTotalLinks) {
                $to += $halfTotalLinks - $this->currentPage;
            }
            if ($this->lastPage - $this->currentPage < $halfTotalLinks) {
                $from -= $halfTotalLinks - ($this->lastPage - $this->currentPage) - 1;
            }

            if ($from < $i && $i < $to) {
                $html .= sprintf('<li class="%s"><a href="%s">%d</a></li>',
                    $this->currentPage == $i ? 'active' : '',
                    self::url(null, ['page' => $i]),
                    $i);
            }
        }

        $endClass = $this->currentPage == $this->lastPage ? 'disabled' : '';
        $url = self::url(null, ['page' => $this->currentPage == $this->lastPage? $this->lastPage : $this->currentPage + 1]);

        $html .= sprintf('<li class="%s"><a href="%s">›</a></li><li class="%s"><a href="%s">»</a></li></ul>'
            , $endClass
            , $url
            , $endClass
            , self::url(null, ['page' => $this->lastPage])
        );

        return $html;
    }

    /**
     * @param $field
     * @return string
     */
    public function sortClass($field)
    {
        $params = $_GET;

        if (isset ($params['orderBy'])) {
            if (!isset ($params['orderDirection'])) {
                $params['orderDirection'] = 'asc';
            }

            if ($params['orderBy'] === $field) {
                return 'sorting_' . strtolower($params['orderDirection']);
            }
        }

        return 'sorting';
    }

    /**
     * @param $text
     * @param $field
     * @return string
     */
    public function sortLink($text, $field)
    {
        $queryData = array(
            'orderBy' => $field,
        );

        if (!empty ($_GET)) {
            $queryData = array_merge($_GET, $queryData);
        }

        if (empty ($queryData['orderDirection'])) {
            $queryData['orderDirection'] = 'asc';
        } else {
            $queryData['orderDirection'] =  strtolower($queryData['orderDirection']) === 'asc' ? 'desc' : 'asc';
        }

        return sprintf('<a href="%s">%s</a>', self::url(null, $queryData), $text);
    }

    /**
     * @param null $uri
     * @param array $query_data
     * @return string
     */
    public static function  url($uri = null, array $query_data = array())
    {
        if ($uri === null) {
            list($url) = explode('?', $_SERVER['REQUEST_URI']);
            if (!empty ($_GET)) {
                $query_data = array_merge($_GET, $query_data);
            }

        } else {
            static $baseUri;

            if (empty ($baseUri)) {
                $baseUri = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
            }

            $url = $baseUri . '/' . trim($uri, '/');
        }

        if (!empty ($query_data)) {
            $url = $url. '?'. http_build_query($query_data);
        }

        return $url;
    }

}