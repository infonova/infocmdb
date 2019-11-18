<?php


class Zend_View_Helper_CiBreadcrumb extends Zend_View_Helper_Abstract
{

    public function ciBreadcrumb($bread, $crumbDepth = null, $type = 'html', $reverse = false)
    {

        $crumbs         = array();
        $crumbSeparator = ' &gt; ';

        if ($reverse === true) {
            $crumbSeparator = ' &lt; ';
        }

        if ($type === 'text') {
            $crumbSeparator = ' > ';
            if ($reverse === true) {
                $crumbSeparator = ' < ';
            }
        }

        if ($crumbDepth === null) {
            $config     = Zend_Registry::get('viewConfig');
            $crumbDepth = $config->ci->detail->breadcrums->depth;

            if ($crumbDepth == null) {
                $crumbDepth = 3;
            }
        }

        $crumbAmount = count($bread);
        if ($crumbDepth && $crumbDepth < $crumbAmount) {
            $toCut                   = $crumbAmount - $crumbDepth - 1;
            $bread                   = array_slice($bread, $toCut);
            $bread[0]['description'] = '..';
        }

        foreach ($bread as $crumb) {
            $crumbs[] = $this->getCrumb($crumb, $type);
        }

        if ($reverse === true) {
            $crumbs = array_reverse($crumbs);
        }

        $fullBreadCrum = join($crumbSeparator, $crumbs);

        return $fullBreadCrum;
    }

    protected function getCrumb($crumb, $type)
    {
        $crumbText = $crumb['description'];

        if ($type === 'text') {
            return $crumbText;
        }

        // wrap bold tag over last element
        if (isset($crumb['crumbType']) && $crumb['crumbType'] == 'ci') {
            $crumbText = '<b>' . $crumbText . '</b>';
        }

        $crumbLink = '';
        if (isset($crumb['crumbLink'])) {
            $crumbLink = $crumb['crumbLink'];
        } elseif (!isset($crumb['crumbType']) || $crumb['crumbType'] == 'ci_type') {
            $crumbLink = APPLICATION_URL . 'ci/index/typeid/' . $crumb[Db_CiType::ID];
        }

        if (!empty($crumbLink)) {
            return '<a href ="' . $crumbLink . '">' . $crumbText . '</a>';
        }

        return $crumbText;
    }

    /**
     * Lazily fetches Helper Instance.
     *
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    public function _getCiBreadcrumb()
    {
        if (null === $this->_breadcrumb) {
            $this->_breadcrumb =
                Zend_Controller_Action_HelperBroker::getStaticHelper(
                    'Breadcrumb');
        }
        return $this->_breadcrumb;
    }

}