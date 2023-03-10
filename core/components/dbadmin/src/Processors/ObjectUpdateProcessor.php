<?php
/**
 * Abstract update processor
 *
 * @package dbadmin
 * @subpackage processors
 */

namespace Sergant210\dbAdmin\Processors;

use Sergant210\dbAdmin\dbAdmin;
use modObjectUpdateProcessor;
use modX;

/**
 * Class ObjectUpdateProcessor
 */
class ObjectUpdateProcessor extends modObjectUpdateProcessor
{
    public $languageTopics = ['dbadmin:default'];

    /** @var dbAdmin $dbadmin */
    public $dbadmin;

    protected $required = [];

    /**
     * {@inheritDoc}
     * @param modX $modx A reference to the modX instance
     * @param array $properties An array of properties
     */
    public function __construct(modX &$modx, array $properties = [])
    {
        parent::__construct($modx, $properties);

        $corePath = $this->modx->getOption('dbadmin.core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/');
        $this->dbadmin = $this->modx->getService('dbadmin', dbAdmin::class, $corePath . 'model/dbadmin/');
    }

    /**
     * Get a boolean property.
     * @param string $k
     * @param mixed $default
     * @return bool
     */
    public function getBooleanProperty($k, $default = null)
    {
        return ($this->getProperty($k, $default) === 'true' || $this->getProperty($k, $default) === true || $this->getProperty($k, $default) === '1' || $this->getProperty($k, $default) === 1);
    }

    /**
     * {@inheritDoc}
     * @return bool
     */
    public function beforeSave()
    {
        foreach ($this->required as $required) {
            $value = $this->getProperty($required);
            if (empty($value)) {
                $this->addFieldError($required, $this->modx->lexicon('field_required'));
            }
        }

        return parent::beforeSave();
    }

    /**
     * Create an unique alias
     *
     * @param $alias
     * @return string
     */
    public function uniqueAlias($alias)
    {
        $i = 0;
        while ($this->modx->getCount($this->classKey, [
            'alias' => $alias . (($i) ? '-' . $i : '')
        ])) {
            $i += 1;
        }
        return $alias . (($i) ? '-' . $i : '');
    }
}
