<?php

namespace Context\Page\Product;

use Context\Page\Base\Form;
use Behat\Mink\Exception\ElementNotFoundException;
use Pim\Bundle\CatalogBundle\Entity\AttributeGroup;

/**
 * Product edit page
 *
 * @author    Gildas Quéméner <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Edit extends Form
{
    /**
     * @var string $path
     */
    protected $path = '/enrich/product/{id}/edit';

    /**
     * {@inheritdoc}
     */
    public function __construct($session, $pageFactory, $parameters = array())
    {
        parent::__construct($session, $pageFactory, $parameters);

        $this->elements = array_merge(
            $this->elements,
            array(
                'Locales dropdown' => array('css' => '#locale-switcher'),
                'Locales selector' => array('css' => '#pim_product_locales'),
                'Enable switcher'  => array('css' => '#switch_status'),
                'Updates grid'     => array('css' => '#history table.grid'),
                'Image preview'    => array('css' => '#lbImage'),
                'Completeness'     => array('css' => 'div#completeness'),
                'Updates grid'     => array('css' => '#history table.grid'),
                'Category pane'    => array('css' => '#categories'),
                'Category tree'    => array('css' => '#trees'),
            )
        );
    }

    /**
     * @param string $locale
     * @param string $content
     *
     * @return NodeElement|null
     */
    public function findLocaleLink($locale, $content = null)
    {
        $link = $this->getElement('Locales dropdown')->findLink($locale);

        if (!$link) {
            throw new ElementNotFoundException(sprintf('Locale %s link not found', $locale));
        }

        if ($content) {
            if (strpos($link->getText(), $content) === false) {
                return null;
            }
        }

        return $link;
    }

    /**
     * @param string $language
     */
    public function selectLanguage($language)
    {
        $this->getElement('Locales selector')->selectOption(ucfirst($language), true);
    }

    /**
     * @param string $locale
     */
    public function switchLocale($locale)
    {
        $this->getElement('Locales dropdown')->clickLink($locale);
    }

    /**
     * @param string $locale
     * @param string $label
     *
     * @return NodeElement
     */
    public function findLocale($locale, $label)
    {
        return $this->getElement('Locales dropdown')->find(
            'css',
            sprintf(
                'a:contains("%s"):contains("%s")',
                strtoupper($locale),
                $label
            )
        );
    }

    /**
     * @param string $group
     *
     * @return integer
     */
    public function getFieldsCountFor($group)
    {
        return count($this->getFieldsForGroup($group));
    }

    /**
     * @param string $group
     *
     * @return NodeElement
     */
    public function getFieldsForGroup($group)
    {
        $locator = sprintf('#tabs-%s label', $group instanceof AttributeGroup ? $group->getId() : 0);

        return $this->findAll('css', $locator);
    }

    /**
     * @param string $name
     *
     * @return NodeElement
     */
    public function findField($name)
    {
        $label = $this->find('css', sprintf('label:contains("%s")', $name));

        if (!$label) {
            throw new ElementNotFoundException($this->getSession(), 'form label ', 'value', $name);
        }

        $field = $label->getParent()->find('css', 'input');

        if (!$field) {
            throw new ElementNotFoundException($this->getSession(), 'form field ', 'id|name|label|value', $name);
        }

        return $field;
    }

    /**
     * @param string $field
     *
     * @return NodeElement
     */
    public function getRemoveLinkFor($field)
    {
        return $this->find('css', sprintf('.control-group:contains("%s") .remove-attribute', $field));
    }

    /**
     * Disable a product
     *
     * @return Edit
     */
    public function disableProduct()
    {
        $this->getElement('Enable switcher')->click();

        return $this;
    }

    /**
     * Enable a product
     *
     * @return Edit
     */
    public function enableProduct()
    {
        $this->getElement('Enable switcher')->click();

        return $this;
    }

    /**
     * @return integer
     */
    public function countUpdates()
    {
        return count($this->getElement('Updates grid')->findAll('css', 'tbody tr'));
    }

    /**
     * @return NodeElement|void
     */
    public function getImagePreview()
    {
        $preview = $this->getElement('Image preview');

        if (!$preview || false === strpos($preview->getAttribute('style'), 'display: block')) {
            return;
        }

        return $preview;
    }

    /**
     * Get the completeness content
     *
     * @return \Behat\Mink\Element\NodeElement
     */
    public function findCompletenessContent()
    {
        $completenessContent = $this->getElement('Completeness')->find('css', 'table#progress-table');
        if (!$completenessContent) {
            throw new \InvalidArgumentException('Completeness content not found !!!');
        }

        return $completenessContent;
    }

    /**
     * Find completeness channel
     * @param string $name the channel name
     *
     * @throws \InvalidArgumentException
     * @return \Behat\Mink\Element\NodeElement
     */
    public function findCompletenessScope($name)
    {
        $channel = $this->findCompletenessContent()
             ->find(sprintf('th .channel:contains("%s")', $name));

        if (!$channel) {
            throw new \InvalidArgumentException(sprintf('Completeness for channel %s not found', $name));
        }

        return $channel;
    }

    /**
     * Find completeness locale
     * @param string $code
     *
     * @throws \InvalidArgumentException
     * @return boolean
     */
    public function findCompletenessLocale($code)
    {
        $flag   = $this->findCompletenessContent()
            ->find('css', sprintf('td img.flag'));
        $locale = $this->findCompletenessContent()
            ->find('css', sprintf('td code.flag-language:contains("%s")'));

        if ($flag && $locale) {
            return true;
        } else {
            throw new \InvalidArgumentException(sprintf('Completeness for locale %s not found', $code));
        }
    }

    /**
     * Find a completeness cell from column and row (channel and locale codes)
     * @param string $columnCode (channel code)
     * @param string $rowCode    (locale code)
     *
     * @throws \InvalidArgumentException
     *
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function findCompletenessCell($columnCode, $rowCode)
    {
        $completenessTable = $this->findCompletenessContent();

        $columnIdx = 0;
        foreach ($completenessTable->findAll('css', 'thead th') as $index => $header) {
            if ($header->getText() === $columnCode) {
                $columnIdx = $index;
                break;
            }
        }
        if ($columnIdx === 0) {
            throw new \InvalidArgumentException(sprintf('Column %s not found', $columnCode));
        }

        $cells = $completenessTable->findAll('css', sprintf('tbody tr:contains("%s") td', $rowCode));
        if (!$cells || count($cells) < $columnIdx) {
            throw new \InvalidArgumentException(sprintf('Row %s not found', $rowCode));
        }

        return $cells[$columnIdx];
    }

    /**
     * Check completeness state
     * @param string $channelCode
     * @param string $localeCode
     * @param string $state
     *
     * @throws \InvalidArgumentException
     */
    public function checkCompletenessState($channelCode, $localeCode, $state)
    {
        $completenessCell = $this
            ->findCompletenessCell($channelCode, $localeCode)
            ->find('css', 'div.progress-cell');

        // check progress bar type
        if (!$completenessCell->find('css', sprintf('div.bar-%s', $state))) {
            throw new \InvalidArgumentException(
                sprintf('Progress bar is not %s for %s:%s', $state, $channelCode, $localeCode)
            );
        }
    }

    /**
     * Check completeness message
     * @param string $channelCode
     * @param string $localeCode
     * @param string $info
     *
     * @throws \InvalidArgumentException
     */
    public function checkCompletenessMessage($channelCode, $localeCode, $info)
    {
        $completenessCell = $this
            ->findCompletenessCell($channelCode, $localeCode)
            ->find('css', 'div.progress-cell');

        // check message displayed bottom to the progress bar
        $infoPassed = ($info === 'Complete')
            ? ($completenessCell->getText() === $info)
            : $completenessCell->find('css', sprintf('span.progress-info:contains("%s")', $info));
        if (!$infoPassed) {
            throw new \InvalidArgumentException(
                sprintf('Message %s not found for %s:%s', $info, $channelCode, $localeCode)
            );
        }
    }

    /**
     * Check completeness ratio
     * @param string $channelCode
     * @param string $localeCode
     * @param string $ratio
     *
     * @throws \InvalidArgumentException
     */
    public function checkCompletenessRatio($channelCode, $localeCode, $ratio)
    {
        $completenessCell = $this
            ->findCompletenessCell($channelCode, $localeCode)
            ->find('css', 'div.progress-cell');

        // check progress bar width
        $title = $completenessCell
            ->find('css', 'div.progress')
            ->getAttribute('data-original-title');

        $pattern = sprintf('/^%s%% complete/', $ratio);
        if (!$title || preg_match($pattern, $title) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Ratio %s not found for %s:%s', $ratio, $channelCode, $localeCode)
            );
        }
    }

    /**
     * Find legend div
     * @throws \InvalidArgumentException
     * @return \Behat\Mink\Element\NodeElement
     */
    public function findCompletenessLegend()
    {
        $legend = $this->getElement('Completeness')->find('css', 'div#legend');
        if (!$legend) {
            throw new \InvalidArgumentException('Legend content not found !!!');
        }

        return $legend;
    }

    /**
     * @return array
     */
    public function getHistoryRows()
    {
        return $this->getElement('Updates grid')->findAll('css', 'tbody tr');
    }

    /**
     * @param string $category
     *
     * @return CategoryView
     */
    public function selectTree($category)
    {
        $link = $this->getElement('Category pane')
            ->find('css', sprintf('#trees-list li a:contains(%s)', $category));
        $link->click();

        return $this;
    }

    /**
     * @param string $category
     *
     * @return CategoryView
     */
    public function expandCategory($category)
    {
        $category = $this->findCategoryInTree($category);
        $category->getParent()->find('css', 'ins')->click();

        return $this;
    }

    /**
     * @param string $category
     *
     * @return NodeElement
     *
     * @throws \InvalidArgumentException
     */
    public function findCategoryInTree($category)
    {
        $elt = $this->getElement('Category tree')->find('css', sprintf('li a:contains(%s)', $category));
        if (!$elt) {
            throw new \InvalidArgumentException(sprintf('Unable to find category "%s" in the tree', $category));
        }

        return $elt;
    }
}
